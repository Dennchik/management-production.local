<?php

	namespace App\Http\Controllers;

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
					'productionLines' => $productionOperation->productionLines,
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
							->orderBy('id')
							->get(),
					'outputMaterials' => Material::query()
							->where('material_type', 'product')
							->where('is_active', true)
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

			$this->syncLineMaterials($productionLine, 'input', $validated['materials'] ?? []);
			$this->syncLineMaterials($productionLine, 'output', $validated['output_materials'] ?? []);

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
					'productionLine' => $productionLine->load(['inputMaterials', 'outputMaterials']),
			]);
		}

		/**
		 * Страница редактирования производственной линии.
		 */
		public function editLine(ProductionOperation $productionOperation, ProductionLine $productionLine): View
		{
			return view('production.operations.edit-line', [
					'operation' => $productionOperation,
					'productionLine' => $productionLine->load(['inputMaterials', 'outputMaterials']),
					'materials' => $productionOperation->materials()->orderBy('id')->get(),
					'outputMaterials' => Material::query()
							->where('material_type', 'product')
							->where('is_active', true)
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

			$this->syncLineMaterials($productionLine, 'input', $validated['materials'] ?? []);
			$this->syncLineMaterials($productionLine, 'output', $validated['output_materials'] ?? []);

			return redirect()
					->route('production.lines.line.show', [$productionOperation, $productionLine])
					->with('success', 'Производственная линия обновлена.');
		}

		/**
		 * Валидация данных производственной линии.
		 */
		private function validateLineData(Request $request): array
		{
			// Невыбранные строки материалов приходят пустыми значениями.
			$data = $request->all();

			foreach (['materials', 'output_materials'] as $key) {
				$data[$key] = array_values(array_filter(
						$data[$key] ?? [],
						static fn ($value) => $value !== null && $value !== ''
				));
			}

			$request->replace($data);

			return $request->validate([
					'name' => ['required', 'string', 'max:255'],
					'materials' => ['nullable', 'array'],
					'materials.*' => ['integer', 'exists:materials,id'],
					'output_materials' => ['nullable', 'array'],
					'output_materials.*' => ['integer', 'exists:materials,id'],
			]);
		}

		/**
		 * Заменяет материалы линии одного направления (вход/выход).
		 */
		private function syncLineMaterials(ProductionLine $productionLine, string $direction, array $materialIds): void
		{
			DB::table('production_line_material')
					->where('production_line_id', $productionLine->id)
					->where('direction', $direction)
					->whereNotIn('material_id', $materialIds)
					->delete();

			foreach ($materialIds as $materialId) {
				DB::table('production_line_material')->updateOrInsert(
						[
								'production_line_id' => $productionLine->id,
								'material_id' => $materialId,
								'direction' => $direction,
						],
						[]
				);
			}
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