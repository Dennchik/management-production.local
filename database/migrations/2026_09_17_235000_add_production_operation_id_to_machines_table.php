<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Фактическая производственная линия (станок) относится
		 * к технологической линии — шаблону.
		 */
		public function up(): void
		{
			Schema::table('machines', function (Blueprint $table) {
				$table->foreignId('production_operation_id')
						->nullable()
						->after('name')
						->constrained('production_operations')
						->nullOnDelete();
			});
		}

		public function down(): void
		{
			Schema::table('machines', function (Blueprint $table) {
				$table->dropConstrainedForeignId('production_operation_id');
			});
		}
	};
