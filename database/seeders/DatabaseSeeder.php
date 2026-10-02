<?php

	namespace Database\Seeders;

	use Illuminate\Database\Seeder;

	class DatabaseSeeder extends Seeder
	{
		/**
		 * Запускает основные Seeder приложения.
		 *
		 * Здесь определяется порядок первоначального
		 * заполнения справочников и системных данных.
		 */
		public function run(): void
		{
			// Пользователи, роли и станки + справочники материалов
			// и технологических линий.
			//
			$this->call([
					UserSeeder::class,
					MaterialSeeder::class,
					ProductionOperationSeeder::class,
					ProductionDemoSeeder::class,
			]);
		}
	}