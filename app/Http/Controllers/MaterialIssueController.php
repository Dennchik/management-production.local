<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialIssue;
	use App\Models\MaterialRoll;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\View\View;

	class MaterialIssueController extends Controller
	{
		/**
		 * Список расходных ордеров.
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

			return view('material-issues.index', compact(
					'issues',
					'dateFrom',
					'dateTo'
			));
		}

		/**
		 * Просмотр расходного ордера.
		 */
		public function show(MaterialIssue $issue): View
		{
			$issue->load([
					'material',
					'roll',
					'user',
			]);

			if (request()->ajax()) {
				return view('material-issues._content', [
						'issue' => $issue,
				]);
			}

			return view('material-issues.show', [
					'issue' => $issue,
			]);
		}

		/**
		 * Отображает форму расходного ордера.
		 */
		public function create(): View
		{
			$materials = Material::orderBy('name')->get();

			return view('material-issues.create', compact('materials'));
		}

		/**
		 * Сохраняет операцию расхода сырья.
		 */
		public function store(Request $request): RedirectResponse
		{
			$validated = $request->validate(
					[
							'material_id' => [
									'required',
									'integer',
									'exists:materials,id',
							],

							'roll_id' => [
									'required',
									'integer',
									'exists:material_rolls,id',
							],

							'weight' => [
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
							'material_id.required' => 'Укажите материал.',
							'material_id.integer' => 'Некорректный материал.',
							'material_id.exists' => 'Выбранный материал не существует.',

							'roll_id.required' => 'Укажите рулон.',
							'roll_id.integer' => 'Некорректный рулон.',
							'roll_id.exists' => 'Выбранный рулон не существует.',

							'weight.required' => 'Укажите вес.',
							'weight.numeric' => 'Вес должен быть числом.',
							'weight.gt' => 'Вес должен быть больше 0.',

							'comment.string' => 'Комментарий должен быть текстом.',
					]
			);

			try {
				DB::transaction(function () use ($validated) {
					/*
					 * Блокируем выбранный рулон на время операции,
					 * чтобы два одновременных расхода не списали
					 * больше материала, чем фактически есть.
					 */
					$roll = MaterialRoll::query()
							->lockForUpdate()
							->findOrFail($validated['roll_id']);

					/*
					 * Проверяем, что рулон относится именно
					 * к выбранному материалу.
					 */
					if ((int)$roll->material_id !== (int)$validated['material_id']) {
						throw new \Exception(
								'Выбранный рулон не относится к выбранному материалу.'
						);
					}

					$currentWeight = (float)$roll->weight;
					$issueWeight = (float)$validated['weight'];

					/*
					 * Проверяем достаточность остатка.
					 */
					if ($issueWeight > $currentWeight) {
						throw new \Exception(
								'Недостаточно материала на рулоне. Доступно: '
								. number_format($currentWeight, 3, '.', '')
								. ' кг'
						);
					}

					/*
					 * Создаём операцию расхода.
					 */
					MaterialIssue::create([
							'material_id' => $roll->material_id,
							'roll_id' => $roll->id,
							'weight' => $issueWeight,
							'comment' => $validated['comment'] ?? null,
							'user_id' => 1,
					]);

					/*
					 * Уменьшаем текущий остаток рулона.
					 */
					$roll->update([
							'weight' => $currentWeight - $issueWeight,
					]);
				});

				return redirect()
						->route('material-issues.create')
						->with('success', 'Материал успешно списан.');
			} catch (\Exception $e) {
				return back()
						->withInput()
						->withErrors([
								'weight' => $e->getMessage(),
						]);
			}
		}
	}

