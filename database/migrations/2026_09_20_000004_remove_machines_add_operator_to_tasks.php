<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		public function up(): void
		{
			Schema::disableForeignKeyConstraints();

			// Станки не используются: за задачей закрепляется оператор.
			Schema::table('production_tasks', function (Blueprint $table) {
				$table->dropColumn('machine_id');

				$table->foreignId('operator_id')
					->nullable()
					->after('quantity')
					->constrained('users')
					->nullOnDelete();
			});

			Schema::table('users', function (Blueprint $table) {
				$table->dropColumn('machine_id');
			});

			// Планируемый вес не используется: на странице задачи вводится расход и остаток.
			Schema::table('production_task_inputs', function (Blueprint $table) {
				$table->dropColumn('planned_weight');
			});

			Schema::table('production_task_outputs', function (Blueprint $table) {
				$table->dropColumn('planned_weight');
			});

			Schema::dropIfExists('machines');

			Schema::enableForeignKeyConstraints();
		}

		public function down(): void
		{
			Schema::disableForeignKeyConstraints();

			Schema::create('machines', function (Blueprint $table) {
				$table->id();
				$table->string('name')->unique();
				$table->timestamps();
			});

			Schema::table('production_tasks', function (Blueprint $table) {
				$table->dropConstrainedForeignId('operator_id');

				$table->foreignId('machine_id')
					->nullable()
					->after('quantity')
					->constrained('machines')
					->nullOnDelete();
			});

			Schema::table('users', function (Blueprint $table) {
				$table->foreignId('machine_id')
					->nullable()
					->after('role_id');
			});

			Schema::table('production_task_inputs', function (Blueprint $table) {
				$table->decimal('planned_weight', 12, 3)->nullable();
			});

			Schema::table('production_task_outputs', function (Blueprint $table) {
				$table->decimal('planned_weight', 12, 3)->nullable();
			});

			Schema::enableForeignKeyConstraints();
		}
	};
