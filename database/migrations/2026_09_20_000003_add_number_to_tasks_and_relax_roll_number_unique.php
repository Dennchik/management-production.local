<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		public function up(): void
		{
			// Номер задачи уникален внутри года и начинается с 1 каждый год.
			Schema::table('production_tasks', function (Blueprint $table) {
				$table->unsignedInteger('number')->nullable()->comment('Номер задачи внутри года');
			});

			DB::table('production_tasks')
				->whereNull('number')
				->update(['number' => DB::raw('id')]);

			// Номера рулонов продукции назначает система («номерЗадачи/порядковый»),
			// поэтому жёсткая уникальность (материал, номер) больше не нужна:
			// номера задач повторяются каждый год.
			// Индекс material_id создаётся до удаления уникального: в MySQL
			// внешний ключ не может остаться без индекса (ошибка 1553).
			Schema::table('material_rolls', function (Blueprint $table) {
				$table->index('material_id');
				$table->dropUnique(['material_id', 'roll_number']);
			});
		}

		public function down(): void
		{
			Schema::table('material_rolls', function (Blueprint $table) {
				$table->unique(['material_id', 'roll_number']);
				$table->dropIndex('material_rolls_material_id_index');
			});

			Schema::table('production_tasks', function (Blueprint $table) {
				$table->dropColumn('number');
			});
		}
	};
