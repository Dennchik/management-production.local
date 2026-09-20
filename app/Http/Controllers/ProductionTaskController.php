<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialIssue;
	use App\Models\MaterialReceipt;
	use App\Models\MaterialReceiptItem;
	use App\Models\MaterialRoll;
	use App\Models\ProductionLine;
	use App\Models\ProductionOperation;
	use App\Models\ProductionTask;
	use App\Models\ProductionTaskInput;
	use App\Models\ProductionTaskOutput;
	use App\Models\User;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Validation\ValidationException;
	use Illuminate\View\View;

	class ProductionTaskController extends Controller
	{
	public function index(Request $request): View
	{
		$tasks = ProductionTask::query()
			->with(['material', 'productionLine.operation', 'operator'])
			->withSum('outputs as produced_weight', 'actual_weight')
			->orderBy('id')
			->get();

		return view('tasks.index', [
				'tasks' => $tasks,
				'statuses' => ProductionTask::STATUSES,
		]);
	}

		public function create(): View
		{
			return view('tasks.create', $this->formOptions());
		}

		public function store(Request $request)
		{
			$validated = $request->validate($this->taskRules($request));

			$outputMaterialId = $this->outputMaterialId($validated);

			$task = ProductionTask::create([
					'number' => $validated['number'],
					'production_line_id' => $validated['production_line_id'] ?? null,
					'material_id' => $outputMaterialId,
					'quantity' => $validated['quantity'],
					'operator_id' => $validated['operator_id'] ?? null,
					'comment' => $validated['comment'] ?? null,
					'status' => 'pending',
					'created_by' => auth()->id(),
			]);

			$this->syncTaskMaterials($task, 'input', $validated['materials_pairs'] ?? []);
			$this->syncTaskMaterials($task, 'output', $validated['output_materials_pairs'] ?? []);

			return redirect()
					->route('tasks.show', $task)
					->with('success', 'Задача создана.');
		}

		public function edit(ProductionTask $task): View
		{
			abort_unless($task->isEditable(), 422, 'Завершённую или отменённую задачу нельзя редактировать.');

			return view('tasks.edit', $this->formOptions($task) + [
					'task' => $task->load([
						'material',
						'inputMaterials.rolls:id,material_id,format,identifier',
						'outputMaterials.rolls:id,material_id,format,identifier',
					]),
			]);
		}

		public function update(Request $request, ProductionTask $task)
		{
			abort_unless($task->isEditable(), 422, 'Завершённую или отменённую задачу нельзя редактировать.');

			// У начатой задачи уже выбраны рулоны-сырьё, поэтому состав материалов фиксируется
			if ($task->status === 'pending') {
				$validated = $request->validate($this->taskRules($request, $task));

				$outputMaterialId = $this->outputMaterialId($validated);

				$task->update([
						'number' => $validated['number'],
						'production_line_id' => $validated['production_line_id'] ?? null,
						'material_id' => $outputMaterialId,
						'quantity' => $validated['quantity'],
						'operator_id' => $validated['operator_id'] ?? null,
						'comment' => $validated['comment'] ?? null,
				]);

				$this->syncTaskMaterials($task, 'input', $validated['materials_pairs'] ?? []);
				$this->syncTaskMaterials($task, 'output', $validated['output_materials_pairs'] ?? []);
			} else {
			$validated = $request->validate([
					'quantity' => ['required', 'numeric', 'min:0.001'],
					'operator_id' => ['nullable', 'integer', 'exists:users,id'],
					'comment' => ['nullable', 'string'],
			]);

				$task->update($validated);
			}

			return redirect()
					->route('tasks.show', $task)
					->with('success', 'Задача сохранена.');
		}

	public function show(ProductionTask $task): View
	{
		$task->load([
			'material',
			'operator',
			'productionLine.operation',
			'inputMaterials.rolls:id,material_id,format,identifier',
			'inputs.material',
			'inputs.roll',
			'outputs',
		]);

			// Свободные рулоны для дозабора прямо на странице задачи.
			$availableRolls = [];

			if ($task->status === 'in_progress') {
				$takenRollIds = $task->inputs()->pluck('roll_id')->filter()->values();

				$availableRolls = $this->rollsForMaterials($task->inputMaterials, $takenRollIds);
			}

			return view('tasks.show', [
					'task' => $task,
					'statuses' => ProductionTask::STATUSES,
					'availableRolls' => $availableRolls,
			]);
		}

	/**
	 * Старт задачи: сразу переводит её «В работу» — рулоны берутся
	 * на самой странице задачи, пока она в работе.
	 * Если оператор не назначен при создании, им становится тот, кто начал.
	 */
	public function start(Request $request, ProductionTask $task)
	{
		abort_if($task->status !== 'pending', 422, 'Задача уже начата или закрыта.');

		$task->update([
				'status' => 'in_progress',
				'started_at' => now(),
				'operator_id' => $task->operator_id ?? auth()->id(),
		]);

		return redirect()
			->route('tasks.show', $task)
			->with('success', 'Задача начата.');
	}

		/**
		 * Дозабор рулона прямо со страницы задачи: оператор может взять
		 * дополнительный рулон по входному материалу, пока задача в работе.
		 */
		public function addInput(Request $request, ProductionTask $task)
		{
			abort_if($task->status !== 'in_progress', 422, 'Дозабор рулонов доступен только для задачи в работе.');

			$validated = $request->validate([
					'inputs' => ['required', 'array'],
					'inputs.*.material_id' => [
							'required', 'integer', 'exists:materials,id',
							// Материал обязан быть входом этой задачи
							static function ($attribute, $value, $fail) use ($task) {
								$exists = $task->inputMaterials()
									->reorder('materials.id')
									->where('materials.id', (int) $value)
									->exists();

								if (!$exists) {
									$fail('Материал не является входом этой задачи.');
								}
							},
					],
					'inputs.*.roll_id' => [
							'nullable', 'integer', 'exists:material_rolls,id',
					// Рулон нельзя взять повторно
					static function ($attribute, $value, $fail) use ($task) {
						if (!empty($value) && $task->inputs()->where('roll_id', (int) $value)->exists()) {
							$fail('Этот рулон уже взят задачей.');
						}
					},
				],
		]);

		$added = 0;

		DB::transaction(function () use ($validated, $task, &$added) {
			foreach ($validated['inputs'] as $input) {
				if (empty($input['roll_id'])) {
					continue;
				}

				ProductionTaskInput::create([
						'task_id' => $task->id,
						'material_id' => $input['material_id'],
						'roll_id' => $input['roll_id'],
				]);

				$added++;
			}
		});

		if ($added === 0) {
			$error = ['inputs' => 'Выберите рулон.'];

			if ($request->wantsJson()) {
				return response()->json(['message' => $error['inputs'], 'errors' => $error], 422);
			}

			return back()->withErrors($error);
		}

		if ($request->wantsJson()) {
			return response()->json(['success' => true, 'added' => $added]);
		}

		return redirect()
				->route('tasks.show', $task)
				->with('success', 'Рулон добавлен к задаче.');
	}

	/**
	 * Текущий расход по взятому рулону сохраняется сразу, чтобы правки
	 * не терялись при обновлении страницы. Остаток = вес рулона − расход.
	 */
	public function saveInput(Request $request, ProductionTask $task, ProductionTaskInput $input)
	{
		abort_unless($task->status === 'in_progress', 422, 'Расход сохраняется только у задачи в работе.');
		abort_unless($input->task_id === $task->id, 404);

		$validated = $request->validate([
				// Минимум проверяется ниже по пересчитанной паре:
				// остаток может уходить в минус — расход бывает больше веса рулона
				'used' => ['nullable', 'numeric'],
				'remaining' => ['nullable', 'numeric'],
		]);

		$rollWeight = (float) ($input->roll?->weight ?? 0);

		$used = ($validated['remaining'] ?? null) !== null
				? round($rollWeight - (float) $validated['remaining'], 3)
				: round((float) ($validated['used'] ?? 0), 3);

		if ($used < 0) {
			return response()->json([
					'message' => 'Расход не может быть отрицательным.',
					'errors' => ['used' => ['Расход не может быть отрицательным.']],
			], 422);
		}

		$input->update(['actual_weight' => $used]);

		return response()->json([
				'success' => true,
				'used' => $used,
				'remaining' => round($rollWeight - $used, 3),
		]);
	}

	/**
	 * Завершение задачи прямо со страницы задачи: по каждому взятому рулону —
	 * остаток или расход, по продукции — произведённые рулоны с номерами.
	 */
	public function complete(Request $request, ProductionTask $task)
	{
		abort_if($task->status !== 'in_progress', 422, 'Задача не в работе.');

		$validated = $request->validate([
				'inputs' => ['nullable', 'array'],
				'inputs.*.id' => ['required_with:inputs', 'integer', 'exists:production_task_inputs,id'],
				// Достаточно любого из двух полей: второе пересчитывается
				// на клиенте и дублируется сюда. Остаток может уходить в минус —
				// расход бывает больше текущего веса рулона.
				'inputs.*.remaining' => ['nullable', 'numeric'],
				'inputs.*.used' => ['nullable', 'numeric', 'min:0'],
				'outputs' => ['required', 'array', 'min:1'],
				'outputs.*.roll_number' => ['required', 'string', 'max:50'],
				'outputs.*.actual_weight' => ['required', 'numeric', 'min:0.001'],
		]);

		// Не выполнена по объёму — завершить нельзя: задача остаётся в работе,
		// продукцию можно добрать (перевыполнение плана разрешено).
		$produced = round(array_sum(array_map(
				static fn (array $output) => (float) $output['actual_weight'],
				$validated['outputs']
		)), 3);

		if ($produced < (float) $task->quantity) {
			$missing = round((float) $task->quantity - $produced, 3);

			throw ValidationException::withMessages([
					'outputs' => 'Задача не выполнена по объёму: произведено '
							. rtrim(rtrim(number_format($produced, 3, '.', ''), '0'), '.')
							. ' из ' . rtrim(rtrim(number_format((float) $task->quantity, 3, '.', ''), '0'), '.')
							. ' кг — доберите ещё '
							. rtrim(rtrim(number_format($missing, 3, '.', ''), '0'), '.')
							. ' кг. Задача остаётся в работе.',
			]);
		}

		$comment = 'Задача №' . $task->number
				. ' (' . ($task->material->name ?? '') . ')';

		// Выходная продукция оприходуется с форматом и идентификатором задачи.
		$outputFormat = $task->outputMaterials()->first()?->pivot->format;

		$outputIdentifier = MaterialRoll::composeIdentifier(
				$task->material?->code,
				$task->material?->grammage,
				$task->material?->thickness,
				$outputFormat
		);

		DB::transaction(function () use ($validated, $task, $comment, $outputFormat, $outputIdentifier) {
			// Списание входного сырья по остаткам рулонов
			foreach ($validated['inputs'] ?? [] as $inputData) {
				/** @var ProductionTaskInput $input */
				$input = ProductionTaskInput::query()
					->where('task_id', $task->id)
					->find($inputData['id'] ?? null);

				if ($input === null || $input->roll_id === null) {
					continue;
				}

				$roll = MaterialRoll::query()
					->where('id', $input->roll_id)
					->lockForUpdate()
					->firstOrFail();

				// Остаток указан прямо, иначе — «вес рулона − расход».
				$remaining = ($inputData['remaining'] ?? null) !== null
					? (float) $inputData['remaining']
					: round((float) $roll->weight - (float) ($inputData['used'] ?? 0), 3);

				$used = round((float) $roll->weight - $remaining, 3);

				if ($used > 0) {
					MaterialIssue::create([
							'material_id' => $roll->material_id,
							'roll_id' => $roll->id,
							'weight' => $used,
							'comment' => $comment,
							'user_id' => auth()->id(),
					]);
				}

				$roll->update(['weight' => $remaining]);

				$input->update(['actual_weight' => $used]);
			}

			// Оприходование выходной продукции новыми рулонами;
			// номер задаётся на странице задачи, по умолчанию «номерЗадачи/порядковый».
			$receipt = MaterialReceipt::create([
					'comment' => $comment,
					'user_id' => auth()->id(),
			]);

			foreach ($validated['outputs'] as $outputData) {
				$roll = MaterialRoll::create([
						'material_id' => $task->material_id,
						'roll_number' => $outputData['roll_number'],
						'weight' => $outputData['actual_weight'],
						'format' => $outputFormat,
						'identifier' => $outputIdentifier,
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

		return redirect()
				->route('tasks.show', $task)
				->with('success', 'Задача завершена: сырьё списано, продукция оприходована.');
	}

		/**
		 * Справочники для формы задачи: линии, шаблоны и материалы.
		 */
		private function formOptions(): array
		{
			$operations = ProductionOperation::query()
					->where('is_active', true)
					->with('productionLines')
					->orderBy('id')
					->get();

			$lines = ProductionLine::query()
					->with([
						'inputMaterials.rolls:id,material_id,format,identifier',
						'outputMaterials.rolls:id,material_id,format,identifier',
					])
					->orderBy('id')
					->get();

			// Данные шаблонов для формы: строка на материал+формат.
			$formatEntry = static fn (Material $material) => [
					'id' => (string) $material->id,
					'name' => $material->name,
					'label' => trim($material->name
							. ($material->grammage !== null ? ' | ' . rtrim(rtrim(number_format((float) $material->grammage, 2, '.', ''), '0'), '.') . ' гр' : '')
							. ($material->thickness !== null ? ' | ' . $material->thickness . ' мкм' : '')
							. ($material->pivot->format !== null ? ' | ' . $material->pivot->format : '')),
					'identifier' => $material->identifierForFormat($material->pivot->format) ?? '',
					'format' => $material->pivot->format !== null ? (string) $material->pivot->format : '',
			];

			// Данные шаблонов для формы: JS заполняет таблицы материалов при выборе шаблона.
			$templates = $lines->mapWithKeys(static fn (ProductionLine $line) => [
					(string) $line->id => [
							'operationId' => (string) $line->production_operation_id,
							'inputs' => $line->inputMaterials->map($formatEntry)->values()->all(),
							'outputs' => $line->outputMaterials->map($formatEntry)->values()->all(),
					],
			])->all();

			// Разрешённые материалы по операциям — для фильтрации входов в форме.
			$allowedMaterials = DB::table('material_production_operation')
					->get()
					->groupBy('production_operation_id')
					->map(static fn ($rows) => $rows->pluck('material_id')->map(static fn ($id) => (string) $id)->all());

			return [
					'operations' => $operations,
					'templates' => $templates,
					'allowedMaterials' => $allowedMaterials,
					'nextNumber' => (int) ProductionTask::query()
						->whereYear('created_at', now()->year)
						->max('number') + 1,
					'allMaterials' => Material::query()
						->where('is_active', true)
						->with('rolls:id,material_id,format,identifier')
						->orderBy('name')
						->get(),
					'products' => Material::query()
						->where('material_type', 'product')
						->where('is_active', true)
						->with('rolls:id,material_id,format,identifier')
						->orderBy('name')
						->get(),
					'operators' => User::query()->orderBy('name')->get(['id', 'name']),
		];
		}

		/**
		 * Правила проверки задачи (создание и редактирование ожидающей задачи).
		 */
		private function taskRules(Request $request, ?ProductionTask $task = null): array
		{
			$data = $request->all();

			// Номер задачи: по умолчанию следующий за максимальным в текущем году.
			if (($data['number'] ?? null) === null || $data['number'] === '') {
				$data['number'] = (int) ProductionTask::query()
					->whereYear('created_at', now()->year)
					->max('number') + 1;
			}

			// Материал выбирается вместе с форматом:
			// materials[] и materials_formats[] идут парами по индексу строки.
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

			return [
					'number' => [
							'required', 'integer', 'min:1',
							// Номер уникален внутри года и начинается с 1 каждый год
							static function ($attribute, $value, $fail) use ($task) {
								$exists = ProductionTask::query()
									->whereYear('created_at', now()->year)
									->where('number', (int) $value)
									->when($task !== null, static fn ($query) => $query->where('id', '!=', $task->id))
									->exists();

								if ($exists) {
									$fail('Задача с таким номером в этом году уже существует.');
								}
							},
					],
					'production_line_id' => ['nullable', 'integer', 'exists:production_lines,id'],
					'materials_pairs' => ['nullable', 'array'],
					'materials_pairs.*.material_id' => ['integer', 'exists:materials,id'],
					'materials_pairs.*.format' => ['nullable', 'integer', 'min:0', 'max:65535'],
					'output_materials_pairs' => ['nullable', 'array'],
					'output_materials_pairs.*.material_id' => ['integer', 'exists:materials,id'],
					'output_materials_pairs.*.format' => ['nullable', 'integer', 'min:0', 'max:65535'],
					'quantity' => ['required', 'numeric', 'min:0.001'],
					'operator_id' => ['nullable', 'integer', 'exists:users,id'],
					'comment' => ['nullable', 'string'],
			];
		}

		/**
		 * Выходной материал задачи — первый из выбранных.
		 */
		private function outputMaterialId(array $validated): int
		{
			$outputPair = collect($validated['output_materials_pairs'] ?? [])->first();

			if ($outputPair === null) {
				throw ValidationException::withMessages([
						'output_materials' => 'Укажите выходной материал: выберите шаблон производства или задайте материалы вручную.',
				]);
			}

			return (int) $outputPair['material_id'];
		}

		/**
		 * Заменяет материалы задачи одного направления (вход/выход).
		 * Материал всегда входит с конкретным форматом.
		 */
		private function syncTaskMaterials(ProductionTask $task, string $direction, array $pairs): void
		{
			$rows = collect($pairs)
				->unique(static fn (array $pair) => $pair['material_id'] . '|' . ($pair['format'] ?? ''))
				->map(static fn (array $pair) => [
						'task_id' => $task->id,
						'material_id' => $pair['material_id'],
						'direction' => $direction,
						'format' => $pair['format'] ?? null,
				])
				->values()
				->all();

			DB::transaction(static function () use ($task, $direction, $rows) {
				DB::table('production_task_material')
					->where('task_id', $task->id)
					->where('direction', $direction)
					->delete();

				if ($rows !== []) {
					DB::table('production_task_material')->insert($rows);
				}
			});
		}

		/**
		 * Рулон берётся того же формата, что и материал задачи.
		 *
		 * @param \Illuminate\Support\Collection $materials Материалы задачи (с pivot->format).
		 * @param \Illuminate\Support\Collection|null $excludeRollIds Рулон, уже взятые задачей.
		 */
		private function rollsForMaterials(
				\Illuminate\Support\Collection $materials,
				?\Illuminate\Support\Collection $excludeRollIds = null
		): array {
			$result = [];

			foreach ($materials as $material) {
				$format = $material->pivot->format ?? null;

				$result[$material->id] = MaterialRoll::query()
					->where('material_id', $material->id)
					->when($format !== null, static fn ($query) => $query->where('format', $format))
					->when($excludeRollIds !== null && $excludeRollIds->isNotEmpty(), static fn ($query) => $query->whereNotIn('id', $excludeRollIds))
					->where('weight', '>', 0)
					->orderBy('roll_number')
					->get(['id', 'roll_number', 'weight', 'format']);
			}

			return $result;
		}
	}
