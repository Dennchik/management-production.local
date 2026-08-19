<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Создаёт таблицу операций оприходования сырья.
		 *
		 * Операция фиксирует факт поступления материала на склад.
		 * Сам текущий остаток хранится в таблице material_rolls.
		 */
		public function up(): void
		{
			Schema::create('material_receipts', function (Blueprint $table) {
				$table->id();

				// Материал, который был оприходован.
				$table->foreignId('material_id')
						->constrained('materials')
						->restrictOnDelete();

				// Физический рулон, созданный в результате оприходования.
				$table->foreignId('roll_id')
						->constrained('material_rolls')
						->restrictOnDelete();

				// Вес материала, который был принят на склад.
				$table->decimal('weight', 10, 3);

				// Дополнительная информация по операции.
				$table->text('comment')->nullable();

				// Пользователь, выполнивший оприходование.
				$table->foreignId('user_id')
						->constrained('users')
						->restrictOnDelete();

				$table->timestamps();
			});
		}

		/**
		 * Удаляет таблицу операций оприходования.
		 */
		public function down(): void
		{
			Schema::dropIfExists('material_receipts');
		}
	};