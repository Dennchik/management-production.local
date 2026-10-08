<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::table('materials', function (Blueprint $table) {
			$table->enum('material_type', ['raw', 'product'])
					->default('raw')
					->after('catalog_id');
		});

		Schema::create('orders', function (Blueprint $table) {
			$table->id();

			$table->string('client_name');

			$table->foreignId('material_id')
					->constrained('materials')
					->restrictOnDelete();

			$table->decimal('quantity', 12, 3);

			$table->string('unit', 20)->default('kg');

			$table->enum('status', ['new', 'in_production', 'done', 'cancelled'])
					->default('new');

			$table->text('comment')->nullable();

			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('orders');

		Schema::table('materials', function (Blueprint $table) {
			$table->dropColumn('material_type');
		});
	}
};
