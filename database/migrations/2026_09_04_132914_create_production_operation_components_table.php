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
			Schema::create('production_operation_components', function (Blueprint $table) {
				$table->id();

				$table->foreignId('operation_id')
						->constrained('production_operations')
						->cascadeOnDelete();

				$table->foreignId('material_id')
						->constrained('materials')
						->restrictOnDelete();

				$table->enum('direction', ['input', 'output']);

				$table->decimal('quantity', 12, 3)->nullable();

				$table->string('unit', 20)->default('kg');

				$table->boolean('is_required')->default(true);

				$table->unsignedInteger('sort_order')->default(0);

				$table->text('comment')->nullable();

				$table->timestamps();

				$table->index(['operation_id', 'direction']);
			});
		}

		/**
		 * Reverse the migrations.
		 */
		public function down(): void
		{
			Schema::dropIfExists('production_operation_components');
		}
	};