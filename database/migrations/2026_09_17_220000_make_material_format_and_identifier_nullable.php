<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Формат и идентификатор материала становятся необязательными:
		 * идентификатор вычисляется из кода, грамматуры (или толщины)
		 * и формата, которых у материала может не быть.
		 */
		public function up(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')
						->nullable()
						->comment('Формат материала')
						->change();

				$table->string('identifier', 30)
						->nullable()
						->comment('Идентификатор типа материала')
						->change();
			});
		}

		/**
		 * Возврат NOT NULL возможен только при отсутствии NULL-значений.
		 */
		public function down(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')
						->comment('Формат материала')
						->change();

				$table->string('identifier', 20)
						->comment('Идентификатор типа материала')
						->change();
			});
		}
	};
