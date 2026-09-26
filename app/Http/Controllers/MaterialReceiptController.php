<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialReceipt;
	use App\Models\MaterialReceiptItem;
	use App\Models\MaterialRoll;
	use Illuminate\Database\UniqueConstraintViolationException;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Http\Request;
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
			->with('rolls:id,material_id,format')
			->orderBy('name')
			->get();

		return view('material-receipts.create', compact('materials'));
	}

		/**
		 * Сохраняет приходный ордер с одним или несколькими рулонами.
		 */
		public function store(Request $request): RedirectResponse
		{
			$mode = $request->input('mode') === 'total_weight'
					? 'total_weight'
					: 'rolls';

			$validated = $request->validate(
					[
							'material_id' => [
									'required',
									'integer',
									'exists:materials,id',
							],

							'format' => [
									// В режиме общего веса формат обязателен: он делит
									// рулоны «Общий вес» между форматами материала
									$mode === 'total_weight' ? 'required' : 'nullable',
									'integer',
									'min:0',
									'max:65535',
							],

							'rolls' => [
									'required',
									'array',
									'min:1',
							],

						'rolls.*.roll_number' => [
								// В режиме общего веса номер не вводится —
								// приход уходит в рулон «Общий вес»
								$mode === 'total_weight' ? 'nullable' : 'required',
								'string',
								'max:50',
								// В режиме общего веса номер фиксированный, повтор разрешён.
								// Для обычных номеров жёсткий индекс снят (номера
								// «задача/порядковый» повторяются каждый год), поэтому
								// уникальность проверяется здесь.
								static function ($attribute, $value, $fail) use ($request, $mode) {
										if ($mode === 'total_weight') {
												return;
										}

										$materialId = (int) $request->input('material_id');

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
							'material_id.required' => 'Укажите материал.',
							'material_id.integer' => 'Некорректный материал.',
							'material_id.exists' => 'Выбранный материал не существует.',

							'format.required' => 'Укажите формат.',
							'format.integer' => 'Формат должен быть целым числом.',
							'format.min' => 'Формат должен быть не меньше 0.',
							'format.max' => 'Формат не должен превышать 65535.',

							'rolls.required' => 'Добавьте хотя бы один рулон.',
							'rolls.array' => 'Некорректный список рулонов.',
							'rolls.min' => 'Добавьте хотя бы один рулон.',

							'rolls.*.roll_number.required' => 'Укажите номер рулона.',
							'rolls.*.roll_number.string' => 'Номер рулона должен быть строкой.',
							'rolls.*.roll_number.max' => 'Номер рулона не должен превышать 50 символов.',

							'rolls.*.weight.required' => 'Укажите вес рулона.',
							'rolls.*.weight.numeric' => 'Вес рулона должен быть числом.',
							'rolls.*.weight.gt' => 'Вес рулона должен быть больше 0.',

							'comment.string' => 'Комментарий должен быть текстом.',
					]
			);

			$material = Material::findOrFail($validated['material_id']);

			/*
			 * Формат выбирается отдельным селектом и сохраняется
			 * на каждом рулоне вместе с вычисленным идентификатором.
			 */
			$format = $validated['format'] ?? null;

			$identifier = MaterialRoll::composeIdentifier(
					$material->code,
					$material->grammage,
					$material->thickness,
					$format
			);

			try {
				DB::transaction(function () use ($validated, $material, $format, $identifier, $mode) {
					// Формат закрепляется за материалом: остаётся доступным,
					// когда рулоны этого формата закончатся
					if ($format !== null) {
						$material->attachFormat($format);
					}

					$receipt = MaterialReceipt::create([
							'comment' => $validated['comment'] ?? null,
							'user_id' => auth()->id(), // Временно, пока нет авторизации
					]);

					// Режим общего веса: весь вес уходит в один рулон
					// «Общий вес» этого материала и формата, вес суммируется.
					if ($mode === 'total_weight') {
						$totalWeight = round(
								collect($validated['rolls'])->sum(static fn (array $roll) => (float) $roll['weight']),
								3
						);

						$roll = MaterialRoll::query()
							->where('material_id', $material->id)
							->where('roll_number', self::TOTAL_WEIGHT_ROLL_NUMBER)
							->where('format', $format)
							->lockForUpdate()
							->first();

						if ($roll === null) {
							$roll = MaterialRoll::create([
									'material_id' => $material->id,
									'roll_number' => self::TOTAL_WEIGHT_ROLL_NUMBER,
									'weight' => $totalWeight,
									'format' => $format,
									'identifier' => $identifier,
							]);
						} else {
							$roll->update([
									'weight' => round((float) $roll->weight + $totalWeight, 3),
							]);
						}

						MaterialReceiptItem::create([
								'material_receipt_id' => $receipt->id,
								'material_id' => $material->id,
								'roll_id' => $roll->id,
								'weight' => $totalWeight,
						]);

						return;
					}

					foreach ($validated['rolls'] as $rollData) {
						$roll = MaterialRoll::create([
								'material_id' => $material->id,
								'roll_number' => $rollData['roll_number'],
								'weight' => $rollData['weight'],
								'format' => $format,
								'identifier' => $identifier,
						]);

						MaterialReceiptItem::create([
								'material_receipt_id' => $receipt->id,
								'material_id' => $material->id,
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