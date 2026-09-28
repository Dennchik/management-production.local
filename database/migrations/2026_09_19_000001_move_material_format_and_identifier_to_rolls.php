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
			// Построчно, без UPDATE ... JOIN: Query Builder на pgsql
			// не включает join в update, а UPDATE...FROM — диалектный SQL.
			$materials = DB::table('materials')
				->get(['id', 'format', 'identifier']);

			foreach ($materials as $material) {
				DB::table('material_rolls')
					->where('material_id', $material->id)
					->update([
							'format' => $material->format,
							'identifier' => $material->identifier,
					]);
			}

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

			// Возвращаем данные из первого рулона материала: построчно,
			// без диалектного UPDATE ... FROM и подзапроса с LIMIT.
			$firstRolls = DB::table('material_rolls as r')
				->select('r.material_id', 'r.format', 'r.identifier')
				->whereIn('r.id', function ($query) {
						$query->selectRaw('MIN(id)')
							->from('material_rolls')
							->groupBy('material_id');
				})
				->get();

			foreach ($firstRolls as $roll) {
				DB::table('materials')
					->where('id', $roll->material_id)
					->update([
							'format' => $roll->format,
							'identifier' => $roll->identifier,
					]);
			}

			Schema::table('material_rolls', function (Blueprint $table) {
				$table->dropColumn(['format', 'identifier']);
			});
		}
	};
