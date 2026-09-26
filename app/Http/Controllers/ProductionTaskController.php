<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Http\Controllers\MaterialReceiptController;
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
			// Отменённую задачу не редактируют; завершённую с недобором
			// можно поправить по весу/комментарию
			abort_if($task->status === 'cancelled', 422, 'Отменённую задачу нельзя редактировать.');

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
			// Отменённую задачу не редактируют; у завершённой с недобором
			// правки веса плана делают её «выполненной» (зелёной)
			abort_if($task->status === 'cancelled', 422, 'Отменённую задачу нельзя редактировать.');

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

				$availableRolls = $this->rollsForMaterials($task->inputMaterials, $takenRollIds, $task->id);

				// Задачи, запущенные до автосохранения продукции: добавляем
				// первую строку, чтобы рулоны было куда вносить.
				if ($task->outputs->isEmpty()) {
					ProductionTaskOutput::create([
							'task_id' => $task->id,
							'material_id' => $task->material_id,
							'roll_number' => $task->number . '/1',
					]);

					$task->load('outputs');
				}
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

		DB::transaction(function () use ($task) {
			$task->update([
					'status' => 'in_progress',
					'started_at' => now(),
					'operator_id' => $task->operator_id ?? auth()->id(),
			]);

			// Первая строка продукции создаётся сразу: произведённые рулоны
			// ведутся на странице задачи с автосохранением, как и расход.
			ProductionTaskOutput::create([
					'task_id' => $task->id,
					'material_id' => $task->material_id,
					'roll_number' => $task->number . '/1',
			]);
		});

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
					// Рулон нельзя взять повторно; берётся только с расходом,
					// не превышающим доступный вес (весь минус резерв других задач)
					function ($attribute, $value, $fail) use ($request, $task) {
						if (empty($value)) {
							return;
						}

						if ($task->inputs()->where('roll_id', (int) $value)->exists()) {
							$fail('Этот рулон уже взят задачей.');

							return;
						}

						preg_match('/^inputs\.(\d+)\./', $attribute, $matches);

						$used = (float) $request->input(
							'inputs.' . ($matches[1] ?? '0') . '.used',
							0
						);

						$available = $this->availableRollWeight((int) $value, $task->id);

						if ($used > $available + 0.0005) {
							$fail('Недостаточно доступного веса: свободно '
									. rtrim(rtrim(number_format($available, 3, '.', ''), '0'), '.')
									. ' кг.');
						}
					},
				],

					// Расход при взятии обязателен: он и становится
					// резервом задачи на этом рулоне
					'inputs.*.used' => [
							'required',
							'numeric',
							'gt:0',
					],
			], [
					'inputs.*.used.required' => 'Укажите предполагаемый расход.',
					'inputs.*.used.numeric' => 'Расход должен быть числом.',
					'inputs.*.used.gt' => 'Расход должен быть больше 0.',
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
						// Предполагаемый расход сразу становится резервом
						'actual_weight' => round((float) $input['used'], 3),
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

		// Правка расхода не должна забирать вес, уже
		// зарезервированный другими задачами в работе
		if ($input->roll_id !== null) {
			$available = $this->availableRollWeight((int) $input->roll_id, $task->id);

			if ($used > $available + 0.0005) {
				$availableLabel = rtrim(rtrim(number_format($available, 3, '.', ''), '0'), '.');

				return response()->json([
						'message' => 'Недостаточно доступного веса: свободно ' . $availableLabel . ' кг.',
						'errors' => ['used' => ['Недостаточно доступного веса: свободно ' . $availableLabel . ' кг.']],
				], 422);
			}
		}

		$input->update(['actual_weight' => $used]);

		return response()->json([
				'success' => true,
				'used' => $used,
				'remaining' => round($rollWeight - $used, 3),
		]);
	}

	/**
	 * Новая строка произведённого рулона: создаётся сразу по AJAX,
	 * номер по умолчанию — «номерЗадачи/порядковый».
	 */
	public function storeOutput(Request $request, ProductionTask $task)
	{
		abort_unless($task->status === 'in_progress', 422, 'Продукция добавляется только у задачи в работе.');

		$output = ProductionTaskOutput::create([
				'task_id' => $task->id,
				'material_id' => $task->material_id,
				'roll_number' => $task->number . '/' . ($task->outputs()->count() + 1),
		]);

		if ($request->wantsJson()) {
			return response()->json(['success' => true, 'id' => $output->id]);
		}

		return redirect()->route('tasks.show', $task);
	}

	/**
	 * Произведённый рулон сохраняется сразу, чтобы правки не терялись
	 * при обновлении страницы или отказе завершения.
	 */
	public function updateOutput(Request $request, ProductionTask $task, ProductionTaskOutput $output)
	{
		abort_unless($task->status === 'in_progress', 422, 'Продукция сохраняется только у задачи в работе.');
		abort_unless($output->task_id === $task->id, 404);

		$validated = $request->validate([
				'roll_number' => ['required', 'string', 'max:50'],
				// Пустой вес — строка ещё не заполнена, в прогресс не входит
				'actual_weight' => ['nullable', 'numeric', 'min:0'],
		]);

		$output->update([
				'roll_number' => $validated['roll_number'],
				'actual_weight' => $validated['actual_weight'] === null
						? null
						: round((float) $validated['actual_weight'], 3),
		]);

		$made = round((float) $task->outputs()->whereNotNull('actual_weight')->sum('actual_weight'), 3);
		$left = max(0.0, round((float) $task->quantity - $made, 3));

		return response()->json([
				'success' => true,
				'made' => $made,
				'left' => $left,
		]);
	}

	public function destroyOutput(Request $request, ProductionTask $task, ProductionTaskOutput $output)
	{
		abort_unless($task->status === 'in_progress', 422, 'Продукция убирается только у задачи в работе.');
		abort_unless($output->task_id === $task->id, 404);

		if ($task->outputs()->count() <= 1) {
			return response()->json([
					'message' => 'У задачи должна остаться хотя бы одна строка продукции.',
			], 422);
		}

		$output->delete();

		return response()->json(['success' => true]);
	}

	/**
	 * Завершение задачи прямо со страницы задачи: расход и произведённые
	 * рулоны уже сохранены по AJAX — списываем сырьё и оприходуем продукцию.
	 */
	public function complete(Request $request, ProductionTask $task)
	{
		abort_if($task->status !== 'in_progress', 422, 'Задача не в работе.');

		$task->load(['outputs', 'inputs.roll']);

		// Новая продукция: без веса или уже оприходованная при прежнем
		// завершении (переоткрытая задача) в списание не входит
		$outputs = $task->outputs->filter(static fn ($output) => (float) ($output->actual_weight ?? 0) > 0
				&& $output->roll_id === null);

		// Недобор считается по всей продукции задачи, включая
		// оприходованную при предыдущем завершении
		$totalProduced = round((float) $task->outputs
				->filter(static fn ($output) => (float) ($output->actual_weight ?? 0) > 0)
				->sum('actual_weight'), 3);

		if ($outputs->isEmpty() && $totalProduced >= (float) $task->quantity) {
			// Переоткрытой задаче с уже оприходованной продукцией
			// достаточно просто закрыться без нового списания
			$task->update([
					'status' => 'done',
					'completed_at' => now(),
			]);

			return redirect()
					->route('tasks.show', $task)
					->with('success', 'Задача завершена.');
		}

		if ($outputs->isEmpty()) {
			throw ValidationException::withMessages([
					'outputs' => 'Укажите хотя бы один произведённый рулон с весом.',
			]);
		}

		$produced = round((float) $outputs->sum('actual_weight'), 3);

		// Недобор — завершение вне плана: разрешено всем,
		// статус такой задачи правится правом tasks,status
		$isShort = $totalProduced < (float) $task->quantity;

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

			DB::transaction(function () use ($outputs, $task, $comment, $outputFormat, $outputIdentifier) {
				// Формат продукции закрепляется за материалом
				if ($outputFormat !== null) {
					$task->material?->attachFormat($outputFormat);
				}

				// Списание входного сырья по сохранённому расходу рулонов;
			// уже списанные при прежнем завершении не трогаются
			foreach ($task->inputs as $input) {
				if ($input->roll_id === null || $input->issued_at !== null) {
					continue;
				}

				$roll = MaterialRoll::query()
					->where('id', $input->roll_id)
					->lockForUpdate()
					->firstOrFail();

				$used = round((float) ($input->actual_weight ?? 0), 3);
				$remaining = round((float) $roll->weight - $used, 3);

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

				$input->update(['issued_at' => now()]);
			}

			// Оприходование выходной продукции новыми рулонами;
			// номера введены на странице задачи, по умолчанию «номерЗадачи/порядковый».
			$receipt = MaterialReceipt::create([
					'comment' => $comment,
					'user_id' => auth()->id(),
			]);

			foreach ($outputs as $output) {
				$roll = MaterialRoll::create([
						'material_id' => $task->material_id,
						'roll_number' => $output->roll_number !== ''
								? $output->roll_number
								: $task->number . '/' . $output->id,
						'weight' => $output->actual_weight,
						'format' => $outputFormat,
						'identifier' => $outputIdentifier,
				]);

				MaterialReceiptItem::create([
						'material_receipt_id' => $receipt->id,
						'material_id' => $task->material_id,
						'roll_id' => $roll->id,
						'weight' => $output->actual_weight,
				]);

				$output->update(['roll_id' => $roll->id]);
			}

			$task->update([
					'status' => 'done',
					'completed_at' => now(),
			]);
		});

		$message = $isShort
				? 'Задача завершена с недобором: сырьё списано, продукция оприходована.'
				: 'Задача завершена: сырьё списано, продукция оприходована.';

		return redirect()
				->route('tasks.show', $task)
				->with('success', $message);
	}

		/**
		 * Отмена задачи: разрешена только пока задача ждёт старта.
		 */
		public function cancel(Request $request, ProductionTask $task)
		{
			abort_if($task->status !== 'pending', 422, 'Отменить можно только задачу в статусе «Ожидает».');

			$task->update(['status' => 'cancelled']);

			return redirect()
				->route('tasks.show', $task)
				->with('success', 'Задача отменена.');
		}

		/**
		 * Смена статуса задачи по праву tasks,status: «Выполнена»,
		 * «Ожидает», «В работе» — например, завершённую с недобором
		 * задачу можно вернуть в работу. Оператор без этого права
		 * может только продолжить завершённую с недобором задачу,
		 * чтобы доделать недостающий вес.
		 */
		public function updateStatus(Request $request, ProductionTask $task)
		{
			$validated = $request->validate([
					'status' => [
							'required',
							'in:pending,in_progress,done',
					],
			], [
					'status.required' => 'Укажите статус.',
					'status.in' => 'Некорректный статус.',
			]);

			$target = $validated['status'];
			$user = $request->user();

			if (!$user->may('tasks', 'status')) {
				// Без права — только продолжить завершённую с недобором задачу
				$allowed = $target === 'in_progress'
						&& $task->status === 'done'
						&& $task->isShort()
						&& $user->may('tasks', 'execute');

				abort_unless($allowed, 403, 'Недостаточно прав для изменения статуса задачи.');
			}

			if ($target === $task->status) {
				return redirect()
						->route('tasks.show', $task)
						->with('success', 'Статус задачи не изменился.');
			}

			DB::transaction(function () use ($task, $target) {
				$task->update([
						'status' => $target,
						// Возврат в работу сбрасывает дату завершения
						'completed_at' => $target === 'done' ? now() : null,
						'started_at' => $target === 'pending' ? null : ($task->started_at ?? now()),
				]);

				// Возвращённой в работу задаче нужна строка продукции,
				// если её не было или она списана при прежнем завершении
				if ($target === 'in_progress' && $task->outputs()->count() === 0) {
					ProductionTaskOutput::create([
							'task_id' => $task->id,
							'material_id' => $task->material_id,
							'roll_number' => $task->number . '/1',
					]);
				}
			});

			return redirect()
					->route('tasks.show', $task)
					->with('success', 'Статус задачи изменён.');
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
					'operators' => User::query()
						->orderBy('login')
						->get(['id', 'login', 'full_name', 'display_name']),
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
				->map(static function (array $pair) {
					// Формат закрепляется за материалом при использовании в задаче
					if (($pair['format'] ?? null) !== null) {
						Material::query()->find($pair['material_id'])?->attachFormat($pair['format']);
					}

					return [
							'task_id' => $task->id,
							'material_id' => $pair['material_id'],
							'direction' => $direction,
							'format' => $pair['format'] ?? null,
					];
				})
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
		 * Рулон делится по весу: доступно «вес минус резерв других
		 * задач в работе». Полностью занятые идут вниз списка
		 * отключёнными с номером задачи.
		 *
		 * @param \Illuminate\Support\Collection $materials Материалы задачи (с pivot->format).
		 * @param \Illuminate\Support\Collection|null $excludeRollIds Рулон, уже взятые этой задачей.
		 * @param int|null $currentTaskId Задача, чей резерв не считается занятым.
		 */
		private function rollsForMaterials(
				\Illuminate\Support\Collection $materials,
				?\Illuminate\Support\Collection $excludeRollIds = null,
				?int $currentTaskId = null
		): array {
			$reservedByRoll = ProductionTaskInput::query()
				->whereNotNull('actual_weight')
				->whereHas('task', static fn ($query) => $query->where('status', 'in_progress'))
				->when($currentTaskId !== null, static fn ($query) => $query->where('task_id', '!=', $currentTaskId))
				->when($excludeRollIds !== null && $excludeRollIds->isNotEmpty(), static fn ($query) => $query->whereNotIn('roll_id', $excludeRollIds))
				->with('task:id,number,status')
				->get(['roll_id', 'task_id', 'actual_weight'])
				->groupBy('roll_id');

			$result = [];

			foreach ($materials as $material) {
				$format = $material->pivot->format ?? null;

				$rolls = MaterialRoll::query()
					->where('material_id', $material->id)
					->when($format !== null, static fn ($query) => $query->where('format', $format))
					->when($excludeRollIds !== null && $excludeRollIds->isNotEmpty(), static fn ($query) => $query->whereNotIn('id', $excludeRollIds))
					->where('weight', '>', 0)
					->orderBy('roll_number')
					->get(['id', 'roll_number', 'weight', 'format']);

				$result[$material->id] = $rolls
					->map(static function (MaterialRoll $roll) use ($reservedByRoll) {
						$reserved = round(
								(float) $reservedByRoll->get($roll->id, collect())->sum('actual_weight'),
								3
						);

						$roll->available = max(0.0, round((float) $roll->weight - $reserved, 3));

						// Рулон «Общий вес»: оператор сам указывает вес,
						// поэтому он не подставляется автоматически
						$roll->is_shared = $roll->roll_number === MaterialReceiptController::TOTAL_WEIGHT_ROLL_NUMBER;

						$holders = $reservedByRoll->get($roll->id, collect())
							->map(static fn ($input) => $input->task?->number)
							->filter()
							->unique()
							->implode(', ');

						$roll->taken_by = $holders !== '' ? $holders : null;

						return $roll;
					})
					// Полностью занятые — вниз списка
					->sortByDesc(static fn (MaterialRoll $roll) => $roll->available > 0)
					->values();
			}

			return $result;
		}

		/**
		 * Доступный для новой задачи вес рулона:
		 * весь вес минус резерв других задач в работе.
		 */
		private function availableRollWeight(int $rollId, ?int $excludeTaskId = null): float
		{
			$roll = MaterialRoll::query()->find($rollId);

			if ($roll === null) {
				return 0.0;
			}

			$reserved = round(
					(float) ProductionTaskInput::query()
						->where('roll_id', $rollId)
						->whereNotNull('actual_weight')
						->whereHas('task', static fn ($query) => $query->where('status', 'in_progress'))
						->when($excludeTaskId !== null, static fn ($query) => $query->where('task_id', '!=', $excludeTaskId))
						->sum('actual_weight'),
					3
			);

			return max(0.0, round((float) $roll->weight - $reserved, 3));
		}
	}
