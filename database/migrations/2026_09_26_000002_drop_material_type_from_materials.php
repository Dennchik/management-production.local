<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Тип материала («Расходник»/«Продукция») выведен из системы:
		 * он нигде не используется, колонка удаляется.
		 */
		public function up(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->dropColumn('material_type');
			});
		}

		public function down(): void
		{
			// Существующие строки получат значение по умолчанию.
			Schema::table('materials', function (Blueprint $table) {
				$table->enum('material_type', ['raw', 'product'])
					->default('raw')
					->comment('Тип: raw — расходник, product — продукция');
			});
		}
	};
