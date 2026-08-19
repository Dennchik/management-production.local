<?php

	namespace Database\Seeders;

	use App\Models\User;
	use Illuminate\Database\Seeder;

	class UserSeeder extends Seeder
	{
		/**
		 * Создаёт начальную учётную запись администратора системы.
		 */
		public function run(): void
		{
			User::create([
				// Имя пользователя используется как логин для входа в систему.
					'name' => 'admin',

				// Пароль автоматически хешируется моделью User.
					'password' => 'admin',
			]);
		}
	}