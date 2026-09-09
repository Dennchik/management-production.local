<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Run the migrations.
		 */
		public function up(): void
		{
			Schema::table('production_operation_inputs', function (Blueprint $table) {
				$table->foreignId('component_id')
						->nullable()
						->after('operation_execution_id')
						->constrained('production_operation_components')
						->restrictOnDelete();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::table('production_operation_inputs', function (Blueprint $table) {
				$table->dropForeign(['component_id']);
				$table->dropColumn('component_id');
			});
		}
	};