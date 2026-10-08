<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Database\Schema\Blueprint;
	use Illuminate\Support\Facades\DB;
	use Illuminate\Support\Facades\Schema;

	return new class extends Migration
	{
		/**
		 * Концепция «формат и идентификатор на рулоне» отменена:
		 * оба поля возвращаются материалу, рулон снова хранит
		 * только номер и вес.
		 */
		public function up(): void
		{
			Schema::table('materials', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат материала');
				$table->string('identifier', 30)->nullable()->comment('Идентификатор типа материала');
			});

			// Формат берём из самого старого рулона материала,
			// при его отсутствии — минимальный закреплённый формат.
			$firstRolls = DB::table('material_rolls as r')
				->select('r.material_id', 'r.format')
				->whereIn('r.id', function ($query) {
						$query->selectRaw('MIN(id)')
							->from('material_rolls')
							->groupBy('material_id');
				})
				->get();

			foreach ($firstRolls as $roll) {
				DB::table('materials')
					->where('id', $roll->material_id)
					->update(['format' => $roll->format]);
			}

			$fallbackFormats = DB::table('material_formats')
				->selectRaw('material_id, MIN(format) as format')
				->groupBy('material_id')
				->get();

			foreach ($fallbackFormats as $row) {
				DB::table('materials')
					->where('id', $row->material_id)
					->whereNull('format')
					->update(['format' => $row->format]);
			}

			// Идентификатор вычисляется заново по текущей формуле
			// (код + грамматура/толщина + цифры формата), а не копируется
			// из рулонов: у материалов без рулонов он тоже появится.
			$identifierByMaterialId = [];

			DB::table('materials')
				->get(['id', 'code', 'grammage', 'thickness', 'format'])
				->each(function ($material) use (&$identifierByMaterialId) {
					$identifier = self::composeIdentifier(
						$material->code,
						$material->grammage,
						$material->thickness,
						$material->format
					);

					if ($identifier !== null) {
						DB::table('materials')
							->where('id', $material->id)
							->update(['identifier' => $identifier]);

						$identifierByMaterialId[$material->id] = $identifier;
					}
				});

			// У двух материалов с одинаковыми кодом, грамматурой/толщиной
			// и форматом идентификатор совпадёт: первый сохраняем,
			// остальным оставляем пустой (уникальный индекс ниже).
			$seenIdentifiers = [];

			foreach ($identifierByMaterialId as $materialId => $identifier) {
				if (in_array($identifier, $seenIdentifiers, true)) {
					DB::table('materials')
						->where('id', $materialId)
						->update(['identifier' => null]);
				} else {
					$seenIdentifiers[] = $identifier;
				}
			}

			Schema::table('materials', function (Blueprint $table) {
				$table->unique('identifier');
			});

			// Состав задачи/линии перестаёт нести формат: он есть только у
			// материала. Возможные дубли «материал + направление» с разными
			// форматами схлопываем, оставляя первое объявление, — иначе
			// не создастся уникальный индекс без формата.
			foreach ([
				'production_line_material' => ['production_line_id', 'material_id', 'direction'],
				'production_task_material' => ['task_id', 'material_id', 'direction'],
			] as $pivotTable => $keyColumns) {
				$seenKeys = [];
				$staleIds = [];

				DB::table($pivotTable)
					->orderBy('id')
					->get()
					->each(function ($pivotRow) use (&$seenKeys, &$staleIds, $keyColumns) {
						$key = implode('|', array_map(
							static fn ($column) => $pivotRow->{$column},
							$keyColumns
						));

						if (isset($seenKeys[$key])) {
							$staleIds[] = $pivotRow->id;
						} else {
							$seenKeys[$key] = true;
						}
					});

				if ($staleIds !== []) {
					DB::table($pivotTable)->whereIn('id', $staleIds)->delete();
				}
			}

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->unique(['production_line_id', 'material_id', 'direction'], 'plm_line_material_direction_unique');
			});

			Schema::table('production_task_material', function (Blueprint $table) {
				$table->unique(['task_id', 'material_id', 'direction']);
			});

			// Уникальность с форматом ищем по фактическим колонкам:
			// имя индекса в базе может отличаться от ожидаемого
			// (PostgreSQL обрезает длинные имена до 63 символов).
			foreach ([
				'production_line_material' => ['production_line_id', 'material_id', 'direction', 'format'],
				'production_task_material' => ['task_id', 'material_id', 'direction', 'format'],
			] as $pivotTable => $indexColumns) {
				$formatIndex = collect(Schema::getIndexes($pivotTable))
					->first(static fn (array $index) => $index['unique'] === true
							&& $index['primary'] === false
							&& $index['columns'] === $indexColumns);

				if ($formatIndex !== null) {
					Schema::table($pivotTable, function (Blueprint $table) use ($formatIndex) {
						$table->dropUnique($formatIndex['name']);
					});
				}

				Schema::table($pivotTable, function (Blueprint $table) {
					$table->dropColumn('format');
				});
			}

			Schema::table('material_formats', function (Blueprint $table) {
				$table->dropForeign(['material_id']);
			});

			Schema::dropIfExists('material_formats');

			Schema::table('material_rolls', function (Blueprint $table) {
				$table->dropColumn(['format', 'identifier']);
			});
		}

		public function down(): void
		{
			Schema::table('material_rolls', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат рулона (ширина, мм)');
				$table->string('identifier', 20)->nullable()->comment('Идентификатор: код + граммаж/толщина + формат');
			});

			DB::table('material_rolls as r')
				->join('materials as m', 'm.id', '=', 'r.material_id')
				->update([
						'r.format' => DB::raw('m.format'),
						'r.identifier' => DB::raw('m.identifier'),
				]);

			Schema::create('material_formats', function (Blueprint $table) {
				$table->id();
				$table->foreignId('material_id')->constrained('materials')->cascadeOnDelete();
				$table->unsignedSmallInteger('format')->comment('Формат (ширина, мм)');
				$table->timestamps();

				$table->unique(['material_id', 'format']);
			});

			DB::table('materials')
				->whereNotNull('format')
				->orderBy('id')
				->each(function ($material) {
					DB::table('material_formats')->insert([
							'material_id' => $material->id,
							'format' => $material->format,
							'created_at' => now(),
							'updated_at' => now(),
					]);
				});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат (ширина, мм)');
			});

			Schema::table('production_task_material', function (Blueprint $table) {
				$table->unsignedSmallInteger('format')->nullable()->comment('Формат (ширина, мм)');
			});

			Schema::table('production_line_material', function (Blueprint $table) {
				$table->unique(['production_line_id', 'material_id', 'direction', 'format'], 'plm_line_material_format_unique');
				$table->dropUnique('plm_line_material_direction_unique');
			});

			Schema::table('production_task_material', function (Blueprint $table) {
				$table->unique(['task_id', 'material_id', 'direction', 'format'], 'ptm_task_material_format_unique');
				$table->dropUnique(['task_id', 'material_id', 'direction']);
			});

			Schema::table('materials', function (Blueprint $table) {
				$table->dropUnique(['identifier']);
				$table->dropColumn(['format', 'identifier']);
			});
		}

		/**
		 * Зеркало формулы Material::composeIdentifier(): миграция должна
		 * оставаться самодостаточной при любых будущих изменениях модели.
		 */
		private static function composeIdentifier(?string $code, $grammage, $thickness, $format): ?string
		{
			$value = $grammage ?? $thickness;

			$formatPart = preg_replace('/\D/', '', (string) $format);

			if ($code === null || $code === '' || $value === null || $formatPart === '') {
				return null;
			}

			// Значение идёт в идентификатор цифрами: 6,35 -> «635»,
			// 0,76 -> «76», минимум два знака: толщина 7 мкм -> «07».
			$valuePart = str_pad(
				(string) ltrim((string) preg_replace('/\D/', '', (string) (float) $value), '0'),
				2,
				'0',
				STR_PAD_LEFT
			);

			if ($valuePart === '') {
				return null;
			}

			return $code . $valuePart . $formatPart;
		}
	};
