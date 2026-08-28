<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Добавляет признак того, что материал может использоваться
		 * как основа в операции ламинации.
		 */
		public function up(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->boolean('lamination_base_allowed')
						->default(false)
						->after('lamination_allowed')
						->comment('Разрешено использовать как основу для ламинации');
			});
		}

		/**
		 * Удаляет признак основы для ламинации.
		 */
		public function down(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->dropColumn('lamination_base_allowed');
			});
		}
	};

