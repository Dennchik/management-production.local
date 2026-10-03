<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Корзина: каталоги, материалы и рулоны удаляются мягко
		 * и попадают в корзину с возможностью восстановления.
		 */
		public function up(): void
		{
			Schema::table('catalogs', function (Blueprint $table) {
				$table->softDeletes();
			});

			Schema::table('materials', function (Blueprint $table) {
				$table->softDeletes();
			});

			Schema::table('material_rolls', function (Blueprint $table) {
				$table->softDeletes();
			});
		}

		public function down(): void
		{
			Schema::table('material_rolls', function (Blueprint $table) {
				$table->dropSoftDeletes();
			});

			Schema::table('materials', function (Blueprint $table) {
				$table->dropSoftDeletes();
			});

			Schema::table('catalogs', function (Blueprint $table) {
				$table->dropSoftDeletes();
			});
		}
	};
