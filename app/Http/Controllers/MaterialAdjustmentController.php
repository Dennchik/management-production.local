<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialAdjustment;
	use App\Models\MaterialRoll;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Str;
	use Illuminate\View\View;

	class MaterialAdjustmentController extends Controller
	{
		/**
		 * Список ордеров корректировки: строки одного сабмита
		 * с общим batch_id показываются как один документ.
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
					->orderBy('batch_id')
					->orderByDesc('id')
					->get();

			return view('material-adjustments.index', compact(
					'adjustments',
					'dateFrom',
					'dateTo'
			));
		}

		/**
		 * Просмотр ордера корректировки: все строки ордера.
		 */
		public function show(MaterialAdjustment $adjustment): View
		{
			$adjustment->load([
					'material',
					'roll',
					'user',
			]);

			$rows = $adjustment->orderRows();

			$rows->load([
					'material',
					'roll',
					'user',
			]);

			return view('material-adjustments.show', [
					'adjustment' => $adjustment,
					'rows' => $rows,
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
		 * Сохраняет ордер корректировки с одной или несколькими позициями.
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
									'distinct',
									'exists:material_rolls,id',
							],

							'rows.*.adjustment' => [
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
							'rows.required' => 'Добавьте хотя бы одну позицию.',
							'rows.min' => 'Добавьте хотя бы одну позицию.',

							'rows.*.material_id.required' => 'Укажите материал в каждой позиции.',
							'rows.*.material_id.exists' => 'Выбранный материал не существует.',

							'rows.*.roll_id.required' => 'Укажите рулон в каждой позиции.',
							'rows.*.roll_id.distinct' => 'Один и тот же рулон указан в нескольких позициях.',
							'rows.*.roll_id.exists' => 'Выбранный рулон не существует.',

							'rows.*.adjustment.required' => 'Укажите отклонение в каждой позиции.',
							'rows.*.adjustment.not_in' => 'Отклонение не может быть нулевым.',

							'comment.string' => 'Комментарий должен быть текстом.',
					]
			);

			$batchId = (string) Str::uuid();

			try {
				DB::transaction(function () use ($validated, $batchId) {
					foreach ($validated['rows'] as $row) {
						/*
						 * Блокируем рулон на время операции: два одновременных
						 * ордера не должны списать больше, чем фактически есть.
						 */
						$roll = MaterialRoll::query()
								->lockForUpdate()
								->findOrFail($row['roll_id']);

						if ((int) $roll->material_id !== (int) $row['material_id']) {
							throw new \Exception(
									'Выбранный рулон не относится к выбранному материалу.'
							);
						}

						$weightBefore = round((float) $roll->weight, 3);
						$adjustment = round((float) $row['adjustment'], 3);
						$weightAfter = round($weightBefore + $adjustment, 3);

						if ($weightAfter < 0) {
							throw new \Exception(
									'Новый остаток рулона «' . $roll->roll_number . '» не может быть отрицательным.'
							);
						}

						MaterialAdjustment::create([
								'material_id' => $roll->material_id,
								'roll_id' => $roll->id,
								'weight_before' => $weightBefore,
								'adjustment' => $adjustment,
								'weight_after' => $weightAfter,
								'batch_id' => $batchId,
								'comment' => $validated['comment'] ?? null,
								'user_id' => auth()->id(),
						]);

						$roll->update(['weight' => $weightAfter]);
					}
				});
			} catch (\Exception $e) {
				return back()
						->withInput()
						->withErrors(['rows' => $e->getMessage()]);
			}

			return redirect()
					->route('material-adjustments.create')
					->with('success', 'Ордер корректировки сохранён (позиций: ' . count($validated['rows']) . ').');
		}
	}
