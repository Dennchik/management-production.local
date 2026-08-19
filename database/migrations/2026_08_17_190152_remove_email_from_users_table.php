<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Удаляет email, так как в системе пользователь
		 * идентифицируется по имени пользователя и паролю.
		 */
		public function up(): void
		{
			Schema::table('users', function (Blueprint $table) {
				$table->dropUnique('users_email_unique');
				$table->dropColumn('email');
				$table->dropColumn('email_verified_at');
			});
		}

		/**
		 * Восстанавливает поля email при откате миграции.
		 */
		public function down(): void
		{
			Schema::table('users', function (Blueprint $table) {
				$table->string('email')->unique();
				$table->timestamp('email_verified_at')->nullable();
			});
		}
	};