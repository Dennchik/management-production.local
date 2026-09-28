<?php

	namespace Database\Seeders;

	use App\Models\ProductionOperation;
	use Illuminate\Database\Seeder;

	class ProductionOperationSeeder extends Seeder
	{
		/**
		 * Заполняет справочник технологических линий.
		 *
		 * Резка — линия с режимом резки: один материал на входе,
		 * тот же материал другого формата на выходе.
		 */
		public function run(): void
		{
			foreach ($this->operations() as $operation) {
				ProductionOperation::updateOrCreate(
					['code' => $operation['code']],
					[
						'name' => $operation['name'],
						'is_active' => true,
						'is_cutting' => $operation['is_cutting'] ?? false,
					]
				);
			}
		}

		/**
		 * @return array<int, array<string, mixed>>
		 */
		private function operations(): array
		{
			return [
					['name' => 'Кэширование', 'code' => 'laminating'],
					['name' => 'Праймирование', 'code' => 'priming'],
					['name' => 'Резка', 'code' => 'cutting', 'is_cutting' => true],
			];
		}
	}
