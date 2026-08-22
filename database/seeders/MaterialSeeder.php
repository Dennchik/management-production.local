<?php

	namespace Database\Seeders;

	use App\Models\Material;
	use Illuminate\Database\Seeder;

	class MaterialSeeder extends Seeder
	{
		/**
		 * Заполняет справочник согласованными типами материалов.
		 *
		 * Идентификаторы являются существующими производственными кодами
		 * и не должны изменяться автоматически программой.
		 */
		public function run(): void
		{
			$materials = [

				/*
				 * Бумага ВП
				 */
				[
					'name' => 'Бумага ВП 60',
					'code' => '13',
					'grammage' => 60,
					'thickness' => null,
					'format' => 820,
					'identifier' => '1360820',
				],
				[
					'name' => 'Бумага ВП 60',
					'code' => '13',
					'grammage' => 60,
					'thickness' => null,
					'format' => 840,
					'identifier' => '1360840',
				],
				[
					'name' => 'Бумага ВП 55',
					'code' => '13',
					'grammage' => 55,
					'thickness' => null,
					'format' => 820,
					'identifier' => '1355820',
				],

				/*
				 * Бумага БЛ
				 */
				[
					'name' => 'Бумага БЛ 60',
					'code' => '14',
					'grammage' => 60,
					'thickness' => null,
					'format' => 820,
					'identifier' => '1460820',
				],
				[
					'name' => 'Бумага БЛ 55',
					'code' => '14',
					'grammage' => 55,
					'thickness' => null,
					'format' => 820,
					'identifier' => '1455820',
				],

				/*
				 * Фольга алюминиевая
				 *
				 * Для фольги в идентификаторе используется существующая
				 * система кодирования. Например, толщина 6,35 мкм
				 * представлена в идентификаторе кодом 06.
				 */
				[
					'name' => 'Фольга алюминиевая 7 мкм',
					'code' => '15',
					'grammage' => null,
					'thickness' => 7,
					'format' => 820,
					'identifier' => '1507820',
				],
				[
					'name' => 'Фольга алюминиевая 6,35 мкм',
					'code' => '15',
					'grammage' => null,
					'thickness' => 6.35,
					'format' => 820,
					'identifier' => '1506820',
				],

				/*
				 * Пленка FPO SP
				 */
				[
					'name' => 'Пленка FPO',
					'code' => '1',
					'grammage' => null,
					'thickness' => 65,
					'format' => 820,
					'identifier' => '165820',
				],
				[
					'name' => 'Пленка FPO',
					'code' => '1',
					'grammage' => null,
					'thickness' => 75,
					'format' => 820,
					'identifier' => '175820',
				],
				[
					'name' => 'Пленка FPO',
					'code' => '1',
					'grammage' => null,
					'thickness' => 80,
					'format' => 820,
					'identifier' => '180820',
				],

				/*
				 * Пергамент А
				 */
				[
					'name' => 'Пергамент А64',
					'code' => '2',
					'grammage' => 64,
					'thickness' => null,
					'format' => 820,
					'identifier' => '264820',
				],
				[
					'name' => 'Пергамент А50',
					'code' => '2',
					'grammage' => 50,
					'thickness' => null,
					'format' => 820,
					'identifier' => '250820',
				],

				/*
				 * Пленка БОПП жемчужная
				 */
				[
					'name' => 'Пленка БОПП жемчужная',
					'code' => '5',
					'grammage' => null,
					'thickness' => 20,
					'format' => 820,
					'identifier' => '520820',
				],
				[
					'name' => 'Пленка БОПП жемчужная',
					'code' => '5',
					'grammage' => null,
					'thickness' => 30,
					'format' => 820,
					'identifier' => '530820',
				],
				[
					'name' => 'Пленка БОПП жемчужная',
					'code' => '5',
					'grammage' => null,
					'thickness' => 35,
					'format' => 820,
					'identifier' => '535820',
				],
				[
					'name' => 'Пленка БОПП жемчужная',
					'code' => '5',
					'grammage' => null,
					'thickness' => 40,
					'format' => 820,
					'identifier' => '540820',
				],

				/*
				 * Пленка БОПП металлиз.
				 */
				[
					'name' => 'Пленка БОПП металлиз.',
					'code' => '6',
					'grammage' => null,
					'thickness' => 20,
					'format' => 820,
					'identifier' => '620820',
				],
				[
					'name' => 'Пленка БОПП металлиз.',
					'code' => '6',
					'grammage' => null,
					'thickness' => 30,
					'format' => 820,
					'identifier' => '630820',
				],
				[
					'name' => 'Пленка БОПП металлиз.',
					'code' => '6',
					'grammage' => null,
					'thickness' => 35,
					'format' => 820,
					'identifier' => '635820',
				],
				[
					'name' => 'Пленка БОПП металлиз.',
					'code' => '6',
					'grammage' => null,
					'thickness' => 40,
					'format' => 820,
					'identifier' => '640820',
				],

				/*
				 * Пленка БОПП белая
				 */
				[
					'name' => 'Пленка БОПП белая',
					'code' => '7',
					'grammage' => null,
					'thickness' => 20,
					'format' => 820,
					'identifier' => '720820',
				],
				[
					'name' => 'Пленка БОПП белая',
					'code' => '7',
					'grammage' => null,
					'thickness' => 30,
					'format' => 820,
					'identifier' => '730820',
				],
				[
					'name' => 'Пленка БОПП белая',
					'code' => '7',
					'grammage' => null,
					'thickness' => 35,
					'format' => 820,
					'identifier' => '735820',
				],
				[
					'name' => 'Пленка БОПП белая',
					'code' => '7',
					'grammage' => null,
					'thickness' => 40,
					'format' => 820,
					'identifier' => '740820',
				],

				/*
				 * Пленка БОПП матовая
				 */
				[
					'name' => 'Пленка БОПП матовая',
					'code' => '8',
					'grammage' => null,
					'thickness' => 20,
					'format' => 820,
					'identifier' => '820820',
				],
				[
					'name' => 'Пленка БОПП матовая',
					'code' => '8',
					'grammage' => null,
					'thickness' => 30,
					'format' => 820,
					'identifier' => '830820',
				],
				[
					'name' => 'Пленка БОПП матовая',
					'code' => '8',
					'grammage' => null,
					'thickness' => 35,
					'format' => 820,
					'identifier' => '835820',
				],
				[
					'name' => 'Пленка БОПП матовая',
					'code' => '8',
					'grammage' => null,
					'thickness' => 40,
					'format' => 820,
					'identifier' => '840820',
				],

				/*
				 * Пленка БОПП прозрачная
				 */
				[
					'name' => 'Пленка БОПП прозрачная',
					'code' => '9',
					'grammage' => null,
					'thickness' => 20,
					'format' => 820,
					'identifier' => '920820',
				],
				[
					'name' => 'Пленка БОПП прозрачная',
					'code' => '9',
					'grammage' => null,
					'thickness' => 30,
					'format' => 820,
					'identifier' => '930820',
				],
				[
					'name' => 'Пленка БОПП прозрачная',
					'code' => '9',
					'grammage' => null,
					'thickness' => 35,
					'format' => 820,
					'identifier' => '935820',
				],
				[
					'name' => 'Пленка БОПП прозрачная',
					'code' => '9',
					'grammage' => null,
					'thickness' => 40,
					'format' => 820,
					'identifier' => '940820',
				],

				/*
				 * Пленка барьерная EVOH
				 */
				[
					'name' => 'Пленка барьерная EVOH прозрачная',
					'code' => '10',
					'grammage' => null,
					'thickness' => 50,
					'format' => 820,
					'identifier' => '1050820',
				],

				/*
				 * Пленка ПЭ-ББЧ-002
				 */
				[
					'name' => 'Пленка ПЭ-ББЧ-002 молочная',
					'code' => '11',
					'grammage' => null,
					'thickness' => 80,
					'format' => 820,
					'identifier' => '1180820',
				],

				/*
				 * Пленка БОПП прозрачная Антифог
				 */
				[
					'name' => 'Пленка БОПП прозрачная Антифог',
					'code' => '12',
					'grammage' => null,
					'thickness' => 30,
					'format' => 820,
					'identifier' => '1230820',
				],

				/*
				 * МКНП (мелованный картон не праймированный) 3
				 */
				[
					'name' => 'МК 3 не праймированный 81 гр.',
					'code' => '30',
					'grammage' => 81,
					'thickness' => null,
					'format' => 820,
					'identifier' => '3081820',
				],
				[
					'name' => 'МК 3 не праймированный 76 гр.',
					'code' => '30',
					'grammage' => 76,
					'thickness' => null,
					'format' => 820,
					'identifier' => '3076820',
				],

				/*
				 * МК 3 праймированный
				 */
				[
					'name' => 'МК 3 праймированный 81 гр.',
					'code' => '31',
					'grammage' => 81,
					'thickness' => null,
					'format' => 820,
					'identifier' => '3181820',
				],
				[
					'name' => 'МК 3 праймированный 76 гр.',
					'code' => '31',
					'grammage' => 76,
					'thickness' => null,
					'format' => 820,
					'identifier' => '3176820',
				],

				/*
				 * МКНП (мелованный картон не праймированный) 4
				 */
				[
					'name' => 'МК 4 не праймированный 81 гр.',
					'code' => '40',
					'grammage' => 81,
					'thickness' => null,
					'format' => 820,
					'identifier' => '4081820',
				],
				[
					'name' => 'МК 4 не праймированный 76 гр.',
					'code' => '40',
					'grammage' => 76,
					'thickness' => null,
					'format' => 820,
					'identifier' => '4076820',
				],

				/*
				 * МК 4 праймированный
				 *
				 * Код 41 соответствует МК 4.
				 */
				[
					'name' => 'МК 4 праймированный 81 гр.',
					'code' => '41',
					'grammage' => 81,
					'thickness' => null,
					'format' => 820,
					'identifier' => '4181820',
				],
				[
					'name' => 'МК 4 праймированный 76 гр.',
					'code' => '41',
					'grammage' => 76,
					'thickness' => null,
					'format' => 820,
					'identifier' => '4176820',
				],
			];

			foreach ($materials as $material) {
				Material::updateOrCreate(
					['identifier' => $material['identifier']],
					$material
				);
			}
		}
	}