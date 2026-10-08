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
			Schema::create('production_operation_executions', function (Blueprint $table) {
				$table->id();

				$table->foreignId('production_job_id')
						->constrained('production_jobs')
						->cascadeOnDelete();

				$table->foreignId('route_step_id')
						->constrained('production_route_steps')
						->restrictOnDelete();

				$table->unsignedInteger('execution_number')->default(1);

				$table->string('status')->default('pending');

				$table->timestamp('started_at')->nullable();
				$table->timestamp('completed_at')->nullable();

				$table->text('comment')->nullable();

				$table->timestamps();

				// Имя короткое явно: MySQL ограничивает идентификаторы 64 символами
				$table->unique([
						'production_job_id',
						'route_step_id',
						'execution_number',
				], 'poe_job_step_number_unique');
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('production_operation_executions');
		}
	};