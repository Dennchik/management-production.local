<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		public function up(): void
		{
			// Формат становится частью состава линии и задачи:
			// материал выбирается всегда с конкретным форматом.
			// Имена индексов короткие явно: MySQL ограничивает идентификаторы
			// 64 символами. Новый индекс создаётся до удаления старого:
			// в MySQL внешний ключ не может остаться без индекса (ошибка 1553).
			Schema::table('production_line_material', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат (ширина, мм)');
			});

			Schema::table('production_task_material', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат (ширина, мм)');
			});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->unique(['production_line_id', 'material_id', 'direction', 'format'], 'plm_line_material_format_unique');
				$table->dropUnique('plm_line_material_direction_unique');
			});

			Schema::table('production_task_material', function (Blueprint $table) {
				$table->unique(['task_id', 'material_id', 'direction', 'format'], 'ptm_task_material_format_unique');
				$table->dropUnique(['task_id', 'material_id', 'direction']);
			});
		}

		public function down(): void
		{
			Schema::table('production_task_material', function (Blueprint $table) {
				$table->unique(['task_id', 'material_id', 'direction']);
				$table->dropUnique('ptm_task_material_format_unique');
			});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->unique(['production_line_id', 'material_id', 'direction'], 'plm_line_material_direction_unique');
				$table->dropUnique('plm_line_material_format_unique');
			});

			Schema::table('production_task_material', function (Blueprint $table) {
				$table->dropColumn('format');
			});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->dropColumn('format');
			});
		}
	};
