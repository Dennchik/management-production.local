<?php

	use Illuminate\Database\Migrations\Migration;
	use Illuminate\Support\Facades\DB;

	return new class extends Migration
	{
		/**
		 * Временно устанавливает доступность материалов для ламинации
		 * на основании существующих наименований материалов.
		 */
		public function up(): void
		{
			// Сначала запрещаем ламинацию всем материалам.
			DB::table('materials')->update([
					'lamination_allowed' => false,
			]);

			// Бумага.
			DB::table('materials')
					->whereIlike('name', '%Бумага%')
					->update([
							'lamination_allowed' => true,
					]);

			// Алюминиевая фольга.
			DB::table('materials')
					->whereIlike('name', '%Фольга алюминиевая%')
					->update([
							'lamination_allowed' => true,
					]);

			// Пленка FPO.
			DB::table('materials')
					->whereIlike('name', '%FPO%')
					->update([
							'lamination_allowed' => true,
					]);

			// Пленка БОПП.
			DB::table('materials')
					->whereIlike('name', '%БОПП%')
					->update([
							'lamination_allowed' => true,
					]);

			// Барьерная пленка EVOH.
			DB::table('materials')
					->whereIlike('name', '%EVOH%')
					->update([
							'lamination_allowed' => true,
					]);

			// Пленка ПЭ.
			DB::table('materials')
					->whereIlike('name', '%ПЭ%')
					->update([
							'lamination_allowed' => true,
					]);

			// МК3 и МК4 являются результатом другого этапа.
			DB::table('materials')
					->where(function ($query) {
						$query
								->whereIlike('name', '%МК 3%')
								->orWhereIlike('name', '%МК3%')
								->orWhereIlike('name', '%МК 4%')
								->orWhereIlike('name', '%МК4%');
					})
					->update([
							'lamination_allowed' => false,
					]);
		}

		/**
		 * Откат временных значений.
		 */
		public function down(): void
		{
			DB::table('materials')->update([
					'lamination_allowed' => false,
			]);
		}
	};
