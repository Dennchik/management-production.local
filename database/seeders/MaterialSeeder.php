<?php

	namespace Database\Seeders;

	use App\Models\Catalog;
	use App\Models\Material;
	use App\Models\MaterialRoll;
	use Illuminate\Database\Seeder;

	class MaterialSeeder extends Seeder
	{
		/**
		 * Заполняет справочник каталогами и материалами,
		 * а также создаёт тестовые рулоны по остаткам склада.
		 *
		 * Остатки делятся на рулоны по 300 кг (последний рулон — остаток).
		 * Формат и идентификатор хранятся на рулоне: идентификатор
		 * вычисляется как код + граммаж/толщина + формат.
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
				['sort_order' => $node['sort_order'] ?? 500, 'is_active' => true]
			);

			$materialType = $node['material_type'] ?? $defaultType;

			foreach ($node['materials'] ?? [] as $material) {
				$model = Material::updateOrCreate(
					[
						'name' => $material['name'],
						'code' => $material['code'],
						'grammage' => $material['grammage'] ?? null,
						'thickness' => $material['thickness'] ?? null,
					],
					[
						'catalog_id' => $catalog->id,
						'material_type' => $material['material_type'] ?? $materialType,
						'is_active' => true,
					]
				);

				if (!empty($material['stock'])) {
					$this->seedRolls($model, $material['stock']);
				}
			}

			foreach ($node['children'] ?? [] as $child) {
				$this->seedCatalogNode($child, $catalog->id, $materialType);
			}
		}

		/**
		 * Делит остаток на рулоны по 300 кг и создаёт их.
		 *
		 * @param Material $material Материал, к которому относятся рулоны.
		 * @param array $stock Остаток: формат (мм) => вес (кг). Формат null — без формата.
		 */
		private function seedRolls(Material $material, array $stock): void
		{
			$number = 1;

			foreach ($stock as $format => $weight) {
				// Ключ null (без формата) PHP превращает в пустую строку.
				$format = $format === '' ? null : $format;

				$identifier = MaterialRoll::composeIdentifier(
					$material->code,
					$material->grammage,
					$material->thickness,
					$format
				);

				$weight = (float) $weight;

				while ($weight > 0) {
					$rollWeight = min(300.0, $weight);
					$weight = round($weight - $rollWeight, 3);

					MaterialRoll::updateOrCreate(
						['material_id' => $material->id, 'roll_number' => sprintf('%04d', $number)],
						[
							'weight' => $rollWeight,
							'format' => $format,
							'identifier' => $identifier,
						]
					);

					$number++;
				}
			}
		}

		/**
		 * Дерево каталогов справочника материалов с остатками склада.
		 *
		 * Для плёнок характеристика «формат, толщина» означает
		 * ширину рулона (мм) и толщину материала (мкм).
		 * Для МК запись «880,76» — ширина 880 мм и толщина 0,76 мм.
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
								['name' => 'Бумага ВП 60', 'code' => '13', 'grammage' => 60, 'thickness' => null, 'stock' => [
									820 => 2968.00,
									900 => 9614.00,
									940 => 572.60,
								]],
								['name' => 'Бумага ВП 60 (1120)', 'code' => '13', 'grammage' => 60, 'thickness' => null, 'stock' => [
									900 => 875.50,
								]],
								['name' => 'Бумага ВП 55', 'code' => '13', 'grammage' => 55, 'thickness' => null],
							],
						],
						[
							'name' => 'Бумага БЛ',
							'sort_order' => 2,
							'materials' => [
								['name' => 'Бумага БЛ 60 (40/20) ламинированная', 'code' => '14', 'grammage' => 60, 'thickness' => null, 'stock' => [
									860 => 3071.00,
									900 => 398.10,
									940 => 1145.10,
								]],
								['name' => 'Бумага БЛ 55 (40/15) ламинированная', 'code' => '14', 'grammage' => 55, 'thickness' => null, 'stock' => [
									900 => 797.60,
								]],
							],
						],
					],
				],

				/*
				 * Фольга алюминиевая
				 */
				[
					'name' => 'Фольга алюминиевая',
					'sort_order' => 2,
					'materials' => [
						['name' => 'Фольга алюминиевая 7 мкм', 'code' => '15', 'grammage' => null, 'thickness' => 7, 'stock' => [
							820 => 157.00,
							840 => 1753.00,
							860 => 532.00,
							900 => 6758.00,
						]],
						['name' => 'Фольга алюминиевая 6,35 мкм', 'code' => '15', 'grammage' => null, 'thickness' => 6.35],
					],
				],

				/*
				 * Пленка FPO SP (Беларусь)
				 */
				[
					'name' => 'Пленка FPO',
					'sort_order' => 3,
					'materials' => [
						['name' => 'Пленка FPO SP (Беларусь)', 'code' => '1', 'grammage' => null, 'thickness' => 65, 'stock' => [
							200 => 274.00,
							280 => 92.20,
							340 => 50.00,
							420 => 364.30,
							460 => 147.00,
							480 => 826.80,
							580 => 72.00,
							680 => 108.00,
							830 => 3093.00,
							880 => 881.00,
						]],
						['name' => 'Пленка FPO SP (Беларусь)', 'code' => '1', 'grammage' => null, 'thickness' => 75, 'stock' => [
							370 => 374.40,
							410 => 573.80,
							420 => 555.40,
							440 => 922.10,
							445 => 1770.00,
							460 => 1425.70,
							510 => 406.00,
							540 => 454.00,
							640 => 1366.80,
							840 => 65.50,
						]],
						['name' => 'Пленка FPO SP (Беларусь)', 'code' => '1', 'grammage' => null, 'thickness' => 80, 'stock' => [
							400 => 978.30,
							420 => 2613.80,
							440 => 103.80,
							460 => 6163.90,
						]],
					],
				],

				/*
				 * Пергамент
				 */
				[
					'name' => 'Пергамент',
					'sort_order' => 4,
					'materials' => [
						['name' => 'Пергамент А64', 'code' => '2', 'grammage' => 64, 'thickness' => null, 'stock' => [
							200 => 49.50,
							440 => 40.00,
							500 => 483.70,
							540 => 346.40,
							560 => 29.00,
							740 => 356.10,
							840 => 757.00,
							880 => 1424.40,
							920 => 519.50,
							960 => 406.60,
						]],
						['name' => 'Пергамент А50', 'code' => '2', 'grammage' => 50, 'thickness' => null],
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
								['name' => 'Пленка БОПП жемчужная', 'code' => '5', 'grammage' => null, 'thickness' => 20],
								['name' => 'Пленка БОПП жемчужная', 'code' => '5', 'grammage' => null, 'thickness' => 30],
								['name' => 'Пленка БОПП жемчужная', 'code' => '5', 'grammage' => null, 'thickness' => 35, 'stock' => [
									300 => 192.00,
									400 => 39.00,
									750 => 379.80,
									820 => 392.80,
								]],
								['name' => 'Пленка БОПП жемчужная', 'code' => '5', 'grammage' => null, 'thickness' => 40],
							],
						],
						[
							'name' => 'Пленка БОПП металлиз.',
							'sort_order' => 2,
							'materials' => [
								['name' => 'Пленка БОПП металлиз.', 'code' => '6', 'grammage' => null, 'thickness' => 20, 'stock' => [
									800 => 661.50,
									820 => 940.60,
									840 => 77.00,
									860 => 62.00,
									880 => 791.10,
									980 => 286.30,
								]],
								['name' => 'Пленка БОПП металлиз.', 'code' => '6', 'grammage' => null, 'thickness' => 30],
								['name' => 'Пленка БОПП металлиз.', 'code' => '6', 'grammage' => null, 'thickness' => 35],
								['name' => 'Пленка БОПП металлиз.', 'code' => '6', 'grammage' => null, 'thickness' => 40],
							],
						],
						[
							'name' => 'Пленка БОПП белая',
							'sort_order' => 3,
							'materials' => [
								['name' => 'Пленка БОПП белая', 'code' => '7', 'grammage' => null, 'thickness' => 20, 'stock' => [
									800 => 51.00,
									840 => 85.00,
									960 => 360.90,
									980 => 125.00,
								]],
								['name' => 'Пленка БОПП белая', 'code' => '7', 'grammage' => null, 'thickness' => 30],
								['name' => 'Пленка БОПП белая', 'code' => '7', 'grammage' => null, 'thickness' => 35],
								['name' => 'Пленка БОПП белая', 'code' => '7', 'grammage' => null, 'thickness' => 40],
							],
						],
						[
							'name' => 'Пленка БОПП матовая',
							'sort_order' => 4,
							'materials' => [
								['name' => 'Пленка БОПП матовая', 'code' => '8', 'grammage' => null, 'thickness' => 20, 'stock' => [
									810 => 792.00,
									820 => 201.00,
									860 => 40.00,
									980 => 695.40,
								]],
								['name' => 'Пленка БОПП матовая', 'code' => '8', 'grammage' => null, 'thickness' => 30],
								['name' => 'Пленка БОПП матовая', 'code' => '8', 'grammage' => null, 'thickness' => 35],
								['name' => 'Пленка БОПП матовая', 'code' => '8', 'grammage' => null, 'thickness' => 40],
							],
						],
						[
							'name' => 'Пленка БОПП прозрачная',
							'sort_order' => 5,
							'materials' => [
								['name' => 'Пленка БОПП прозрачная', 'code' => '9', 'grammage' => null, 'thickness' => 20, 'stock' => [
									820 => 686.90,
									830 => 554.00,
									840 => 63.00,
									980 => 210.00,
								]],
								['name' => 'Пленка БОПП прозрачная', 'code' => '9', 'grammage' => null, 'thickness' => 30, 'stock' => [
									500 => 378.00,
									750 => 120.00,
									800 => 53.00,
									960 => 452.70,
								]],
								['name' => 'Пленка БОПП прозрачная', 'code' => '9', 'grammage' => null, 'thickness' => 35, 'stock' => [
									480 => 47.00,
									490 => 162.00,
								]],
								['name' => 'Пленка БОПП прозрачная', 'code' => '9', 'grammage' => null, 'thickness' => 40],
								['name' => 'Пленка БОПП прозрачная Антифог', 'code' => '12', 'grammage' => null, 'thickness' => 30, 'stock' => [
									880 => 201.20,
								]],
							],
						],
						[
							'name' => 'Пленка БОПП перфорация',
							'sort_order' => 6,
							'materials' => [
								['name' => 'Пленка БОПП перфорация (образец)', 'code' => '17', 'grammage' => null, 'thickness' => 20, 'stock' => [
									160 => 12.40,
								]],
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
						['name' => 'Пленка барьерная EVOH прозрачная', 'code' => '10', 'grammage' => null, 'thickness' => 50, 'stock' => [
							800 => 713.00,
							820 => 17.00,
						]],
					],
				],

				/*
				 * Пленки ПЭ
				 */
				[
					'name' => 'Пленка ПЭ-ББЧ-002',
					'sort_order' => 7,
					'materials' => [
						['name' => 'Пленка ПЭ-ББЧ-002 молочная', 'code' => '11', 'grammage' => null, 'thickness' => 80, 'stock' => [
							650 => 2828.00,
						]],
						['name' => 'Пленка ПЭ-Б-СП-001 (образец)', 'code' => '16', 'grammage' => null, 'thickness' => 40, 'stock' => [
							840 => 30.00,
						]],
					],
				],

				/*
				 * Пленка СРР (образцы)
				 */
				[
					'name' => 'Пленка СРР',
					'sort_order' => 8,
					'materials' => [
						['name' => 'Пленка СРР прозрачная (образец)', 'code' => '20', 'grammage' => null, 'thickness' => 25, 'stock' => [
							840 => 24.00,
						]],
						['name' => 'Пленка СРР прозрачная (образец)', 'code' => '20', 'grammage' => null, 'thickness' => 20, 'stock' => [
							980 => 48.00,
						]],
						['name' => 'Пленка СРР металлизиров (образец)', 'code' => '19', 'grammage' => null, 'thickness' => 30, 'stock' => [
							980 => 98.10,
						]],
						['name' => 'Пленка СРР металлизиров (образец)', 'code' => '19', 'grammage' => null, 'thickness' => 20, 'stock' => [
							980 => 26.00,
						]],
						['name' => 'Пленка СРР матовая (образец)', 'code' => '18', 'grammage' => null, 'thickness' => 40, 'stock' => [
							980 => 48.20,
						]],
					],
				],

				/*
				 * Пленка РЕТ (образцы)
				 */
				[
					'name' => 'Пленка РЕТ',
					'sort_order' => 9,
					'materials' => [
						['name' => 'Пленка РЕТ прозрачная (образец)', 'code' => '21', 'grammage' => null, 'thickness' => 12, 'stock' => [
							950 => 18.00,
							1200 => 236.00,
						]],
					],
				],

				/*
				 * Прочие материалы
				 */
				[
					'name' => 'Прочее',
					'sort_order' => 10,
					'materials' => [
						['name' => 'Клей 1-но компонентный', 'code' => '22', 'grammage' => null, 'thickness' => null, 'stock' => [
							null => 450.00,
						]],
					],
				],

				/*
				 * МК (мелованный картон) — производимая продукция
				 */
				[
					'material_type' => 'product',
					'name' => 'МК',
					'sort_order' => 11,
					'children' => [
						[
							'name' => 'МК 3 не праймированный',
							'sort_order' => 1,
							'materials' => [
								['name' => 'МК 3 не праймированный', 'code' => '30', 'grammage' => null, 'thickness' => null, 'stock' => [
									820 => 4112.00,
									880 => 6197.00,
								]],
							],
						],
						[
							'name' => 'МК 4 не праймированный',
							'sort_order' => 2,
							'materials' => [
								['name' => 'МК 4 не праймированный', 'code' => '40', 'grammage' => null, 'thickness' => null, 'stock' => [
									880 => 174.00,
								]],
								['name' => 'МК 4 не праймированный 0,76', 'code' => '40', 'grammage' => null, 'thickness' => 0.76, 'stock' => [
									880 => 160.00,
								]],
							],
						],
						[
							'name' => 'МК 3 праймированный',
							'sort_order' => 3,
							'materials' => [
								['name' => 'МК 3 праймированный не резаный', 'code' => '31', 'grammage' => null, 'thickness' => null, 'stock' => [
									840 => 453.00,
									880 => 3042.00,
								]],
							],
						],
						[
							'name' => 'МК 4 праймированный',
							'sort_order' => 4,
							'materials' => [
								['name' => 'МК 4 праймированный не резаный', 'code' => '41', 'grammage' => null, 'thickness' => null, 'stock' => [
									800 => 403.00,
									840 => 1408.00,
									880 => 539.00,
								]],
								['name' => 'МК 4 праймированный не резаный 0,76', 'code' => '41', 'grammage' => null, 'thickness' => 0.76, 'stock' => [
									880 => 408.00,
								]],
							],
						],
						[
							'name' => 'МК 3 праймированный резаный',
							'sort_order' => 5,
							'materials' => [
								['name' => 'МК 3 праймированный резаный', 'code' => '32', 'grammage' => null, 'thickness' => null, 'stock' => [
									340 => 252.00,
									400 => 35.00,
									410 => 344.00,
									420 => 4060.00,
									430 => 115.00,
									460 => 1718.00,
									640 => 145.00,
								]],
								['name' => 'МК 3 праймированный резаный 0,5', 'code' => '32', 'grammage' => null, 'thickness' => 0.5, 'stock' => [
									420 => 76.00,
									440 => 2719.00,
								]],
							],
						],
						[
							'name' => 'МК 4 праймированный резаный',
							'sort_order' => 6,
							'materials' => [
								['name' => 'МК 4 праймированный резаный', 'code' => '42', 'grammage' => null, 'thickness' => null, 'stock' => [
									200 => 96.00,
									340 => 152.00,
									420 => 951.00,
									440 => 26.00,
									460 => 25.00,
									500 => 498.00,
									640 => 126.00,
								]],
							],
						],
					],
				],
			];
		}
	}
