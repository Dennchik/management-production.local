<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\MaterialReceipt;
	use App\Models\MaterialRoll;
	use Illuminate\Database\UniqueConstraintViolationException;
	use Illuminate\Http\RedirectResponse;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;

	class MaterialReceiptController extends Controller
	{
		/**
		 * Отображает форму нового оприходования сырья.
		 *
		 * Передаём в форму список типов материалов,
		 * чтобы кладовщик мог выбрать нужный материал.
		 */
		public function create()
		{
			$materials = Material::orderBy('name')->get();

			return view('material-receipts.create', compact('materials'));
		}

		/**
		 * Сохраняет операцию оприходования сырья.
		 *
		 * Одновременно создаются:
		 * - физический рулон;
		 * - запись об операции оприходования.
		 *
		 * Обе операции выполняются в одной транзакции.
		 */
		public function store(Request $request): RedirectResponse
		{
			$request->merge([
				'material_id' => $request->input('material_select'),
			]);
			
			$validated = $request->validate(
				[
					'material_id' => ['required', 'integer', 'exists:materials,id'],
					'roll_number' => ['required', 'string', 'max:50'],
					'weight' => ['required', 'numeric', 'gt:0'],
					'comment' => ['nullable', 'string'],
				],
				[
					'material_id.required' => 'Укажите материал.',
					'material_id.integer' => 'Некорректный материал.',
					'material_id.exists' => 'Выбранный материал не существует.',

					'roll_number.required' => 'Укажите номер рулона.',
					'roll_number.string' => 'Номер рулона должен быть строкой.',
					'roll_number.max' => 'Номер рулона не должен превышать 50 символов.',

					'weight.required' => 'Укажите вес.',
					'weight.numeric' => 'Вес должен быть числом.',
					'weight.gt' => 'Вес должен быть больше 0.',

					'comment.string' => 'Комментарий должен быть текстом.',
				]
			);

			try {
				DB::transaction(function () use ($validated) {
					$roll = MaterialRoll::create([
						'material_id' => $validated['material_id'],
						'roll_number' => $validated['roll_number'],
						'weight' => $validated['weight'],
					]);

					MaterialReceipt::create([
						'material_id' => $validated['material_id'],
						'roll_id' => $roll->id,
						'weight' => $validated['weight'],
						'comment' => $validated['comment'] ?? null,
						'user_id' => 1,
					]);
				});
			} catch (UniqueConstraintViolationException $e) {
				return back()
					->withInput()
					->withErrors([
						'roll_number' => 'Рулон с таким номером уже существует для выбранного материала.',
					]);
			}

			return redirect()
				->route('material-receipts.create')
				->with('success', 'Материал успешно оприходован.');
		}
	}