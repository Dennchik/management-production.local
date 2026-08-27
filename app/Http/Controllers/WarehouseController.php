<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use Illuminate\Http\Request;
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

			$materialsQuery = Material::query()
					->withCount([
							'rolls as rolls_count' => function ($query) {
								$query->where('weight', '>', 0);
							},
					])
					->withSum('rolls as total_weight', 'weight');

			if ($search) {
				$materialsQuery->where(function ($query) use ($search) {
					$query
							->where('name', 'ilike', "%{$search}%")
							->orWhere('identifier', 'ilike', "%{$search}%");
				});
			}

			if ($format) {
				$materialsQuery->where('format', $format);
			}

			if ($code) {
				$materialsQuery->where('code', $code);
			}

			// ДОБАВЛЕНО:
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

			$formats = Material::query()
					->select('format')
					->distinct()
					->orderBy('format')
					->pluck('format');

			$materialTypes = Material::query()
					->select('code')
					->distinct()
					->orderBy('code')
					->pluck('code');

			return view('warehouse.index', compact(
					'materials',
					'formats',
					'materialTypes',
					'search',
					'format',
					'stock',
					'code',
					'codes'
			));
		}

		/**
		 * Отображает карточку материала
		 * и физические рулоны этого материала.
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
			 * с положительным остатком.
			 */
			$rollsCount = $material->rolls->count();

			/*
			 * Общий текущий остаток материала.
			 */
			$totalWeight = $material->rolls->sum(
					fn($roll) => (float)$roll->weight
			);

			return view('warehouse.material', [
					'material' => $material,
					'rolls' => $material->rolls,
					'rollsCount' => $rollsCount,
					'totalWeight' => $totalWeight,
			]);
		}
	}