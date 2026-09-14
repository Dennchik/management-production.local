<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialIssue;
	use App\Models\MaterialReceipt;
	use App\Models\MaterialReceiptItem;
	use App\Models\MaterialRoll;
	use App\Models\Order;
	use App\Models\ProductionTask;
	use App\Models\ProductionTaskInput;
	use App\Models\ProductionTaskOutput;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Validation\ValidationException;
	use Illuminate\View\View;

	class ProductionTaskController extends Controller
	{
		public function index(Request $request): View
		{
			$user = $request->user();

			$query = ProductionTask::query()
					->with(['material', 'machine', 'order']);

			// Оператор видит задачи только своего станка.
			if (!$user->may('tasks') || $user->machine_id !== null) {
				$query->where('machine_id', $user->machine_id);
			}

			return view('tasks.index', [
					'tasks' => $query->orderBy('id')->get(),
					'statuses' => ProductionTask::STATUSES,
					'ownMachineOnly' => $user->machine_id !== null,
			]);
		}

		public function create(): View
		{
			return view('tasks.create', [
					'products' => Material::query()
							->where('material_type', 'product')
							->where('is_active', true)
							->orderBy('name')
							->get(),
					'machines' => DB::table('machines')->orderBy('name')->get(),
					'orders' => Order::query()->orderByDesc('id')->get(),
			]);
		}

		public function store(Request $request)
		{
			$validated = $request->validate([
					'order_id' => ['nullable', 'integer', 'exists:orders,id'],
					'material_id' => ['required', 'integer', 'exists:materials,id'],
					'quantity' => ['required', 'numeric', 'min:0.001'],
					'machine_id' => ['nullable', 'integer', 'exists:machines,id'],
					'comment' => ['nullable', 'string'],
			]);

			$task = ProductionTask::create($validated + [
					'status' => 'pending',
					'created_by' => auth()->id(),
			]);

			return redirect()
					->route('tasks.show', $task)
					->with('success', 'Задача создана.');
		}

		public function show(ProductionTask $task): View
		{
			return view('tasks.show', [
					'task' => $task->load(['material', 'machine', 'order', 'inputs.material', 'inputs.roll', 'outputs']),
					'statuses' => ProductionTask::STATUSES,
			]);
		}

		/**
		 * Форма старта: оператор указывает рулоны по каждому входному материалу.
		 */
		public function startForm(ProductionTask $task): View
		{
			abort_if($task->status !== 'pending', 422, 'Задача уже начата или закрыта.');

			$recipeInputs = $task->recipeInputs();

			return view('tasks.start', [
					'task' => $task->load('material'),
					'recipeInputs' => $recipeInputs,
					'rollsByMaterial' => $this->rollsForMaterials(
							$recipeInputs->pluck('material_id')->unique()
					),
			]);
		}

		public function start(Request $request, ProductionTask $task)
		{
			abort_if($task->status !== 'pending', 422, 'Задача уже начата или закрыта.');

			$validated = $request->validate([
					'inputs' => ['required', 'array'],
					'inputs.*.material_id' => ['required', 'integer', 'exists:materials,id'],
					'inputs.*.roll_id' => ['nullable', 'integer', 'exists:material_rolls,id'],
					'inputs.*.planned_weight' => ['nullable', 'numeric', 'min:0'],
			]);

			DB::transaction(function () use ($validated, $task) {
				$task->inputs()->delete();

				foreach ($validated['inputs'] as $input) {
					if (empty($input['roll_id'])) {
						continue;
					}

					ProductionTaskInput::create([
							'task_id' => $task->id,
							'material_id' => $input['material_id'],
							'roll_id' => $input['roll_id'],
							'planned_weight' => $input['planned_weight'] ?? null,
					]);
				}

				$task->update([
						'status' => 'in_progress',
						'started_at' => now(),
				]);
			});

			return redirect()
					->route('tasks.show', $task)
					->with('success', 'Задача начата.');
		}

		/**
		 * Форма завершения: фактические веса входов и выходов.
		 */
		public function completeForm(ProductionTask $task): View
		{
			abort_if($task->status !== 'in_progress', 422, 'Задача не в работе.');

			return view('tasks.complete', [
					'task' => $task->load(['material', 'inputs.material', 'inputs.roll']),
					'plannedOutput' => $task->quantity,
			]);
		}

		public function complete(Request $request, ProductionTask $task)
		{
			abort_if($task->status !== 'in_progress', 422, 'Задача не в работе.');

			$validated = $request->validate([
					'inputs' => ['nullable', 'array'],
					'inputs.*.id' => ['required_with:inputs', 'integer', 'exists:production_task_inputs,id'],
					'inputs.*.actual_weight' => ['required_with:inputs', 'numeric', 'min:0'],
					'outputs' => ['required', 'array', 'min:1'],
					'outputs.*.roll_number' => [
							'required', 'string', 'max:50',
							static function ($attribute, $value, $fail) use ($task) {
								if (MaterialRoll::query()
										->where('material_id', $task->material_id)
										->where('roll_number', $value)
										->exists()) {
									$fail("Рулон с номером «{$value}» для этого материала уже существует.");
								}
							},
					],
					'outputs.*.actual_weight' => ['required', 'numeric', 'min:0.001'],
			]);

			$comment = 'Заказ №' . ($task->order_id ?? '—')
					. ' / Задача №' . $task->id
					. ' (' . ($task->material->name ?? '') . ')';

			try {
				DB::transaction(function () use ($validated, $task, $comment) {
					// Списание входного сырья с указанных рулонов
					foreach ($validated['inputs'] ?? [] as $inputData) {
						/** @var ProductionTaskInput $input */
						$input = ProductionTaskInput::query()
								->where('task_id', $task->id)
								->find($inputData['id'] ?? null);

						if ($input === null) {
							continue;
						}

						if ((float) ($inputData['actual_weight'] ?? 0) <= 0 || $input->roll_id === null) {
							continue;
						}

						$roll = MaterialRoll::query()
								->where('id', $input->roll_id)
								->lockForUpdate()
								->firstOrFail();

						if ((float) $inputData['actual_weight'] > (float) $roll->weight) {
							\ValidationException::withMessages([
									'inputs.' . $input->id . '.actual_weight' =>
											"Недостаточно материала на рулоне {$roll->roll_number}: осталось "
											. rtrim(rtrim($roll->weight, '0'), '.') . ' кг.',
							]);
						}

						MaterialIssue::create([
								'material_id' => $roll->material_id,
								'roll_id' => $roll->id,
								'weight' => $inputData['actual_weight'],
								'comment' => $comment,
								'user_id' => auth()->id(),
						]);

						$roll->update(['weight' => (float) $roll->weight - (float) $inputData['actual_weight']]);

						$input->update(['actual_weight' => $inputData['actual_weight']]);
					}

					// Оприходование выходной продукции новыми рулонами
					$receipt = MaterialReceipt::create([
							'comment' => $comment,
							'user_id' => auth()->id(),
					]);

					foreach ($validated['outputs'] as $outputData) {
						$roll = MaterialRoll::create([
								'material_id' => $task->material_id,
								'roll_number' => $outputData['roll_number'],
								'weight' => $outputData['actual_weight'],
						]);

						MaterialReceiptItem::create([
								'material_receipt_id' => $receipt->id,
								'material_id' => $task->material_id,
								'roll_id' => $roll->id,
								'weight' => $outputData['actual_weight'],
						]);

						ProductionTaskOutput::create([
								'task_id' => $task->id,
								'material_id' => $task->material_id,
								'roll_number' => $outputData['roll_number'],
								'actual_weight' => $outputData['actual_weight'],
								'roll_id' => $roll->id,
						]);
					}

					$task->update([
							'status' => 'done',
							'completed_at' => now(),
					]);
				});
			} catch (ValidationException $e) {
				throw $e;
			} catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
				return redirect()
						->back()
						->withInput()
						->withErrors(['outputs.0.roll_number' => 'Рулон с таким номером уже существует для этого материала.']);
			}

			return redirect()
					->route('tasks.show', $task)
					->with('success', 'Задача завершена: сырьё списано, продукция оприходована.');
		}

		private function rollsForMaterials(\Illuminate\Support\Collection $materialIds): array
		{
			$result = [];

			foreach ($materialIds as $materialId) {
				$result[$materialId] = MaterialRoll::query()
						->where('material_id', $materialId)
						->where('weight', '>', 0)
						->orderBy('roll_number')
						->get(['id', 'roll_number', 'weight']);
			}

			return $result;
		}
	}
