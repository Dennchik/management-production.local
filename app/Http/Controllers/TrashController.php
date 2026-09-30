<?php

	namespace App\Http\Controllers;

	use App\Models\Catalog;
	use App\Models\Material;
	use Illuminate\Http\JsonResponse;
	use Illuminate\Support\Facades\DB;
	use Illuminate\View\View;

	/** Занятый идентификатор или имя при восстановлении из корзины. */
	class RestoreConflictException extends \RuntimeException
	{
	}

	class TrashController extends Controller
	{
		/**
		 * Корзина: удалённые каталоги и материалы.
		 */
		public function index(): View
		{
			$catalogs = Catalog::onlyTrashed()
					->withCount('materials')
					->orderByDesc('deleted_at')
					->get();

			$materials = Material::onlyTrashed()
					->with('catalog')
					->withCount('rolls')
					->orderByDesc('deleted_at')
					->get();

			return view('trash.index', compact(
					'catalogs',
					'materials'
			));
		}

		/**
		 * Восстанавливает запись из корзины: каталог — вместе
		 * с материалами и рулонами, материал — с рулонами.
		 */
		public function restore(string $type, int $id): JsonResponse
		{
			try {
				DB::transaction(function () use ($type, $id) {
					if ($type === 'catalogs') {
						$catalog = Catalog::onlyTrashed()->findOrFail($id);

						$catalog->restore();

						Material::onlyTrashed()
								->where('catalog_id', $catalog->id)
								->each(function (Material $material) {
									$this->ensureMaterialCanBeRestored($material);

									$material->rolls()->restore();
									$material->restore();
								});

						return;
					}

					$material = Material::onlyTrashed()->findOrFail($id);

					$this->ensureMaterialCanBeRestored($material);

					$material->rolls()->restore();
					$material->restore();
				});
			} catch (RestoreConflictException $e) {
				return response()->json([
						'success' => false,
						'message' => $e->getMessage(),
				], 422);
			} catch (\Throwable $e) {
				return response()->json([
						'success' => false,
						'message' => 'Не удалось восстановить запись.',
				], 422);
			}

			return response()->json([
					'success' => true,
					'message' => 'Запись восстановлена из корзины.',
			]);
		}

		/**
		 * Удаляет запись из корзины навсегда.
		 */		public function destroy(string $type, int $id): JsonResponse
		{
			try {
				DB::transaction(function () use ($type, $id) {
					if ($type === 'catalogs') {
						$catalog = Catalog::onlyTrashed()->findOrFail($id);

						Material::onlyTrashed()
								->where('catalog_id', $catalog->id)
								->each(static function (Material $material) {
									$material->rolls()->forceDelete();
									$material->forceDelete();
								});

						$catalog->forceDelete();

						return;
					}

					$material = Material::onlyTrashed()->findOrFail($id);

					$material->rolls()->forceDelete();
					$material->forceDelete();
				});
			} catch (\Throwable $e) {
				return response()->json([
						'success' => false,
						'message' => 'Нельзя удалить навсегда: по записи есть проведённые операции.',
				], 422);
			}

			return response()->json([
					'success' => true,
					'message' => 'Запись удалена навсегда.',
			]);
		}

		/**
		 * Проверяет, что идентификатор материала не занят активным
		 * материалом: иначе восстановление упрётся в уникальный индекс.
		 */
		private function ensureMaterialCanBeRestored(Material $material): void
		{
			if ($material->identifier === null) {
				return;
			}

			$busy = Material::query()
					->where('identifier', $material->identifier)
					->where('id', '!=', $material->id)
					->exists();

			if ($busy) {
				throw new RestoreConflictException(
						'Нельзя восстановить: материал с идентификатором «'
						. $material->identifier . '» уже существует.'
				);
			}
		}
	}
