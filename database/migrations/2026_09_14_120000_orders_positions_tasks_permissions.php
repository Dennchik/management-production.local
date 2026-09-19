<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
	public function up(): void
	{
		// Позиции заказа
		Schema::create('order_items', function (Blueprint $table) {
			$table->id();

			$table->foreignId('order_id')
					->constrained('orders')
					->cascadeOnDelete();

			$table->foreignId('material_id')
					->constrained('materials')
					->restrictOnDelete();

			$table->decimal('quantity', 12, 3);

			$table->string('unit', 20)->default('kg');

			$table->unsignedInteger('sort_order')->default(0);

			$table->timestamps();
		});

		// Перенос старых однострочных заказов в позиции
		$orders = DB::table('orders')->get(['id', 'material_id', 'quantity', 'unit']);

		foreach ($orders as $order) {
			DB::table('order_items')->insert([
					'order_id' => $order->id,
					'material_id' => $order->material_id,
					'quantity' => $order->quantity,
					'unit' => $order->unit ?? 'kg',
					'sort_order' => 0,
					'created_at' => now(),
					'updated_at' => now(),
			]);
		}

		Schema::table('orders', function (Blueprint $table) {
			$table->string('address')
					->nullable()
					->after('client_name');

			$table->dropForeign(['material_id']);
			$table->dropColumn(['material_id', 'quantity', 'unit']);
		});

		// Станки
		Schema::create('machines', function (Blueprint $table) {
			$table->id();
			$table->string('name')->unique();
			$table->timestamps();
		});

		// Роли и права
		Schema::create('roles', function (Blueprint $table) {
			$table->id();
			$table->string('name')->unique();
			$table->timestamps();
		});

		Schema::create('role_permissions', function (Blueprint $table) {
			$table->id();

			$table->foreignId('role_id')
					->constrained('roles')
					->cascadeOnDelete();

			$table->string('object', 50);
			$table->string('action', 20);

			$table->unique(['role_id', 'object', 'action']);
		});

		Schema::table('users', function (Blueprint $table) {
			$table->foreignId('role_id')
					->nullable()
					->after('password');

			$table->foreignId('machine_id')
					->nullable()
					->after('role_id');
		});

		// Производственные задачи
		Schema::create('production_tasks', function (Blueprint $table) {
			$table->id();

			$table->foreignId('order_id')
					->nullable()
					->constrained('orders')
					->nullOnDelete();

			$table->foreignId('material_id')
					->constrained('materials')
					->restrictOnDelete();

			$table->decimal('quantity', 12, 3);

			$table->foreignId('machine_id')
					->nullable()
					->constrained('machines')
					->nullOnDelete();

			$table->enum('status', ['pending', 'in_progress', 'done', 'cancelled'])
					->default('pending');

			$table->timestamp('started_at')->nullable();
			$table->timestamp('completed_at')->nullable();

			$table->foreignId('created_by')
					->nullable()
					->constrained('users')
					->nullOnDelete();

			$table->text('comment')->nullable();

			$table->timestamps();
		});

		Schema::create('production_task_inputs', function (Blueprint $table) {
			$table->id();

			$table->foreignId('task_id')
					->constrained('production_tasks')
					->cascadeOnDelete();

			$table->foreignId('material_id')
					->constrained('materials')
					->restrictOnDelete();

			$table->foreignId('roll_id')
					->nullable()
					->constrained('material_rolls')
					->nullOnDelete();

			$table->decimal('planned_weight', 12, 3)->nullable();
			$table->decimal('actual_weight', 12, 3)->nullable();

			$table->timestamps();
		});

		Schema::create('production_task_outputs', function (Blueprint $table) {
			$table->id();

			$table->foreignId('task_id')
					->constrained('production_tasks')
					->cascadeOnDelete();

			$table->foreignId('material_id')
					->constrained('materials')
					->restrictOnDelete();

			$table->string('roll_number', 50);

			$table->decimal('planned_weight', 12, 3)->nullable();
			$table->decimal('actual_weight', 12, 3)->nullable();

			// Рулон, созданный при завершении задачи
			$table->foreignId('roll_id')
					->nullable()
					->constrained('material_rolls')
					->nullOnDelete();

			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('production_task_outputs');
		Schema::dropIfExists('production_task_inputs');
		Schema::dropIfExists('production_tasks');

		Schema::table('users', function (Blueprint $table) {
			$table->dropConstrainedForeignId('machine_id');
			$table->dropConstrainedForeignId('role_id');
		});

		Schema::dropIfExists('role_permissions');
		Schema::dropIfExists('roles');
		Schema::dropIfExists('machines');

		Schema::table('orders', function (Blueprint $table) {
			$table->dropColumn('address');

			$table->foreignId('material_id')->nullable();
			$table->decimal('quantity', 12, 3)->nullable();
			$table->string('unit', 20)->default('kg');
		});

		Schema::dropIfExists('order_items');
	}
};
