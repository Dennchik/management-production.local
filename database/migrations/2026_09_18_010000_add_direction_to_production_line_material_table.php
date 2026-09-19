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

			// Один материал может быть и входом, и выходом линии.
			Schema::table('production_line_material', function (Blueprint $table) {
				$table->dropUnique('production_line_material_production_line_id_material_id_unique');
			});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->unique(['production_line_id', 'material_id', 'direction']);
			});
		}

		public function down(): void
		{
			Schema::table('production_line_material', function (Blueprint $table) {
				$table->dropUnique('production_line_material_production_line_id_material_id_direction_unique');
				$table->unique(['production_line_id', 'material_id']);
				$table->dropColumn('direction');
			});
		}
	};
