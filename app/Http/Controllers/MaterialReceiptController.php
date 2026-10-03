<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialReceipt;
	use App\Models\MaterialReceiptItem;
	use App\Models\MaterialRoll;
	use Illuminate\Database\UniqueConstraintViolationException;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Collection;
	use Illuminate\Support\Facades\DB;
	use Illuminate\View\View;

	class MaterialReceiptController extends Controller
	{
		/**
		 * Номер рулона, накапливающего приход без учёта рулонами.
		 */
		public const TOTAL_WEIGHT_ROLL_NUMBER = 'Общий вес';

		/**
		 * Список приходных ордеров.
		 */
		public function index(Request $request): View
		{
			$dateFrom = $request->input('date_from');
			$dateTo = $request->input('date_to');

			$receipts = MaterialReceipt::with([
					'items.material',
					'items.roll',
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

			return view('material-receipts.index', compact(
					'receipts',
					'dateFrom',
					'dateTo'
			));
		}

		/**
		 * Просмотр приходного ордера.
		 */
		public function show(MaterialReceipt $receipt): View
		{
			$receipt->load([
					'items.material',
					'items.roll',
					'user',
			]);

			if (request()->ajax()) {
				return view('material-receipts._content', [
						'receipt' => $receipt,
				]);
			}

			return view('material-receipts.show', [
					'receipt' => $receipt,
			]);
		}

	/**
	 * Отображает форму нового оприходования сырья.
	 */
	public function create(): View
	{
		$materials = Material::query()
			->orderBy('name')
			->get();

		return view('material-receipts.create', compact('materials'));
	}

		/**
		 * Сохраняет приходный ордер с одним или несколькими рулонами
		 * одного или разных материалов: материал выбирается в каждой
		 * строке.
		 */
		public function store(Request $request): RedirectResponse
		{
			$mode = $request->input('mode') === 'total_weight'
					? 'total_weight'
					: 'rolls';

			$validated = $request->validate(
					[
							'rolls' => [
									'required',
									'array',
									'min:1',
							],

						'rolls.*.material_id' => [
								'required',
								'integer',
								'exists:materials,id',
						],

						'rolls.*.roll_number' => [
								// В режиме общего веса номер не вводится —
								// приход уходит в рулон «Общий вес»
								$mode === 'total_weight' ? 'nullable' : 'required',
								'string',
								'max:50',
								// В режиме общего веса номер фиксированный, повтор разрешён.
								// Для обычных номеров уникальность проверяется здесь:
								// в рамках материала строки и внутри одной заявки.
								static function ($attribute, $value, $fail) use ($request, $mode) {
										if ($mode === 'total_weight') {
												return;
										}

										$value = trim((string) $value);

										$index = (int) substr($attribute, strlen('rolls.'));

										$materialId = (int) ($request->input("rolls.{$index}.material_id") ?? 0);

										// Дубль номера в другой строке этого же материала
										foreach ((array) $request->input('rolls', []) as $otherIndex => $otherRoll) {
												if ((int) $otherIndex >= $index) {
														continue;
												}

												if ((int) ($otherRoll['material_id'] ?? 0) === $materialId
														&& trim((string) ($otherRoll['roll_number'] ?? '')) === $value) {
														$fail("В ордере два рулона с номером «{$value}» для одного материала.");

														return;
												}
										}

										$exists = MaterialRoll::query()
												->where('material_id', $materialId)
												->where('roll_number', $value)
												->exists();

										if ($exists) {
												$fail("Рулон с номером «{$value}» для этого материала уже существует.");
										}
								},
						],

							'rolls.*.weight' => [
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
							'rolls.required' => 'Добавьте хотя бы один рулон.',
							'rolls.array' => 'Некорректный список рулонов.',
							'rolls.min' => 'Добавьте хотя бы один рулон.',

							'rolls.*.material_id.required' => 'Укажите материал в каждой строке.',
							'rolls.*.material_id.integer' => 'Некорректный материал.',
							'rolls.*.material_id.exists' => 'Выбранный материал не существует.',

							'rolls.*.roll_number.required' => 'Укажите номер рулона.',
							'rolls.*.roll_number.string' => 'Номер рулона должен быть строкой.',
							'rolls.*.roll_number.max' => 'Номер рулона не должен превышать 50 символов.',

							'rolls.*.weight.required' => 'Укажите вес рулона.',
							'rolls.*.weight.numeric' => 'Вес рулона должен быть числом.',
							'rolls.*.weight.gt' => 'Вес рулона должен быть больше 0.',

							'comment.string' => 'Комментарий должен быть текстом.',
					]
			);

			try {
				DB::transaction(function () use ($validated, $mode) {
					$receipt = MaterialReceipt::create([
							'comment' => $validated['comment'] ?? null,
							'user_id' => auth()->id(), // Временно, пока нет авторизации
					]);

					// Режим общего веса: вес каждой строки уходит в рулон
					// «Общий вес» её материала, вес по материалу суммируется.
					if ($mode === 'total_weight') {
							collect($validated['rolls'])
									->groupBy(static fn (array $roll) => (int) $roll['material_id'])
									->each(static function (Collection $rows, int $materialId) use ($receipt) {
											$totalWeight = round(
													$rows->sum(static fn (array $roll) => (float) $roll['weight']),
													3
											);

											$roll = MaterialRoll::query()
												->where('material_id', $materialId)
												->where('roll_number', self::TOTAL_WEIGHT_ROLL_NUMBER)
												->lockForUpdate()
												->first();

											if ($roll === null) {
													$roll = MaterialRoll::create([
															'material_id' => $materialId,
															'roll_number' => self::TOTAL_WEIGHT_ROLL_NUMBER,
															'weight' => $totalWeight,
													]);
											} else {
													$roll->update([
															'weight' => round((float) $roll->weight + $totalWeight, 3),
													]);
											}

											MaterialReceiptItem::create([
													'material_receipt_id' => $receipt->id,
													'material_id' => $materialId,
													'roll_id' => $roll->id,
													'weight' => $totalWeight,
											]);
									});

							return;
					}

					foreach ($validated['rolls'] as $rollData) {
						$roll = MaterialRoll::create([
								'material_id' => $rollData['material_id'],
								'roll_number' => $rollData['roll_number'],
								'weight' => $rollData['weight'],
						]);

						MaterialReceiptItem::create([
								'material_receipt_id' => $receipt->id,
								'material_id' => $rollData['material_id'],
								'roll_id' => $roll->id,
								'weight' => $rollData['weight'],
						]);
					}
				});
			} catch (UniqueConstraintViolationException $e) {
				return back()
						->withInput()
						->withErrors([
								'rolls' => 'Один из указанных рулонов уже существует для выбранного материала.',
						]);
			}

			return redirect()
					->route('material-receipts.create')
					->with('success', 'Материал успешно оприходован.');
		}
	}