<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Связь материалов с разрешёнными технологическими линиями:
		 * какие линии можно выполнять над материалом.
		 */
		public function up(): void
		{
			Schema::create('material_production_operation', function (Blueprint $table) {
				$table->id();

				$table->foreignId('material_id')
						->constrained()
						->cascadeOnDelete();

				$table->foreignId('production_operation_id')
						->constrained()
						->cascadeOnDelete();

				$table->unique(['material_id', 'production_operation_id']);
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('material_production_operation');
		}
	};
