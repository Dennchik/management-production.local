<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;

	/*
	 * Право смены статуса задач: роли, умеющие редактировать задачи,
	 * получают его автоматически. Плюс отметка списания входа задачи:
	 * повторное завершение переоткрытой задачи не списывает рулон дважды.
	 */
	return new class extends Migration
	{
		public function up(): void
		{
			Schema::table('production_task_inputs', function (Blueprint $table) {
				$table->timestamp('issued_at')->nullable();
			});

			// Роли с правом редактирования задач получают право смены статуса
			DB::statement(
				'INSERT INTO role_permissions (role_id, object, action)
				 SELECT DISTINCT role_id, \'tasks\', \'status\'
				 FROM role_permissions
				 WHERE object = \'tasks\' AND action = \'edit\''
			);
		}

		public function down(): void
		{
			DB::table('role_permissions')
				->where('object', 'tasks')
				->where('action', 'status')
				->delete();

			Schema::table('production_task_inputs', function (Blueprint $table) {
				$table->dropColumn('issued_at');
			});
		}
	};
