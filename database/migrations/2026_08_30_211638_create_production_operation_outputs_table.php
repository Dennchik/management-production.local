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
			Schema::create('production_operation_outputs', function (Blueprint $table) {
				$table->id();

				$table->foreignId('operation_execution_id')
						->constrained('production_operation_executions')
						->cascadeOnDelete();

				$table->foreignId('material_id')
						->nullable()
						->constrained('materials')
						->restrictOnDelete();

				$table->foreignId('roll_id')
						->nullable()
						->constrained('material_rolls')
						->restrictOnDelete();

				$table->string('output_type')->default('semi_finished');

				$table->string('roll_number')->nullable();

				$table->decimal('weight', 12, 3)->nullable();

				$table->text('comment')->nullable();

				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('production_operation_outputs');
		}
	};