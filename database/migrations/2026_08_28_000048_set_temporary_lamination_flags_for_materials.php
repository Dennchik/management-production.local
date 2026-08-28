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
					->where('name', 'ilike', '%Бумага%')
					->update([
							'lamination_allowed' => true,
					]);

			// Алюминиевая фольга.
			DB::table('materials')
					->where('name', 'ilike', '%Фольга алюминиевая%')
					->update([
							'lamination_allowed' => true,
					]);

			// Пленка FPO.
			DB::table('materials')
					->where('name', 'ilike', '%FPO%')
					->update([
							'lamination_allowed' => true,
					]);

			// Пленка БОПП.
			DB::table('materials')
					->where('name', 'ilike', '%БОПП%')
					->update([
							'lamination_allowed' => true,
					]);

			// Барьерная пленка EVOH.
			DB::table('materials')
					->where('name', 'ilike', '%EVOH%')
					->update([
							'lamination_allowed' => true,
					]);

			// Пленка ПЭ.
			DB::table('materials')
					->where('name', 'ilike', '%ПЭ%')
					->update([
							'lamination_allowed' => true,
					]);

			// МК3 и МК4 являются результатом другого этапа.
			DB::table('materials')
					->where(function ($query) {
						$query
								->where('name', 'ilike', '%МК 3%')
								->orWhere('name', 'ilike', '%МК3%')
								->orWhere('name', 'ilike', '%МК 4%')
								->orWhere('name', 'ilike', '%МК4%');
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