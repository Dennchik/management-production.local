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
					'tasks' => ['view', 'edit'],
					'warehouse' => ['view'],
					'rolls' => ['view'],
			]);

			$managerRole = Role::firstOrCreate(['name' => 'Менеджер']);
			$managerRole->syncPermissions([
					'materials' => ['view', 'create', 'edit', 'delete'],
					'orders' => ['view', 'create', 'edit'],
					'tasks' => ['view', 'create', 'edit'],
					'warehouse' => ['view'],
					'rolls' => ['view'],
					'operations' => ['view', 'create', 'edit', 'delete'],
					'reports' => ['view'],
			]);

		User::query()->updateOrCreate(
				['name' => 'admin'],
				['password' => 'admin', 'role_id' => $adminRole->id]
		);

		User::query()->updateOrCreate(
				['name' => 'operator'],
				['password' => 'operator', 'role_id' => $operatorRole->id]
		);

			User::query()->updateOrCreate(
					['name' => 'manager'],
					['password' => 'manager', 'role_id' => $managerRole->id]
			);
		}
	}
