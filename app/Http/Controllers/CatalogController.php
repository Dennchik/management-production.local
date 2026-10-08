<?php

	namespace App\Http\Controllers;

	use App\Models\Catalog;
	use App\Models\Material;
	use App\Models\MaterialRoll;
	use App\Models\Setting;
	use Illuminate\Http\Exceptions\HttpResponseException;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Validation\Rule;
	use Illuminate\View\View;

	class CatalogController extends Controller
	{
		public function index(): View
		{
			$hierarchyEnabled = Catalog::hierarchyEnabled();

			$catalogs = Catalog::query()
					->withCount('materials')
					->orderBy('sort_order')
					->orderBy('name')
					->get();

			$paths = Catalog::pathMap();

			return view('catalogs.index', [
					'catalogs' => $catalogs,
					'paths' => $paths,
					'hierarchyEnabled' => $hierarchyEnabled,
			]);
		}

		public function create(): View
		{
			$selectedParentId = request('parent');

			// Копирование: форма создания, заполненная данными источника.
			$copyFrom = request('copy_from');
			$copy = $copyFrom !== null && $copyFrom !== ''
					? Catalog::query()->findOrFail((int) $copyFrom)
					: null;

			if ($copy !== null) {
					$selectedParentId = $copy->parent_id;
			}

			return view('catalogs._create', [
					'parents' => Catalog::selectableParents(),
					'selectedParentId' => $selectedParentId !== null ? (int) $selectedParentId : null,
					'copy' => $copy,
					'copyName' => $copy !== null ? $copy->name . ' (копия)' : null,
			]);
		}

		public function store(Request $request): JsonResponse
		{
			$validated = $this->validateCatalog($request);

			$catalog = Catalog::create([
					'name' => $validated['name'],
					'parent_id' => $validated['parent_id'] ?? null,
					'sort_order' => $validated['sort_order'] ?? 500,
					'is_active' => $validated['is_active'] ?? false,
			]);

			return response()->json([
					'success' => true,
					'catalog' => $catalog,
			]);
		}

		public function show(Catalog $catalog): View
		{
			return view('catalogs._show', [
					'catalog' => $catalog,
					'path' => Catalog::pathMap()[$catalog->id] ?? $catalog->name,
			]);
		}

		public function edit(Catalog $catalog): View
		{
			return view('catalogs._edit', [
					'catalog' => $catalog,
					'parents' => Catalog::selectableParents($catalog->id),
			]);
		}

		public function update(Request $request, Catalog $catalog): JsonResponse
		{
			$validated = $this->validateCatalog($request, $catalog);

			$catalog->update([
					'name' => $validated['name'],
					'parent_id' => $validated['parent_id'] ?? null,
					'sort_order' => $validated['sort_order'] ?? $catalog->sort_order,
					'is_active' => $validated['is_active'] ?? false,
			]);

			return response()->json([
					'success' => true,
					'catalog' => $catalog->fresh(),
			]);
		}

		public function delete(Catalog $catalog): View
		{
			/*
			 * Материалы каталога без остатков удалятся вместе
			 * с каталогом (в корзину) — предупреждаем в окне.
			 */
			$materials = $catalog->materials()
					->with('rolls')
					->get();

			$deletable = $materials->filter(
					static fn (Material $material) => $material->rolls
							->every(static fn (MaterialRoll $roll) => (float) $roll->weight <= 0)
			);

			return view('catalogs._delete', [
					'catalog' => $catalog,
					'deletableMaterials' => $deletable,
			]);
		}

		public function destroy(Catalog $catalog): JsonResponse
		{
			if (Catalog::descendantIds($catalog->id)->isNotEmpty()) {
				return response()->json([
						'success' => false,
						'message' => 'Сначала удалите вложенные каталоги.',
				], 422);
			}

			$materials = $catalog->materials()
					->with('rolls')
					->get();

			/*
			 * Удаление запрещено, пока в материалах каталога
			 * есть рулоны в наличии: сначала корректировочный ордер.
			 */
			$inStock = $materials->filter(
					static fn (Material $material) => $material->rolls
							->contains(static fn (MaterialRoll $roll) => (float) $roll->weight > 0)
			);

			if ($inStock->isNotEmpty()) {
				return response()->json([
						'success' => false,
						'message' => 'В каталоге присутствуют материалы в наличии, требуется корректировочный ордер.',
				], 422);
			}

			DB::transaction(function () use ($materials, $catalog) {
				// Материалы без остатков удаляются в корзину
				// вместе со своими рулонами и каталогом.
				$materials->each(static function (Material $material) {
					$material->rolls()->delete();
					$material->delete();
				});

				$catalog->delete();
			});

			return response()->json([
					'success' => true,
					'message' => 'Каталог удалён в корзину.'
							. ($materials->isNotEmpty() ? ' Вместе с ним удалено материалов: ' . $materials->count() . '.' : ''),
			]);
		}

		public function toggleHierarchy(): JsonResponse
		{
			$enabled = !Catalog::hierarchyEnabled();

			Setting::set(Catalog::HIERARCHY_SETTING_KEY, $enabled ? '1' : '0');

			return response()->json([
					'success' => true,
					'hierarchy_enabled' => $enabled,
			]);
		}

		/**
		 * Валидация данных каталога, включая защиту от циклической вложенности.
		 */
		private function validateCatalog(Request $request, ?Catalog $catalog = null): array
		{
			$validated = $request->validate([
					'name' => ['required', 'string', 'max:255'],
					// Удалённые (в корзине) каталоги нельзя выбирать родителем.
					'parent_id' => [
							'nullable',
							'integer',
							Rule::exists('catalogs', 'id')->whereNull('deleted_at'),
					],
					'sort_order' => ['nullable', 'integer', 'min:0'],
					'is_active' => ['boolean'],
			]);

			$parentId = $validated['parent_id'] ?? null;

			if ($catalog !== null && $parentId !== null) {
				if ($parentId === $catalog->id || Catalog::descendantIds($catalog->id)->contains($parentId)) {
					$message = 'Нельзя вложить каталог в самого себя или в своего потомка.';

					throw new HttpResponseException(response()->json([
							'message' => $message,
							'errors' => [
									'parent_id' => [$message],
							],
					], 422));
				}
			}

			// При выключенной иерархии вложенность запрещена.
			if (!Catalog::hierarchyEnabled()) {
				$validated['parent_id'] = null;
			}

			return $validated;
		}
	}
