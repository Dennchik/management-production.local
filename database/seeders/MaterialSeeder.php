<?php

	namespace Database\Seeders;

	use App\Models\Catalog;
	use App\Models\Material;
	use Illuminate\Database\Seeder;

	class MaterialSeeder extends Seeder
	{
		/**
		 * Заполняет справочник каталогами и согласованными типами материалов.
		 *
		 * Идентификаторы являются существующими производственными кодами
		 * и не должны изменяться автоматически программой.
		 *
		 * Структура: каталог → подкаталоги → материалы.
		 */
		public function run(): void
		{
			foreach ($this->catalogTree() as $node) {
				$this->seedCatalogNode($node, null, 'raw');
			}
		}

		/**
		 * Создаёт каталог (при необходимости вложенный) и его материалы.
		 *
		 * @param string $defaultType Тип материала, наследуемый от родительского узла.
		 */
		private function seedCatalogNode(array $node, ?int $parentId, string $defaultType): void
		{
			$catalog = Catalog::updateOrCreate(
					['name' => $node['name'], 'parent_id' => $parentId],
					['sort_order' => $node['sort_order'] ?? 0, 'is_active' => true]
			);

			$materialType = $node['material_type'] ?? $defaultType;

			foreach ($node['materials'] ?? [] as $material) {
				Material::updateOrCreate(
						['identifier' => $material['identifier']],
						$material + [
								'catalog_id' => $catalog->id,
								'material_type' => $material['material_type'] ?? $materialType,
						]
				);
			}

			foreach ($node['children'] ?? [] as $child) {
				$this->seedCatalogNode($child, $catalog->id, $materialType);
			}
		}

		/**
		 * Дерево каталогов справочника материалов.
		 */
		private function catalogTree(): array
		{
			return [

				/*
				 * Бумага
				 */
				[
					'name' => 'Бумага',
					'sort_order' => 1,
					'children' => [
						[
							'name' => 'Бумага ВП',
							'sort_order' => 1,
							'materials' => [
								['name' => 'Бумага ВП 60', 'code' => '13', 'grammage' => 60, 'thickness' => null, 'format' => 820, 'identifier' => '1360820'],
								['name' => 'Бумага ВП 60', 'code' => '13', 'grammage' => 60, 'thickness' => null, 'format' => 840, 'identifier' => '1360840'],
								['name' => 'Бумага ВП 55', 'code' => '13', 'grammage' => 55, 'thickness' => null, 'format' => 820, 'identifier' => '1355820'],
							],
						],
						[
							'name' => 'Бумага БЛ',
							'sort_order' => 2,
							'materials' => [
								['name' => 'Бумага БЛ 60', 'code' => '14', 'grammage' => 60, 'thickness' => null, 'format' => 820, 'identifier' => '1460820'],
								['name' => 'Бумага БЛ 55', 'code' => '14', 'grammage' => 55, 'thickness' => null, 'format' => 820, 'identifier' => '1455820'],
							],
						],
					],
				],

				/*
				 * Фольга алюминиевая
				 *
				 * Для фольги в идентификаторе используется существующая
				 * система кодирования. Например, толщина 6,35 мкм
				 * представлена в идентификаторе кодом 06.
				 */
				[
					'name' => 'Фольга алюминиевая',
					'sort_order' => 2,
					'materials' => [
						['name' => 'Фольга алюминиевая 7 мкм', 'code' => '15', 'grammage' => null, 'thickness' => 7, 'format' => 820, 'identifier' => '1507820'],
						['name' => 'Фольга алюминиевая 6,35 мкм', 'code' => '15', 'grammage' => null, 'thickness' => 6.35, 'format' => 820, 'identifier' => '1506820'],
					],
				],

				/*
				 * Пленка FPO
				 */
				[
					'name' => 'Пленка FPO',
					'sort_order' => 3,
					'materials' => [
						['name' => 'Пленка FPO', 'code' => '1', 'grammage' => null, 'thickness' => 65, 'format' => 820, 'identifier' => '165820'],
						['name' => 'Пленка FPO', 'code' => '1', 'grammage' => null, 'thickness' => 75, 'format' => 820, 'identifier' => '175820'],
						['name' => 'Пленка FPO', 'code' => '1', 'grammage' => null, 'thickness' => 80, 'format' => 820, 'identifier' => '180820'],
					],
				],

				/*
				 * Пергамент
				 */
				[
					'name' => 'Пергамент',
					'sort_order' => 4,
					'materials' => [
						['name' => 'Пергамент А64', 'code' => '2', 'grammage' => 64, 'thickness' => null, 'format' => 820, 'identifier' => '264820'],
						['name' => 'Пергамент А50', 'code' => '2', 'grammage' => 50, 'thickness' => null, 'format' => 820, 'identifier' => '250820'],
					],
				],

				/*
				 * Пленка БОПП
				 */
				[
					'name' => 'Пленка БОПП',
					'sort_order' => 5,
					'children' => [
						[
							'name' => 'Пленка БОПП жемчужная',
							'sort_order' => 1,
							'materials' => [
								['name' => 'Пленка БОПП жемчужная', 'code' => '5', 'grammage' => null, 'thickness' => 20, 'format' => 820, 'identifier' => '520820'],
								['name' => 'Пленка БОПП жемчужная', 'code' => '5', 'grammage' => null, 'thickness' => 30, 'format' => 820, 'identifier' => '530820'],
								['name' => 'Пленка БОПП жемчужная', 'code' => '5', 'grammage' => null, 'thickness' => 35, 'format' => 820, 'identifier' => '535820'],
								['name' => 'Пленка БОПП жемчужная', 'code' => '5', 'grammage' => null, 'thickness' => 40, 'format' => 820, 'identifier' => '540820'],
							],
						],
						[
							'name' => 'Пленка БОПП металлиз.',
							'sort_order' => 2,
							'materials' => [
								['name' => 'Пленка БОПП металлиз.', 'code' => '6', 'grammage' => null, 'thickness' => 20, 'format' => 820, 'identifier' => '620820'],
								['name' => 'Пленка БОПП металлиз.', 'code' => '6', 'grammage' => null, 'thickness' => 30, 'format' => 820, 'identifier' => '630820'],
								['name' => 'Пленка БОПП металлиз.', 'code' => '6', 'grammage' => null, 'thickness' => 35, 'format' => 820, 'identifier' => '635820'],
								['name' => 'Пленка БОПП металлиз.', 'code' => '6', 'grammage' => null, 'thickness' => 40, 'format' => 820, 'identifier' => '640820'],
							],
						],
						[
							'name' => 'Пленка БОПП белая',
							'sort_order' => 3,
							'materials' => [
								['name' => 'Пленка БОПП белая', 'code' => '7', 'grammage' => null, 'thickness' => 20, 'format' => 820, 'identifier' => '720820'],
								['name' => 'Пленка БОПП белая', 'code' => '7', 'grammage' => null, 'thickness' => 30, 'format' => 820, 'identifier' => '730820'],
								['name' => 'Пленка БОПП белая', 'code' => '7', 'grammage' => null, 'thickness' => 35, 'format' => 820, 'identifier' => '735820'],
								['name' => 'Пленка БОПП белая', 'code' => '7', 'grammage' => null, 'thickness' => 40, 'format' => 820, 'identifier' => '740820'],
							],
						],
						[
							'name' => 'Пленка БОПП матовая',
							'sort_order' => 4,
							'materials' => [
								['name' => 'Пленка БОПП матовая', 'code' => '8', 'grammage' => null, 'thickness' => 20, 'format' => 820, 'identifier' => '820820'],
								['name' => 'Пленка БОПП матовая', 'code' => '8', 'grammage' => null, 'thickness' => 30, 'format' => 820, 'identifier' => '830820'],
								['name' => 'Пленка БОПП матовая', 'code' => '8', 'grammage' => null, 'thickness' => 35, 'format' => 820, 'identifier' => '835820'],
								['name' => 'Пленка БОПП матовая', 'code' => '8', 'grammage' => null, 'thickness' => 40, 'format' => 820, 'identifier' => '840820'],
							],
						],
						[
							'name' => 'Пленка БОПП прозрачная',
							'sort_order' => 5,
							'materials' => [
								['name' => 'Пленка БОПП прозрачная', 'code' => '9', 'grammage' => null, 'thickness' => 20, 'format' => 820, 'identifier' => '920820'],
								['name' => 'Пленка БОПП прозрачная', 'code' => '9', 'grammage' => null, 'thickness' => 30, 'format' => 820, 'identifier' => '930820'],
								['name' => 'Пленка БОПП прозрачная', 'code' => '9', 'grammage' => null, 'thickness' => 35, 'format' => 820, 'identifier' => '935820'],
								['name' => 'Пленка БОПП прозрачная', 'code' => '9', 'grammage' => null, 'thickness' => 40, 'format' => 820, 'identifier' => '940820'],

								/*
								 * Пленка БОПП прозрачная Антифог
								 */
								['name' => 'Пленка БОПП прозрачная Антифог', 'code' => '12', 'grammage' => null, 'thickness' => 30, 'format' => 820, 'identifier' => '1230820'],
							],
						],
					],
				],

				/*
				 * Пленка барьерная EVOH
				 */
				[
					'name' => 'Пленка барьерная EVOH',
					'sort_order' => 6,
					'materials' => [
						['name' => 'Пленка барьерная EVOH прозрачная', 'code' => '10', 'grammage' => null, 'thickness' => 50, 'format' => 820, 'identifier' => '1050820'],
					],
				],

				/*
				 * Пленка ПЭ-ББЧ-002
				 */
				[
					'name' => 'Пленка ПЭ-ББЧ-002',
					'sort_order' => 7,
					'materials' => [
						['name' => 'Пленка ПЭ-ББЧ-002 молочная', 'code' => '11', 'grammage' => null, 'thickness' => 80, 'format' => 820, 'identifier' => '1180820'],
					],
				],

				/*
				 * МК (мелованный картон) — производимая продукция
				 */
				[
					'material_type' => 'product',
					'name' => 'МК',
					'sort_order' => 8,
					'children' => [
						[
							'name' => 'МК 3 не праймированный',
							'sort_order' => 1,
							'materials' => [
								['name' => 'МК 3 не праймированный 81 гр.', 'code' => '30', 'grammage' => 81, 'thickness' => null, 'format' => 820, 'identifier' => '3081820'],
								['name' => 'МК 3 не праймированный 76 гр.', 'code' => '30', 'grammage' => 76, 'thickness' => null, 'format' => 820, 'identifier' => '3076820'],
							],
						],
						[
							'name' => 'МК 3 праймированный',
							'sort_order' => 2,
							'materials' => [
								['name' => 'МК 3 праймированный 81 гр.', 'code' => '31', 'grammage' => 81, 'thickness' => null, 'format' => 820, 'identifier' => '3181820'],
								['name' => 'МК 3 праймированный 76 гр.', 'code' => '31', 'grammage' => 76, 'thickness' => null, 'format' => 820, 'identifier' => '3176820'],
							],
						],
						[
							'name' => 'МК 4 не праймированный',
							'sort_order' => 3,
							'materials' => [
								['name' => 'МК 4 не праймированный 81 гр.', 'code' => '40', 'grammage' => 81, 'thickness' => null, 'format' => 820, 'identifier' => '4081820'],
								['name' => 'МК 4 не праймированный 76 гр.', 'code' => '40', 'grammage' => 76, 'thickness' => null, 'format' => 820, 'identifier' => '4076820'],
							],
						],
						[
							'name' => 'МК 4 праймированный',
							'sort_order' => 4,
							'materials' => [
								['name' => 'МК 4 праймированный 81 гр.', 'code' => '41', 'grammage' => 81, 'thickness' => null, 'format' => 820, 'identifier' => '4181820'],
								['name' => 'МК 4 праймированный 76 гр.', 'code' => '41', 'grammage' => 76, 'thickness' => null, 'format' => 820, 'identifier' => '4176820'],
							],
						],
					],
				],
			];
		}
	}
