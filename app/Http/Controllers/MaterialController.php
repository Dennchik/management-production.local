<?php

	namespace App\Http\Controllers;

	use App\Models\Catalog;
	use App\Models\Material;
	use App\Models\ProductionOperation;
	use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

	class MaterialController extends Controller
	{
		public function index(): View
		{
			$hierarchyEnabled = Catalog::hierarchyEnabled();
			$currentCatalog = null;
			$catalogs = collect();
			$breadcrumbs = collect();

			if ($hierarchyEnabled) {
				$currentCatalog = Catalog::query()->find(request('catalog'));

				$catalogQuery = Catalog::query()
						->orderBy('sort_order')
						->orderBy('name');

				$catalogs = $currentCatalog === null
						? $catalogQuery->whereNull('parent_id')->get()
						: $catalogQuery->where('parent_id', $currentCatalog->id)->get();

				if ($currentCatalog !== null) {
					$breadcrumbs = $this->catalogAncestors($currentCatalog);
				}
			}

			$materialsQuery = Material::query()
				->with('rolls:id,material_id,format,identifier')
				->orderBy('id');

			if ($hierarchyEnabled) {
				$materialsQuery->where('catalog_id', $currentCatalog?->id);
			}

			$materials = $materialsQuery->get();

			return view('materials.index', [
					'materials' => $materials,
					'catalogs' => $catalogs,
					'currentCatalog' => $currentCatalog,
					'breadcrumbs' => $breadcrumbs,
					'hierarchyEnabled' => $hierarchyEnabled,
			]);
		}

		/**
		 * Цепочка предков каталога от корня к текущему каталогу.
		 */
		private function catalogAncestors(Catalog $catalog): \Illuminate\Support\Collection
		{
			$chain = collect([$catalog]);
			$parent = $catalog->parent;

			while ($parent !== null) {
				$chain->prepend($parent);
				$parent = $parent->parent;
			}

			return $chain;
		}

		public function create(): View
		{
			$number = Material::query()->count() + 1;

			return view('materials._create', [
					'number' => $number,
					'catalogOptions' => $this->catalogOptions(),
					'selectedCatalogId' => request('catalog'),
					'productionLines' => $this->productionLines(),
			]);
		}

		/**
		 * Окно выбора: создать каталог или материал.
		 */
		public function createChoice(): View
		{
			return view('materials._create-choice');
		}

		public function edit(Material $material): View
		{
			$number = Material::query()
					->where('id', '<=', $material->id)
					->count();

			return view('materials._edit', [
					'material' => $material,
					'number' => $number,
					'catalogOptions' => $this->catalogOptions(),
					'productionLines' => $this->productionLines(),
					'selectedOperationIds' => $material->allowedOperations()->pluck(
							'production_operations.id'
					)->all(),
			]);
		}

		public function store(Request $request): JsonResponse
		{
		$validated = $request->validate([
				'name' => ['required', 'string', 'max:255'],
				'code' => ['required', 'string', 'max:10'],
				'grammage' => ['nullable', 'numeric', 'min:0'],
				'thickness' => ['nullable', 'numeric', 'min:0'],
				'catalog_id' => ['nullable', 'integer', 'exists:catalogs,id'],
				'material_type' => ['nullable', 'in:raw,product'],
				'allowed_operations' => ['nullable', 'array'],
				'allowed_operations.*' => ['integer', 'exists:production_operations,id'],
				'is_active' => ['boolean'],
		]);

		$material = Material::create([
				'name' => $validated['name'],
				'code' => $validated['code'],
				'grammage' => $validated['grammage'] ?? null,
				'thickness' => $validated['thickness'] ?? null,
				'catalog_id' => $validated['catalog_id'] ?? null,
				'material_type' => $validated['material_type'] ?? 'raw',
				'is_active' => $validated['is_active'] ?? false,
		]);

			$material->allowedOperations()->sync($validated['allowed_operations'] ?? []);

			return response()->json([
					'success' => true,
					'material' => $material,
			]);
		}

	public function show(Material $material): View
	{
		return view('materials._show', [
			'material' => $material->load([
				'allowedOperations',
				'rolls:id,material_id,format,identifier',
			]),
		]);
	}

		public function update(Request $request, Material $material): JsonResponse
		{
		$validated = $request->validate([
				'name' => ['required', 'string', 'max:255'],
				'code' => ['required', 'string', 'max:10'],
				'grammage' => ['nullable', 'numeric', 'min:0'],
				'thickness' => ['nullable', 'numeric', 'min:0'],
				'catalog_id' => ['nullable', 'integer', 'exists:catalogs,id'],
				'material_type' => ['nullable', 'in:raw,product'],
				'allowed_operations' => ['nullable', 'array'],
				'allowed_operations.*' => ['integer', 'exists:production_operations,id'],
				'is_active' => ['boolean'],
		]);

		$material->update([
				'name' => $validated['name'],
				'code' => $validated['code'],
				'grammage' => $validated['grammage'] ?? null,
				'thickness' => $validated['thickness'] ?? null,
				'catalog_id' => $validated['catalog_id'] ?? null,
				'material_type' => $validated['material_type'] ?? $material->material_type,
				'is_active' => $validated['is_active'] ?? false,
		]);

			$material->allowedOperations()->sync($validated['allowed_operations'] ?? []);

			return response()->json([
					'success' => true,
					'material' => $material->fresh(),
			]);
		}

		public function delete(Material $material): View
		{
			return view('materials._delete', [
					'material' => $material,
			]);
		}

		public function destroy(Material $material): JsonResponse
		{
			$material->delete();

			return response()->json([
					'success' => true,
					'message' => 'Материал успешно удалён.',
			]);
		}

		/**
		 * Каталоги для выбора в форме материала.
		 */
		private function catalogOptions(): array
		{
			return Catalog::selectableParents()
					->mapWithKeys(static fn (Catalog $catalog) => [
							$catalog->id => Catalog::pathMap()[$catalog->id] ?? $catalog->name,
					])
					->all();
		}

		/**
		 * Активные технологические линии для блока разрешённых операций.
		 */
		private function productionLines()
		{
			return ProductionOperation::query()
					->where('is_active', true)
					->orderBy('id')
					->get();
		}

}