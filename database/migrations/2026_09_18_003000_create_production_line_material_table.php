<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration {
		/**
		 * Материалы производственной линии.
		 */
		public function up(): void
		{
			Schema::create('production_line_material', function (Blueprint $table) {
				$table->id();

				$table->foreignId('production_line_id')
						->constrained()
						->cascadeOnDelete();

				$table->foreignId('material_id')
						->constrained()
						->cascadeOnDelete();

				$table->unique(['production_line_id', 'material_id']);
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('production_line_material');
		}
	};
