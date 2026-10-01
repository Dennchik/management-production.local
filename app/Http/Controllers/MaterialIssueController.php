<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialIssue;
	use App\Models\MaterialRoll;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Str;
	use Illuminate\View\View;

	class MaterialIssueController extends Controller
	{
		/**
		 * Список расходных ордеров: строки одного сабмита
		 * с общим batch_id показываются как один документ.
		 */
		public function index(Request $request): View
		{
			$dateFrom = $request->input('date_from');
			$dateTo = $request->input('date_to');

			$issues = MaterialIssue::with([
					'material',
					'roll',
					'user',
			])
					->when($dateFrom, function ($query) use ($dateFrom) {
						$query->whereDate('created_at', '>=', $dateFrom);
					})
					->when($dateTo, function ($query) use ($dateTo) {
						$query->whereDate('created_at', '<=', $dateTo);
					})
					->latest()
					->get();

			// Старые однострочные записи (без batch_id) остаются документами
			// из одной строки.
			$orders = $issues
					->groupBy(static fn (MaterialIssue $issue) => $issue->batch_id ?? 'single-' . $issue->id)
					->values();

			return view('material-issues.index', compact(
					'orders',
					'dateFrom',
					'dateTo'
			));
		}

		/**
		 * Просмотр расходного ордера: все строки ордера.
		 */
		public function show(MaterialIssue $issue): View
		{
			$issue->load([
					'material',
					'roll',
					'user',
			]);

			$rows = $issue->orderRows();

			$rows->load([
					'material',
					'roll',
					'user',
			]);

			if (request()->ajax()) {
				return view('material-issues._content', [
						'issue' => $issue,
						'rows' => $rows,
				]);
			}

			return view('material-issues.show', [
					'issue' => $issue,
					'rows' => $rows,
			]);
		}

	/**
	 * Отображает форму расходного ордера.
	 */
	public function create(): View
	{
		$materials = Material::query()
			->orderBy('name')
			->get();

		return view('material-issues.create', compact('materials'));
	}

		/**
		 * Сохраняет расходный ордер с одной или несколькими позициями:
		 * материал и рулон выбираются в каждой строке.
		 */
		public function store(Request $request): RedirectResponse
		{
			$validated = $request->validate(
					[
							'rows' => [
									'required',
									'array',
									'min:1',
							],

							'rows.*.material_id' => [
									'required',
									'integer',
									'exists:materials,id',
							],

							'rows.*.roll_id' => [
									'required',
									'integer',
									// Один рулон не может быть указан в двух
									// позициях одного ордера
									'distinct',
									'exists:material_rolls,id',
							],

							'rows.*.weight' => [
									'required',
									'numeric',
									'gt:0',
							],

							'comment' => [
									'nullable',
									'string',
							],
					],
					[
							'rows.required' => 'Добавьте хотя бы одну позицию.',
							'rows.min' => 'Добавьте хотя бы одну позицию.',

							'rows.*.material_id.required' => 'Укажите материал в каждой позиции.',
							'rows.*.material_id.integer' => 'Некорректный материал.',
							'rows.*.material_id.exists' => 'Выбранный материал не существует.',

							'rows.*.roll_id.required' => 'Укажите рулон в каждой позиции.',
							'rows.*.roll_id.integer' => 'Некорректный рулон.',
							'rows.*.roll_id.distinct' => 'Один и тот же рулон указан в нескольких позициях.',
							'rows.*.roll_id.exists' => 'Выбранный рулон не существует.',

							'rows.*.weight.required' => 'Укажите вес расхода в каждой позиции.',
							'rows.*.weight.numeric' => 'Вес должен быть числом.',
							'rows.*.weight.gt' => 'Вес должен быть больше 0.',

							'comment.string' => 'Комментарий должен быть текстом.',
					]
			);

			$batchId = (string) Str::uuid();

			try {
				DB::transaction(function () use ($validated, $batchId) {
					foreach ($validated['rows'] as $row) {
						/*
						 * Блокируем рулон на время операции, чтобы два
						 * одновременных расхода не списали больше материала,
						 * чем фактически есть.
						 */
						$roll = MaterialRoll::query()
								->lockForUpdate()
								->findOrFail($row['roll_id']);

						/*
						 * Проверяем, что рулон относится именно
						 * к выбранному материалу.
						 */
						if ((int) $roll->material_id !== (int) $row['material_id']) {
							throw new \Exception(
									'Выбранный рулон не относится к выбранному материалу.'
							);
						}

						$currentWeight = round((float) $roll->weight, 3);
						$issueWeight = round((float) $row['weight'], 3);

						/*
						 * Проверяем достаточность остатка.
						 */
						if ($issueWeight > $currentWeight) {
							throw new \Exception(
									'Недостаточно материала на рулоне «' . $roll->roll_number . '». Доступно: '
									. number_format($currentWeight, 3, '.', '')
									. ' кг'
							);
						}

						/*
						 * Создаём позицию расходного ордера.
						 */
						MaterialIssue::create([
								'material_id' => $roll->material_id,
								'roll_id' => $roll->id,
								'weight' => $issueWeight,
								'batch_id' => $batchId,
								'comment' => $validated['comment'] ?? null,
								'user_id' => auth()->id(),
						]);

						/*
						 * Уменьшаем текущий остаток рулона.
						 */
						$roll->update([
								'weight' => round($currentWeight - $issueWeight, 3),
						]);
					}
				});

				return redirect()
						->route('material-issues.create')
						->with('success', 'Материалы успешно списаны (позиций: ' . count($validated['rows']) . ').');
			} catch (\Exception $e) {
				return back()
						->withInput()
						->withErrors([
								'rows' => $e->getMessage(),
						]);
			}
		}
	}
