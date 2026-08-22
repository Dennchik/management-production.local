<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Создаёт таблицу операций расхода сырья.
		 *
		 * Операция фиксирует факт списания материала со склада.
		 * Текущий остаток физического рулона хранится в таблице material_rolls.
		 */
		public function up(): void
		{
			Schema::create('material_issues', function (Blueprint $table) {
				$table->id();

				// Материал, который был списан со склада.
				$table->foreignId('material_id')
					->constrained('materials')
					->restrictOnDelete();

				// Физический рулон, с которого был списан материал.
				$table->foreignId('roll_id')
					->constrained('material_rolls')
					->restrictOnDelete();

				// Вес материала, который был списан со склада.
				$table->decimal('weight', 10, 3);

				// Дополнительная информация по операции.
				$table->text('comment')->nullable();

				// Пользователь, выполнивший списание.
				$table->foreignId('user_id')
					->constrained('users')
					->restrictOnDelete();

				$table->timestamps();
			});
		}

		/**
		 * Удаляет таблицу операций расхода.
		 */
		public function down(): void
		{
			Schema::dropIfExists('material_issues');
		}
	};