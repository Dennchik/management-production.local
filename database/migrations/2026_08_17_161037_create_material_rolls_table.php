<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Создаёт таблицу физических рулонов.
		 *
		 * Каждый рулон относится к определённому типу материала
		 * и хранит его текущий фактический остаток в килограммах.
		 */
		public function up(): void
		{
			Schema::create('material_rolls', function (Blueprint $table) {
				$table->id();

				// Ссылка на тип материала из справочника materials.
				$table->foreignId('material_id')
						->constrained('materials')
						->restrictOnDelete();

				// Номер конкретного физического рулона.
				$table->string('roll_number', 50);

				// Текущий фактический вес рулона в килограммах.
				$table->decimal('weight', 10, 3);

				$table->timestamps();

				// Один номер рулона может повторяться у разных материалов,
				// но не должен дублироваться внутри одного типа материала.
				$table->unique(['material_id', 'roll_number']);
			});
		}

		/**
		 * Удаляет таблицу физических рулонов.
		 */
		public function down(): void
		{
			Schema::dropIfExists('material_rolls');
		}
	};