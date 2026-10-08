<?php

	namespace App\Http\Controllers;

	use App\Models\Catalog;
	use App\Models\Material;
	use Illuminate\Http\Request;
	use Illuminate\Support\Collection;
	use Illuminate\View\View;

	class WarehouseController extends Controller
	{
		/**
		 * Отображает остатки материалов на складе.
		 */
		public function index(Request $request): View
		{
			$search = $request->input('search');
			$format = $request->input('format');
			$stock = $request->input('stock');
			$code = $request->input('code');
			// Поддерживаем как один код, так и несколько кодов.
			$codes = $request->input('codes', []);
			if (!is_array($codes)) {
				$codes = [$codes];
			}
			$codes = array_values(array_filter($codes));

			/*
			 * Складские фильтры (поиск, формат, статус остатка, коды)
			 * пробивают иерархию: материалы всех каталогов показываются
			 * плоским списком. Без фильтров — раскрывающееся дерево
			 * каталогов, в котором перечислены все каталоги и материалы.
			 */
			$hasStockFilters = $search !== null
					|| $format !== null
					|| $stock !== null
					|| $code !== null
					|| $codes !== [];

			$flatList = $hasStockFilters;

			$currentCatalog = Catalog::query()->find($request->input('catalog'));

			// Ветка до каталога из адреса раскрывается в дереве.
			$activePath = $currentCatalog === null
					? collect()
					: $this->catalogAncestors($currentCatalog)->pluck('id');

			// Адрес списка запоминается для кнопки «Назад» карточки
			// материала; раскрытые ветви дерева хранит браузер.
			session()->put('warehouse.index_url', $request->fullUrl());

			$materialsQuery = Material::query()
					->with([
							'rolls' => function ($query) {
								$query
									->select('id', 'material_id', 'weight')
									->where('weight', '>', 0);
							},
					]);

			if ($search) {
				$materialsQuery->where(function ($query) use ($search) {
					$query
						->whereIlike('name', "%{$search}%")
						->orWhereIlike('identifier', "%{$search}%");
				});
			}

			if ($format) {
				$materialsQuery->where('format', $format);
			}

			if ($code) {
				$materialsQuery->where('code', $code);
			}

			if ($codes) {
				$materialsQuery->whereIn('code', $codes);
			}

			if ($stock === 'available') {
				$materialsQuery->whereHas('rolls', function ($query) {
					$query->where('weight', '>', 0);
				});
			}

			if ($stock === 'empty') {
				$materialsQuery->whereDoesntHave('rolls', function ($query) {
					$query->where('weight', '>', 0);
				});
			}

			if ($stock === 'low') {
				$materialsQuery
						->whereHas('rolls', function ($query) {
							$query->where('weight', '>', 0);
						})
						->whereRaw(
								'(SELECT COALESCE(SUM(material_rolls.weight), 0) 
								FROM material_rolls WHERE material_rolls.material_id = materials.id) < 50'
						);
			}

			$materials = $materialsQuery
					->orderBy('name')
					->get();

			// Материалы раскладываются по своим каталогам для дерева.
			$materialsByCatalog = $materials->groupBy(
					static fn (Material $material) => $material->catalog_id ?? 0
			);

			$rootMaterials = $materialsByCatalog->get(0, collect())->values();

			$catalogTree = $flatList
					? collect()
					: $this->catalogTree(
							Catalog::query()
									->orderBy('sort_order')
									->orderBy('name')
									->get(),
							$materialsByCatalog
					);

			// Формат — атрибут материала: список для фильтра строится из материалов.
			$formats = Material::query()
				->select('format')
				->distinct()
				->whereNotNull('format')
				->orderBy('format')
				->pluck('format');

			return view('warehouse.index', compact(
					'materials',
					'rootMaterials',
					'catalogTree',
					'activePath',
					'flatList',
					'formats',
					'search',
					'format',
					'stock',
					'code',
					'codes',
					'currentCatalog'
			));
		}

		/**
		 * Рекурсивное дерево каталогов с материалами каждого каталога:
		 * узел — id, имя, материалы и потомки.
		 */
		private function catalogTree(Collection $catalogs, Collection $materialsByCatalog, ?int $parentId = null): Collection
		{
			return $catalogs
					->where('parent_id', '=', $parentId)
					->values()
					->map(fn (Catalog $catalog) => [
							'id' => $catalog->id,
							'name' => $catalog->name,
							'materials' => $materialsByCatalog->get($catalog->id, collect())->values(),
							'children' => $this->catalogTree($catalogs, $materialsByCatalog, $catalog->id),
					]);
		}

		/**
		 * Цепочка предков каталога от корня к текущему каталогу.
		 */
		private function catalogAncestors(Catalog $catalog): Collection
		{
			$chain = collect([$catalog]);
			$parent = $catalog->parent;

			while ($parent !== null) {
				$chain->prepend($parent);
				$parent = $parent->parent;
			}

			return $chain;
		}

		/**
		 * Отображает карточку материала.
		 */
		public function material(Material $material): View
		{
			$material->load([
					'rolls' => function ($query) {
						$query
								->where('weight', '>', 0)
								->orderBy('roll_number');
					},
			]);

			/*
			 * Количество физических рулонов
			 * с положительным остатком и общий
			 * остаток материала.
			 */
			$rollsCount = $material->rolls->count();

			$totalWeight = $material->rolls->sum(
					fn($roll) => (float)$roll->weight
			);

			return view('warehouse.material', [
					'material' => $material,
					'rollsCount' => $rollsCount,
					'totalWeight' => $totalWeight,
			]);
		}
	}
