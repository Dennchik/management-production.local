<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		public function up(): void
		{
			Schema::table('production_tasks', function (Blueprint $table) {
				// Шаблон производства, по которому создана задача.
				$table->foreignId('production_line_id')
					->nullable()
					->constrained('production_lines')
					->nullOnDelete();
			});

			// Материалы конкретной задачи (могут отличаться от шаблона).
			Schema::create('production_task_material', function (Blueprint $table) {
				$table->id();
				$table->foreignId('task_id')->constrained('production_tasks')->cascadeOnDelete();
				$table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
				$table->string('direction')->default('input')->comment('input|output');
				$table->timestamps();

				$table->unique(['task_id', 'material_id', 'direction']);
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('production_task_material');

			Schema::table('production_tasks', function (Blueprint $table) {
				$table->dropConstrainedForeignId('production_line_id');
			});
		}
	};
