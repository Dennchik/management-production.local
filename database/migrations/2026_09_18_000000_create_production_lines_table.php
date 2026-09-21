<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Производственные линии — фактические линии, относящиеся
		 * к технологической линии (шаблону). Станки (machines)
		 * к шаблонам не привязываются.
		 */
		public function up(): void
		{
			Schema::create('production_lines', function (Blueprint $table) {
				$table->id();

				$table->string('name')->comment('Название производственной линии');

				$table->foreignId('production_operation_id')
						->comment('Технологическая линия (шаблон)')
						->constrained()
						->cascadeOnDelete();

				$table->timestamps();
			});

			Schema::table('machines', function (Blueprint $table) {
				$table->dropConstrainedForeignId('production_operation_id');
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('production_lines');

			Schema::table('machines', function (Blueprint $table) {
				$table->foreignId('production_operation_id')
						->nullable()
						->after('name')
						->constrained('production_operations')
						->nullOnDelete();
			});
		}
	};
