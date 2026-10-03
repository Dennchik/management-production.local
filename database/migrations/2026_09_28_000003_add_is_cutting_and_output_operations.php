<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Режим резки технологической линии: на входе один материал,
		 * на выходе тот же материал другого формата.
		 */
		public function up(): void
		{
			Schema::table('production_operations', function (Blueprint $table) {
				$table->boolean('is_cutting')
					->default(false)
					->after('is_active')
					->comment('Режим резки: один материал на входе, тот же материал другого формата на выходе');
			});

			Schema::create('production_operation_output', function (Blueprint $table) {
				$table->id();
				$table->foreignId('production_operation_id')
					->comment('Технологическая линия, с которой уходит продукция')
					->constrained()
					->cascadeOnDelete();
				$table->foreignId('output_operation_id')
					->comment('Технологическая линия, на которую поступает продукция')
					->constrained('production_operations')
					->cascadeOnDelete();
				$table->unique(['production_operation_id', 'output_operation_id'], 'poo_operation_output_unique');
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('production_operation_output');

			Schema::table('production_operations', function (Blueprint $table) {
				$table->dropColumn('is_cutting');
			});
		}
	};
