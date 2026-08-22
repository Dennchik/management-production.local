<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Переводит существующие приходы
		 * на новую структуру с позициями.
		 */
		public function up(): void
		{
			/*
			 * Сначала переносим существующие данные
			 * из material_receipts в material_receipt_items.
			 */
			$receipts = DB::table('material_receipts')
				->select([
					'id',
					'material_id',
					'roll_id',
					'weight',
					'created_at',
					'updated_at',
				])
				->get();

			foreach ($receipts as $receipt) {
				DB::table('material_receipt_items')->insert([
					'material_receipt_id' => $receipt->id,
					'material_id' => $receipt->material_id,
					'roll_id' => $receipt->roll_id,
					'weight' => $receipt->weight,
					'created_at' => $receipt->created_at,
					'updated_at' => $receipt->updated_at,
				]);
			}

			/*
			 * После успешного переноса удаляем старые
			 * поля из таблицы приходных ордеров.
			 */
			Schema::table('material_receipts', function (Blueprint $table) {
				$table->dropForeign(['material_id']);
				$table->dropForeign(['roll_id']);

				$table->dropColumn([
					'material_id',
					'roll_id',
					'weight',
				]);
			});
		}

		/**
		 * Возвращает старую структуру.
		 */
		public function down(): void
		{
			/*
			 * Возвращаем старые поля.
			 */
			Schema::table('material_receipts', function (Blueprint $table) {
				$table->foreignId('material_id')
					->nullable()
					->constrained('materials')
					->restrictOnDelete();

				$table->foreignId('roll_id')
					->nullable()
					->constrained('material_rolls')
					->restrictOnDelete();

				$table->decimal('weight', 10, 3)
					->nullable();
			});

			/*
			 * Восстанавливаем данные из первой позиции
			 * каждого приходного ордера.
			 */
			$receipts = DB::table('material_receipts')
				->select('id')
				->get();

			foreach ($receipts as $receipt) {
				$item = DB::table('material_receipt_items')
					->where('material_receipt_id', $receipt->id)
					->orderBy('id')
					->first();

				if (!$item) {
					continue;
				}

				DB::table('material_receipts')
					->where('id', $receipt->id)
					->update([
						'material_id' => $item->material_id,
						'roll_id' => $item->roll_id,
						'weight' => $item->weight,
					]);
			}

			/*
			 * Удаляем позиции.
			 */
			DB::table('material_receipt_items')->delete();
		}
	};