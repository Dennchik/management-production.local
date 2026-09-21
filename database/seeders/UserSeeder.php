<?php

	namespace Database\Seeders;

	use App\Models\Role;
	use App\Models\User;
	use Illuminate\Database\Seeder;

	class UserSeeder extends Seeder
	{
		public function run(): void
		{
			$adminRole = Role::firstOrCreate(['name' => 'Администратор']);

			$operatorRole = Role::firstOrCreate(['name' => 'Оператор']);
			$operatorRole->syncPermissions([
					'tasks' => ['view', 'execute'],
					'warehouse' => ['view'],
					'rolls' => ['view'],
			]);

			$managerRole = Role::firstOrCreate(['name' => 'Менеджер']);
			$managerRole->syncPermissions([
					'materials' => ['view', 'create', 'edit', 'delete'],
					'orders' => ['view', 'create', 'edit'],
					'tasks' => ['view', 'create', 'edit', 'cancel', 'execute'],
					'warehouse' => ['view'],
					'rolls' => ['view'],
					'operations' => ['view', 'create', 'edit', 'delete'],
					'reports' => ['view'],
			]);

		User::query()->updateOrCreate(
				['login' => 'admin'],
				[
						'password' => 'admin',
						'role_id' => $adminRole->id,
						'full_name' => 'Админов Админ Админович',
						'display_name' => 'Администратор',
				]
		);

		User::query()->updateOrCreate(
				['login' => 'operator'],
				[
						'password' => 'operator',
						'role_id' => $operatorRole->id,
						'full_name' => 'Операторов Оператор Операторович',
						'display_name' => 'Оператор линии',
				]
		);

			User::query()->updateOrCreate(
					['login' => 'manager'],
					[
							'password' => 'manager',
							'role_id' => $managerRole->id,
							'full_name' => 'Менеджеров Менеджер Менеджерович',
							'display_name' => 'Менеджер продаж',
					]
			);
		}
	}
