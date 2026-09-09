<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
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
			$productionOperation->load([
					'components.material',
					'routeSteps.route',
			]);

			return view('production.operations._show', [
					'operation' => $productionOperation,
			]);
		}

		public function edit(ProductionOperation $productionOperation): View
		{
			$productionOperation->load([
					'components.material',
			]);

			$materials = Material::query()
					->where('is_active', true)
					->orderBy('id')
					->get();

			return view('production.operations._edit', [
					'operation' => $productionOperation,
					'materials' => $materials,
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
					'message' => 'Производственная операция успешно удалена.',
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