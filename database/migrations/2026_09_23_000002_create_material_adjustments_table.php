<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	/*
	 * Ордера корректировки: правка веса рулона вручную
	 * с сохранением истории (до / корректировка / после).
	 */
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::create('material_adjustments', function (Blueprint $table) {
				$table->id();
				$table->foreignId('material_id')->constrained('materials')->restrictOnDelete();
				$table->foreignId('roll_id')->constrained('material_rolls')->restrictOnDelete();
				$table->decimal('weight_before', 12, 3);
				$table->decimal('adjustment', 12, 3);
				$table->decimal('weight_after', 12, 3);
				$table->string('comment')->nullable();
				$table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
				$table->timestamps();
			});
		}

		public function down(): void
		{
			Schema::dropIfExists('material_adjustments');
		}
	};
