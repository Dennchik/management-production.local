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
			Schema::create('production_route_steps', function (Blueprint $table) {
				$table->id();

				$table->foreignId('route_id')
						->constrained('production_routes')
						->cascadeOnDelete();

				$table->foreignId('operation_id')
						->constrained('production_operations')
						->restrictOnDelete();

				$table->unsignedInteger('step_number');

				$table->string('name')->nullable();

				$table->text('description')->nullable();

				$table->boolean('is_required')->default(true);

				$table->timestamps();

				$table->unique(['route_id', 'step_number']);
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('production_route_steps');
		}
	};