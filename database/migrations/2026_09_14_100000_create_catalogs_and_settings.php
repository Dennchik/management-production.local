<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		Schema::create('catalogs', function (Blueprint $table) {
			$table->id();
			$table->string('name');
			$table->foreignId('parent_id')
					->nullable()
					->constrained('catalogs')
					->nullOnDelete();
			$table->unsignedInteger('sort_order')->default(0);
			$table->boolean('is_active')->default(true);
			$table->timestamps();
		});

		Schema::table('materials', function (Blueprint $table) {
			$table->foreignId('catalog_id')
					->nullable()
					->after('identifier')
					->constrained('catalogs')
					->nullOnDelete();
		});

		Schema::create('settings', function (Blueprint $table) {
			$table->id();
			$table->string('key')->unique();
			$table->text('value')->nullable();
			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::table('materials', function (Blueprint $table) {
			$table->dropConstrainedForeignId('catalog_id');
		});

		Schema::dropIfExists('settings');
		Schema::dropIfExists('catalogs');
	}
};
