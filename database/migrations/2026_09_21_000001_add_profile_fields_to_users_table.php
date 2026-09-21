<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Подробная карточка пользователя: `name` становится логином,
		 * добавляются ФИО одной строкой и имя для отображения в системе.
		 */
		public function up(): void
		{
			Schema::table('users', function (Blueprint $table) {
				$table->renameColumn('name', 'login');
				$table->unique('login');

				$table->string('full_name')->nullable();
				$table->string('display_name')->nullable();
			});
		}

		/**
		 * Возвращает таблицу к прежнему виду.
		 */
		public function down(): void
		{
			Schema::table('users', function (Blueprint $table) {
				$table->dropUnique('users_login_unique');
				$table->renameColumn('login', 'name');

				$table->dropColumn(['full_name', 'display_name']);
			});
		}
	};
