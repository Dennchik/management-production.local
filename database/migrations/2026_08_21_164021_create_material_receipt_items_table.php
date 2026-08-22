<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Создаёт позиции приходного ордера.
		 *
		 * Один приходный ордер может содержать
		 * несколько физических рулонов.
		 */
		public function up(): void
		{
			Schema::create('material_receipt_items', function (Blueprint $table) {
				$table->id();

				// Приходный ордер.
				$table->foreignId('material_receipt_id')
					->constrained('material_receipts')
					->cascadeOnDelete();

				// Материал конкретного рулона.
				$table->foreignId('material_id')
					->constrained('materials')
					->restrictOnDelete();

				// Физический рулон.
				$table->foreignId('roll_id')
					->constrained('material_rolls')
					->restrictOnDelete();

				// Вес принятого рулона.
				$table->decimal('weight', 10, 3);

				$table->timestamps();
			});
		}

		/**
		 * Удаляет таблицу позиций приходных ордеров.
		 */
		public function down(): void
		{
			Schema::dropIfExists('material_receipt_items');
		}
	};