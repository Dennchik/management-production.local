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
			// Справочник типов материалов.
			$this->call([
					MaterialSeeder::class,
			]);
		}
	}