<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Создание справочника типов материалов.
		 */
		public function up(): void
		{
			Schema::create('materials', function (Blueprint $table) {
				$table->id();

				// Основные данные материала.
				$table->string('name')->comment('Наименование материала');

				$table->string('code', 10)->comment('Код типа материала');

				// Используется в зависимости от типа материала:
				// для бумаги — грамматура, для плёнки/фольги — толщина.
				$table->decimal('grammage', 8, 2)->nullable()->comment('Грамматура материала');

				$table->decimal('thickness', 8, 2)->nullable()->comment('Толщина материала');

				$table->unsignedSmallInteger('format')->comment('Формат материала');

				// Существующий производственный идентификатор.
				// Не является идентификатором конкретного физического рулона.
				$table->string('identifier', 20)->unique()->comment('Идентификатор типа материала');

				$table->timestamps();
			});
		}

		/**
		 * Удаление справочника типов материалов.
		 */
		public function down(): void
		{
			Schema::dropIfExists('materials');
		}
	};