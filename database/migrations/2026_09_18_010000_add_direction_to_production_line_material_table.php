<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Материал производственной линии бывает входным (сырьё)
		 * или выходным (продукция).
		 */
		public function up(): void
		{
			Schema::table('production_line_material', function (Blueprint $table) {
				$table->string('direction')->default('input')->after('material_id');
			});

			/*
			 * Один материал может быть и входом, и выходом линии.
			 * Новый индекс создаётся до удаления старого: в MySQL
			 * внешний ключ не может остаться без индекса (ошибка 1553).
			 */
			Schema::table('production_line_material', function (Blueprint $table) {
				// Имя короткое явно: MySQL ограничивает идентификаторы 64 символами
				$table->unique(['production_line_id', 'material_id', 'direction'], 'plm_line_material_direction_unique');
			});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->dropUnique('production_line_material_production_line_id_material_id_unique');
			});
		}

		public function down(): void
		{
			Schema::table('production_line_material', function (Blueprint $table) {
				// Сначала восстановление старого индекса, затем удаление нового
				$table->unique(['production_line_id', 'material_id']);
			});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->dropUnique('plm_line_material_direction_unique');
				$table->dropColumn('direction');
			});
		}
	};
