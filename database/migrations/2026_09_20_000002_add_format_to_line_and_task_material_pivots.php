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
			Schema::table('production_line_material', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат (ширина, мм)');
				$table->dropUnique(['production_line_id', 'material_id', 'direction']);
				$table->unique(['production_line_id', 'material_id', 'direction', 'format']);
			});

			Schema::table('production_task_material', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат (ширина, мм)');
				$table->dropUnique(['task_id', 'material_id', 'direction']);
				$table->unique(['task_id', 'material_id', 'direction', 'format']);
			});
		}

		public function down(): void
		{
			Schema::table('production_task_material', function (Blueprint $table) {
				$table->dropUnique(['task_id', 'material_id', 'direction', 'format']);
				$table->unique(['task_id', 'material_id', 'direction']);
				$table->dropColumn('format');
			});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->dropUnique(['production_line_id', 'material_id', 'direction', 'format']);
				$table->unique(['production_line_id', 'material_id', 'direction']);
				$table->dropColumn('format');
			});
		}
	};
