<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialAdjustment;
	use App\Models\MaterialRoll;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\View\View;

	class MaterialAdjustmentController extends Controller
	{
		/**
		 * Список ордеров корректировки.
		 */
		public function index(Request $request): View
		{
			$dateFrom = $request->input('date_from');
			$dateTo = $request->input('date_to');

			$adjustments = MaterialAdjustment::with([
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

			return view('material-adjustments.index', compact(
					'adjustments',
					'dateFrom',
					'dateTo'
			));
		}

		/**
		 * Просмотр ордера корректировки.
		 */
		public function show(MaterialAdjustment $adjustment): View
		{
			$adjustment->load([
					'material',
					'roll',
					'user',
			]);

			if (request()->ajax()) {
				return view('material-adjustments._content', [
						'adjustment' => $adjustment,
				]);
			}

			return view('material-adjustments.show', [
					'adjustment' => $adjustment,
			]);
		}

		/**
		 * Форма нового ордера корректировки.
		 */
		public function create(): View
		{
			$materials = Material::query()
				->orderBy('name')
				->get();

			return view('material-adjustments.create', compact('materials'));
		}

		/**
		 * Сохраняет корректировку веса рулона.
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

							// Корректировка со знаком: плюс или минус
							'adjustment' => [
									'required',
									'numeric',
									'not_in:0',
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

							'adjustment.required' => 'Укажите корректировку.',
							'adjustment.numeric' => 'Корректировка должна быть числом.',
							'adjustment.not_in' => 'Корректировка не может быть нулевой.',

							'comment.string' => 'Комментарий должен быть текстом.',
					]
			);

			try {
				DB::transaction(function () use ($validated) {
					// Блокируем рулон, чтобы корректировка не пересеклась
					// со списанием или приходом
					$roll = MaterialRoll::query()
							->lockForUpdate()
							->findOrFail($validated['roll_id']);

					if ((int) $roll->material_id !== (int) $validated['material_id']) {
						throw new \Exception(
								'Выбранный рулон не относится к выбранному материалу.'
						);
					}

					$weightBefore = round((float) $roll->weight, 3);
					$adjustment = round((float) $validated['adjustment'], 3);
					$weightAfter = round($weightBefore + $adjustment, 3);

					if ($weightAfter < 0) {
						throw new \Exception(
								'Конечный вес не может быть отрицательным. Текущий вес: '
								. rtrim(rtrim(number_format($weightBefore, 3, '.', ''), '0'), '.')
								. ' кг'
						);
					}

					MaterialAdjustment::create([
							'material_id' => $roll->material_id,
							'roll_id' => $roll->id,
							'weight_before' => $weightBefore,
							'adjustment' => $adjustment,
							'weight_after' => $weightAfter,
							'comment' => $validated['comment'] ?? null,
							'user_id' => auth()->id(),
					]);

					$roll->update([
							'weight' => $weightAfter,
					]);
				});

				return redirect()
						->route('material-adjustments.create')
						->with('success', 'Вес рулона скорректирован.');
			} catch (\Exception $e) {
				return back()
						->withInput()
						->withErrors([
								'adjustment' => $e->getMessage(),
						]);
			}
		}
	}
