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
			// 👇 Добавляем получение материалов
			$materials = Material::orderBy('name')->get();

			// 👇 Добавляем получение рулонов (для выпадающего списка)
			$rolls = MaterialRoll::with('material')
				->where('weight', '>', 0)
				->orderBy('roll_number')
				->get();

			return view('material-issues.create', compact('materials', 'rolls'));
		}

		/**
		 * Сохраняет операцию расхода сырья.
		 */
		public function store(Request $request): RedirectResponse
		{
			$validated = $request->validate(
				[
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
					$roll = MaterialRoll::query()
						->lockForUpdate()
						->findOrFail($validated['roll_id']);

					$currentWeight = (float)$roll->weight;
					$issueWeight = (float)$validated['weight'];

					//* Проверка на списание недостающего материала
					if ($issueWeight > $currentWeight) {
						throw new \Exception(
							'Недостаточно материала на рулоне. Доступно: '
							. number_format($currentWeight, 3, '.', '')
							. ' кг'
						);
					}

					MaterialIssue::create([
						'material_id' => $roll->material_id,
						'roll_id' => $roll->id,
						'weight' => $issueWeight,
						'comment' => $validated['comment'] ?? null,
						'user_id' => 1,
					]);

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
					->withErrors(['weight' => $e->getMessage()]);
			}
		}
	}