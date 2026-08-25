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

	class MaterialReceiptController extends Controller
	{
		/**
		 * Список приходных ордеров.
		 */
		public function index(Request $request)
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
		public function show(MaterialReceipt $receipt)
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
		public function create()
		{
			$materials = Material::orderBy('name')->get();

			return view('material-receipts.create', compact('materials'));
		}

		/**
		 * Сохраняет приходный ордер с одним или несколькими рулонами.
		 */
		public function store(Request $request): RedirectResponse
		{
			$request->merge([
				'material_id' => $request->input('material_select'),
			]);

			$validated = $request->validate(
				[
					'material_id' => [
						'required',
						'integer',
						'exists:materials,id',
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

			try {
				DB::transaction(function () use ($validated) {
					$receipt = MaterialReceipt::create([
						'comment' => $validated['comment'] ?? null,
						'user_id' => 1, // Временно, пока нет авторизации
					]);

					foreach ($validated['rolls'] as $rollData) {
						$roll = MaterialRoll::create([
							'material_id' => $validated['material_id'],
							'roll_number' => $rollData['roll_number'],
							'weight' => $rollData['weight'],
						]);

						MaterialReceiptItem::create([
							'material_receipt_id' => $receipt->id,
							'material_id' => $validated['material_id'],
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