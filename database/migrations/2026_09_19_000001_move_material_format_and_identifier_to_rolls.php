<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		public function up(): void
		{
			Schema::table('material_rolls', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат рулона (ширина, мм)');
				$table->string('identifier', 20)->nullable()->comment('Идентификатор: код + граммаж/толщина + формат');
			});

			// Переносим значения существующим рулонам из их материалов.
			DB::statement(
				'UPDATE material_rolls SET format = m.format, identifier = m.identifier
				FROM materials m WHERE m.id = material_rolls.material_id'
			);

			Schema::table('materials', function (Blueprint $table) {
				$table->dropColumn(['format', 'identifier']);
			});
		}

		public function down(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат материала');
				$table->string('identifier', 20)->nullable()->comment('Идентификатор материала');
			});

			// Возвращаем данные из первого рулона материала.
			DB::statement(
				'UPDATE materials m SET format = r.format, identifier = r.identifier
				FROM material_rolls r
				WHERE r.id = (SELECT id FROM material_rolls WHERE material_id = m.id ORDER BY id LIMIT 1)'
			);

			Schema::table('material_rolls', function (Blueprint $table) {
				$table->dropColumn(['format', 'identifier']);
			});
		}
	};
