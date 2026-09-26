<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;

	/*
	 * Форматы привязываются к материалу напрямую, а не выводятся
	 * из существующих рулонов: работа с форматом возможна и когда
	 * рулоны этого формата закончились.
	 */
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('material_formats', function (Blueprint $table) {
				$table->id();
				$table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
				$table->unsignedSmallInteger('format');
				$table->timestamps();

				$table->unique(['material_id', 'format']);
			});

			// Перенос форматов с существующих рулонов
			DB::statement(
				'INSERT INTO material_formats (material_id, format, created_at, updated_at)
				 SELECT DISTINCT material_id, format, NOW(), NOW()
				 FROM material_rolls
				 WHERE format IS NOT NULL'
			);
		}

		public function down(): void
		{
			Schema::dropIfExists('material_formats');
		}
	};
