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
			$validated = $request->validate(
					[
							'material_id' => [
									'required',
									'integer',
									'exists:materials,id',
							],

							'format' => [
									'nullable',
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
								'required',
								'string',
								'max:50',
								// Жёсткий индекс снят (номера «задача/порядковый» повторяются
								// каждый год), поэтому уникальность вручную введённых номеров
								// проверяется здесь.
								static function ($attribute, $value, $fail) use ($request) {
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
			 * Формат вводится при оприходовании и сохраняется
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
				DB::transaction(function () use ($validated, $material, $format, $identifier) {
					$receipt = MaterialReceipt::create([
							'comment' => $validated['comment'] ?? null,
							'user_id' => auth()->id(), // Временно, пока нет авторизации
					]);

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