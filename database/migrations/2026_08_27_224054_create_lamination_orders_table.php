<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Создание заданий на ламинацию.
		 */
		public function up(): void
		{
			Schema::create('lamination_orders', function (Blueprint $table) {
				$table->id();

				// Номер задания.
				$table->string('order_number', 50)->unique();

				// Вид изготавливаемого полуфабриката.
				$table->string('product_type', 255);

				// Бумага.
				$table->foreignId('paper_material_id')
						->constrained('materials')
						->restrictOnDelete();

				// Фольга.
				$table->foreignId('foil_material_id')
						->nullable()
						->constrained('materials')
						->restrictOnDelete();

				// Клей.
				$table->foreignId('glue_material_id')
						->nullable()
						->constrained('materials')
						->restrictOnDelete();

				// Идентификаторы выбранных материалов
				// фиксируем в задании, чтобы они не зависели
				// от последующих изменений справочника.
				$table->string('paper_identifier', 20);

				$table->string('foil_identifier', 20)->nullable();

				$table->string('glue_identifier', 20)->nullable();

				// Статус задания.
				$table->string('status', 30)->default('draft');

				// Комментарий оператора.
				$table->text('comment')->nullable();

				// Пользователь, создавший задание.
				$table->foreignId('user_id')
						->nullable()
						->constrained()
						->nullOnDelete();

				$table->timestamps();
			});
		}

		/**
		 * Удаление заданий на ламинацию.
		 */
		public function down(): void
		{
			Schema::dropIfExists('lamination_orders');
		}
	};