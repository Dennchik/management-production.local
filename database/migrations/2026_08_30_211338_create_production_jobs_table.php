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
			Schema::create('production_jobs', function (Blueprint $table) {
				$table->id();

				$table->string('job_number')->unique();

				$table->foreignId('route_id')
						->constrained('production_routes')
						->restrictOnDelete();

				$table->string('product_name')->nullable();

				$table->decimal('planned_weight', 12, 3)->nullable();

				$table->string('status')->default('draft');

				$table->text('comment')->nullable();

				$table->timestamps();
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('production_jobs');
		}
	};