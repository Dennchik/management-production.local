<?php

	namespace App\Http\Controllers;

	use App\Models\Catalog;
	use App\Models\Material;
	use App\Models\ProductionLine;
	use App\Models\ProductionOperation;
	use App\Models\ProductionOperationComponent;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\View\View;

	class ProductionOperationController extends Controller
	{
		public function index(): View
		{
			$operations = ProductionOperation::query()
					->withCount('components')
					->orderBy('id')
					->get();

			return view('production.operations.index', [
					'operations' => $operations,
			]);
		}

		public function create(): View
		{
			$materials = Material::query()
					->where('is_active', true)
					->orderBy('id')
					->get();

			return view('production.operations.create', [
					'materials' => $materials,
			]);
		}

		public function store(Request $request): JsonResponse
		{
			$validated = $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'code' => ['required', 'string', 'max:255', 'unique:production_operations,code'],
					'description' => ['nullable', 'string'],
					'is_active' => ['boolean'],
					'inputs' => ['nullable', 'array'],
					'inputs.*.material_id' => ['required', 'integer', 'exists:materials,id'],
					'inputs.*.quantity' => ['nullable', 'numeric', 'min:0'],
					'inputs.*.unit' => ['required', 'string', 'max:20'],
					'inputs.*.is_required' => ['boolean'],
					'inputs.*.sort_order' => ['nullable', 'integer', 'min:0'],
					'inputs.*.comment' => ['nullable', 'string'],
					'outputs' => ['nullable', 'array'],
					'outputs.*.material_id' => ['required', 'integer', 'exists:materials,id'],
					'outputs.*.quantity' => ['nullable', 'numeric', 'min:0'],
					'outputs.*.unit' => ['required', 'string', 'max:20'],
					'outputs.*.is_required' => ['boolean'],
					'outputs.*.sort_order' => ['nullable', 'integer', 'min:0'],
					'outputs.*.comment' => ['nullable', 'string'],
			]);

			$operation = DB::transaction(function () use ($validated) {
				$operation = ProductionOperation::create([
						'name' => $validated['name'],
						'code' => $validated['code'],
						'description' => $validated['description'] ?? null,
						'is_active' => $validated['is_active'] ?? true,
				]);

				$this->createComponents(
						$operation,
						$validated['inputs'] ?? [],
						'input'
				);

				$this->createComponents(
						$operation,
						$validated['outputs'] ?? [],
						'output'
				);

				return $operation->load('components.material');
			});

			return response()->json([
					'success' => true,
					'operation' => $operation,
			]);
		}

		public function show(ProductionOperation $productionOperation): View
		{
			return view('production.operations._show', [
					'operation' => $productionOperation,
			]);
		}

		public function edit(ProductionOperation $productionOperation): View
		{
			return view('production.operations.edit', [
					'operation' => $productionOperation,
			]);
		}

		public function update(Request $request, ProductionOperation $productionOperation): JsonResponse
		{
			$validated = $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'code' => [
							'required',
							'string',
							'max:255',
							'unique:production_operations,code,' . $productionOperation->id,
					],
					'description' => ['nullable', 'string'],
					'is_active' => ['boolean'],
					'inputs' => ['nullable', 'array'],
					'inputs.*.id' => ['nullable', 'integer'],
					'inputs.*.material_id' => ['required', 'integer', 'exists:materials,id'],
					'inputs.*.quantity' => ['nullable', 'numeric', 'min:0'],
					'inputs.*.unit' => ['required', 'string', 'max:20'],
					'inputs.*.is_required' => ['boolean'],
					'inputs.*.sort_order' => ['nullable', 'integer', 'min:0'],
					'inputs.*.comment' => ['nullable', 'string'],
					'outputs' => ['nullable', 'array'],
					'outputs.*.id' => ['nullable', 'integer'],
					'outputs.*.material_id' => ['required', 'integer', 'exists:materials,id'],
					'outputs.*.quantity' => ['nullable', 'numeric', 'min:0'],
					'outputs.*.unit' => ['required', 'string', 'max:20'],
					'outputs.*.is_required' => ['boolean'],
					'outputs.*.sort_order' => ['nullable', 'integer', 'min:0'],
					'outputs.*.comment' => ['nullable', 'string'],
			]);

			$operation = DB::transaction(function () use ($validated, $productionOperation) {
				$productionOperation->update([
						'name' => $validated['name'],
						'code' => $validated['code'],
						'description' => $validated['description'] ?? null,
						'is_active' => $validated['is_active'] ?? true,
				]);

				$this->syncComponents(
						$productionOperation,
						$validated['inputs'] ?? [],
						'input'
				);

				$this->syncComponents(
						$productionOperation,
						$validated['outputs'] ?? [],
						'output'
				);

				return $productionOperation->load('components.material');
			});

			return response()->json([
					'success' => true,
					'operation' => $operation,
			]);
		}

		public function delete(ProductionOperation $productionOperation): View
		{
			return view('production.operations._delete', [
					'operation' => $productionOperation,
			]);
		}

		public function destroy(ProductionOperation $productionOperation): JsonResponse
		{
			$productionOperation->delete();

			return response()->json([
					'success' => true,
					'message' => 'Технологическая линия успешно удалена.',
			]);
		}

		/**
		 * Список производственных линий, относящихся к шаблону.
		 */
		public function lines(ProductionOperation $productionOperation): View
		{
			return view('production.operations.lines', [
					'operation' => $productionOperation,
					'productionLines' => $productionOperation->productionLines()
						->with(['inputMaterials', 'outputMaterials'])
						->get(),
			]);
		}

		/**
		 * Страница создания производственной линии шаблона.
		 */
		public function createLine(ProductionOperation $productionOperation): View
		{
			return view('production.operations.create-line', [
					'operation' => $productionOperation,
					'materials' => $productionOperation->materials()
						->with('rolls:id,material_id,format,identifier')
						->orderBy('id')
						->get(),
					'outputMaterials' => Material::query()
						->where('material_type', 'product')
						->where('is_active', true)
						->with('rolls:id,material_id,format,identifier')
						->orderBy('id')
						->get(),
			]);
		}

		public function storeLine(Request $request, ProductionOperation $productionOperation): \Illuminate\Http\RedirectResponse
		{
			$validated = $this->validateLineData($request);

			$productionLine = ProductionLine::create([
					'name' => $validated['name'],
					'production_operation_id' => $productionOperation->id,
			]);

			$this->syncLineMaterials($productionLine, 'input', $validated['materials_pairs'] ?? []);
			$this->syncLineMaterials($productionLine, 'output', $validated['output_materials_pairs'] ?? []);

			return redirect()
					->route('production.lines.show', $productionOperation)
					->with('success', 'Производственная линия добавлена.');
	}

		/**
		 * Страница просмотра производственной линии.
		 */
		public function showLine(ProductionOperation $productionOperation, ProductionLine $productionLine): View
		{
			return view('production.operations.show-line', [
					'operation' => $productionOperation,
					'productionLine' => $productionLine->load([
						'inputMaterials.rolls:id,material_id,format,identifier',
						'outputMaterials.rolls:id,material_id,format,identifier',
					]),
			]);
		}

		/**
		 * Страница редактирования производственной линии.
		 */
		public function editLine(ProductionOperation $productionOperation, ProductionLine $productionLine): View
		{
			return view('production.operations.edit-line', [
					'operation' => $productionOperation,
					'productionLine' => $productionLine->load([
						'inputMaterials.rolls:id,material_id,format,identifier',
						'outputMaterials.rolls:id,material_id,format,identifier',
					]),
					'materials' => $productionOperation->materials()
						->with('rolls:id,material_id,format,identifier')
						->orderBy('id')
						->get(),
					'outputMaterials' => Material::query()
						->where('material_type', 'product')
						->where('is_active', true)
						->with('rolls:id,material_id,format,identifier')
						->orderBy('id')
						->get(),
			]);
		}

		public function updateLine(Request $request, ProductionOperation $productionOperation, ProductionLine $productionLine): \Illuminate\Http\RedirectResponse
		{
			$validated = $this->validateLineData($request);

			$productionLine->update([
					'name' => $validated['name'],
			]);

			$this->syncLineMaterials($productionLine, 'input', $validated['materials_pairs'] ?? []);
			$this->syncLineMaterials($productionLine, 'output', $validated['output_materials_pairs'] ?? []);

			return redirect()
					->route('production.lines.line.show', [$productionOperation, $productionLine])
					->with('success', 'Производственная линия обновлена.');
	}

		/**
		 * Валидация данных производственной линии.
		 */
		private function validateLineData(Request $request): array
		{
			// Материал выбирается вместе с форматом:
			// materials[] и materials_formats[] идут парами по индексу строки.
			$data = $request->all();

			foreach (['materials', 'output_materials'] as $key) {
				$ids = array_values($data[$key] ?? []);
				$formats = array_values($data[$key . '_formats'] ?? []);
				$pairs = [];

				foreach ($ids as $index => $materialId) {
					if ($materialId === null || $materialId === '') {
						continue;
					}

					$format = $formats[$index] ?? null;

					$pairs[] = [
							'material_id' => (int) $materialId,
							'format' => $format !== null && $format !== '' ? (int) $format : null,
					];
				}

				$data[$key . '_pairs'] = $pairs;
				unset($data[$key], $data[$key . '_formats']);
			}

			$request->replace($data);

			return $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'materials_pairs' => ['nullable', 'array'],
					'materials_pairs.*.material_id' => ['integer', 'exists:materials,id'],
					'materials_pairs.*.format' => ['nullable', 'integer', 'min:0', 'max:65535'],
					'output_materials_pairs' => ['nullable', 'array'],
					'output_materials_pairs.*.material_id' => ['integer', 'exists:materials,id'],
					'output_materials_pairs.*.format' => ['nullable', 'integer', 'min:0', 'max:65535'],
			]);
		}

		/**
		 * Заменяет материалы линии одного направления (вход/выход).
		 * Материал всегда входит с конкретным форматом.
		 */
		private function syncLineMaterials(ProductionLine $productionLine, string $direction, array $pairs): void
		{
			$rows = collect($pairs)
				->unique(static fn (array $pair) => $pair['material_id'] . '|' . ($pair['format'] ?? ''))
				->map(static fn (array $pair) => [
						'production_line_id' => $productionLine->id,
						'material_id' => $pair['material_id'],
						'direction' => $direction,
						'format' => $pair['format'] ?? null,
				])
				->values()
				->all();

			DB::transaction(static function () use ($productionLine, $direction, $rows) {
				DB::table('production_line_material')
					->where('production_line_id', $productionLine->id)
					->where('direction', $direction)
					->delete();

				if ($rows !== []) {
					DB::table('production_line_material')->insert($rows);
				}
			});
		}

		public function destroyLine(ProductionLine $productionLine): JsonResponse
		{
			$operation = $productionLine->operation;

			$productionLine->delete();

			return response()->json([
					'success' => true,
					'message' => 'Производственная линия успешно удалена.',
					'redirect' => $operation !== null
							? route('production.lines.show', $operation)
							: null,
			]);
		}

	/**
	 * Страница настройки разрешённых операций шаблона по каталогам и материалам.
	 *
	 * Структура повторяет страницу материалов: сначала корневые каталоги,
	 * вход в подкаталоги по ссылкам, материалы текущего каталога.
	 * Статус каталога вычисляется по материалам его поддерева:
	 * все материалы с разрешённой операцией — «all», часть — «partial», ни один — «none».
	 */
	public function allowedCatalogs(Request $request, ProductionOperation $productionOperation): View
	{
		$hierarchyEnabled = Catalog::hierarchyEnabled();
		$currentCatalog = null;
		$catalogs = collect();
		$breadcrumbs = collect();

		if ($hierarchyEnabled) {
			$currentCatalog = Catalog::query()->find($request->query('catalog'));

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

		$materialsQuery = Material::query()->orderBy('id');

		if ($hierarchyEnabled) {
			$materialsQuery->where('catalog_id', $currentCatalog?->id);
		}

		$materials = $materialsQuery->get();

		$allowedMaterialIds = DB::table('material_production_operation')
				->where('production_operation_id', $productionOperation->id)
				->pluck('material_id');

		return view('production.operations.allowed-catalogs', [
				'operation' => $productionOperation,
				'catalogs' => $catalogs,
				'catalogStates' => $this->catalogSubtreeStates($catalogs, $productionOperation),
				'materials' => $materials,
				'allowedMaterialIds' => $allowedMaterialIds,
				'currentCatalog' => $currentCatalog,
				'breadcrumbs' => $breadcrumbs,
				'hierarchyEnabled' => $hierarchyEnabled,
		]);
	}

	/**
	 * Статусы разрешённых операций для поддеревьев указанных каталогов.
	 */
	private function catalogSubtreeStates($catalogs, ProductionOperation $productionOperation): \Illuminate\Support\Collection
	{
		$allCatalogs = Catalog::query()
				->orderBy('sort_order')
				->orderBy('name')
				->get();

		$materialCounts = Material::query()
				->whereNotNull('catalog_id')
				->groupBy('catalog_id')
				->selectRaw('catalog_id, count(*) as total_count')
				->pluck('total_count', 'catalog_id');

		$allowedCounts = DB::table('material_production_operation')
				->join('materials', 'materials.id', '=', 'material_production_operation.material_id')
				->where('material_production_operation.production_operation_id', $productionOperation->id)
				->whereNotNull('materials.catalog_id')
				->groupBy('materials.catalog_id')
				->selectRaw('materials.catalog_id, count(*) as allowed_count')
				->pluck('allowed_count', 'materials.catalog_id');

		$childrenMap = $allCatalogs->groupBy('parent_id');
		$subtreeCounts = [];

		$aggregate = function (int $catalogId) use (&$aggregate, $childrenMap, $materialCounts, $allowedCounts, &$subtreeCounts): array {
				if (array_key_exists($catalogId, $subtreeCounts)) {
					return $subtreeCounts[$catalogId];
				}

				$total = (int) ($materialCounts[$catalogId] ?? 0);
				$allowed = (int) ($allowedCounts[$catalogId] ?? 0);

				foreach ($childrenMap->get($catalogId, collect()) as $child) {
					[$childTotal, $childAllowed] = $aggregate($child->id);

					$total += $childTotal;
					$allowed += $childAllowed;
				}

				return $subtreeCounts[$catalogId] = [$total, $allowed];
		};

		return $catalogs->mapWithKeys(function (Catalog $catalog) use ($aggregate) {
				[$total, $allowed] = $aggregate($catalog->id);

				$state = $total === 0 ? 'none' : ($allowed === $total ? 'all' : ($allowed === 0 ? 'none' : 'partial'));

				return [$catalog->id => ['total' => $total, 'allowed' => $allowed, 'state' => $state]];
		});
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

	/**
	 * Применяет разрешение операции к материалу или ко всем материалам поддерева каталога.
	 */
	public function updateAllowedCatalogs(Request $request, ProductionOperation $productionOperation): JsonResponse
	{
		$validated = $request->validate([
				'catalog_id' => ['required_without:material_id', 'integer', 'exists:catalogs,id'],
				'material_id' => ['required_without:catalog_id', 'integer', 'exists:materials,id'],
				'allowed' => ['required', 'boolean'],
		]);

		if (isset($validated['material_id'])) {
			$materialIds = collect([(int) $validated['material_id']]);
		} else {
			$catalogIds = Catalog::descendantIds((int) $validated['catalog_id'])
					->push((int) $validated['catalog_id']);

			$materialIds = Material::query()
					->whereIn('catalog_id', $catalogIds)
					->pluck('id');
		}

		DB::transaction(function () use ($validated, $productionOperation, $materialIds) {
				if ($validated['allowed']) {
					$rows = $materialIds
							->map(static fn (int $materialId) => [
									'material_id' => $materialId,
									'production_operation_id' => $productionOperation->id,
							])
							->all();

					if ($rows !== []) {
						DB::table('material_production_operation')->insertOrIgnore($rows);
					}
				} else {
					DB::table('material_production_operation')
							->where('production_operation_id', $productionOperation->id)
							->whereIn('material_id', $materialIds)
							->delete();
				}
		});

		$count = $materialIds->count();

		return response()->json([
				'success' => true,
				'message' => $validated['allowed']
						? "Разрешение выдано для материалов поддерева: {$count}."
						: "Разрешение отозвано для материалов поддерева: {$count}.",
		]);
	}

	/**
	 * Окно подтверждения удаления производственной линии.
	 */
	public function deleteLine(ProductionLine $productionLine): View
		{
			return view('production.operations._delete-line', [
					'productionLine' => $productionLine,
			]);
		}

		private function createComponents(
				ProductionOperation $operation,
				array $components,
				string $direction
		): void {
			foreach ($components as $index => $component) {
				ProductionOperationComponent::create([
						'operation_id' => $operation->id,
						'material_id' => $component['material_id'],
						'direction' => $direction,
						'quantity' => $component['quantity'] ?? null,
						'unit' => $component['unit'],
						'is_required' => $component['is_required'] ?? true,
						'sort_order' => $component['sort_order'] ?? $index,
						'comment' => $component['comment'] ?? null,
				]);
			}
		}

		private function syncComponents(
				ProductionOperation $operation,
				array $components,
				string $direction
		): void {
			$existingIds = [];

			foreach ($components as $index => $component) {
				$componentId = $component['id'] ?? null;

				if ($componentId !== null) {
					$operationComponent = $operation->components()
							->where('id', $componentId)
							->where('direction', $direction)
							->firstOrFail();

					$operationComponent->update([
							'material_id' => $component['material_id'],
							'quantity' => $component['quantity'] ?? null,
							'unit' => $component['unit'],
							'is_required' => $component['is_required'] ?? true,
							'sort_order' => $component['sort_order'] ?? $index,
							'comment' => $component['comment'] ?? null,
					]);

					$existingIds[] = $operationComponent->id;

					continue;
				}

				$operationComponent = ProductionOperationComponent::create([
						'operation_id' => $operation->id,
						'material_id' => $component['material_id'],
						'direction' => $direction,
						'quantity' => $component['quantity'] ?? null,
						'unit' => $component['unit'],
						'is_required' => $component['is_required'] ?? true,
						'sort_order' => $component['sort_order'] ?? $index,
						'comment' => $component['comment'] ?? null,
				]);

				$existingIds[] = $operationComponent->id;
			}

			$operation->components()
					->where('direction', $direction)
					->whereNotIn('id', $existingIds)
					->delete();
		}
	}