<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Support\Collection;

	#[Fillable([
			'name',
			'parent_id',
			'sort_order',
			'is_active',
	])]
	class Catalog extends Model
	{
		public const HIERARCHY_SETTING_KEY = 'catalogs.hierarchy_enabled';

		protected function casts(): array
		{
			return [
					'parent_id' => 'integer',
					'sort_order' => 'integer',
					'is_active' => 'boolean',
			];
		}

		public function parent(): BelongsTo
		{
			return $this->belongsTo(Catalog::class, 'parent_id');
		}

		public function children(): HasMany
		{
			return $this->hasMany(Catalog::class, 'parent_id')
					->orderBy('sort_order')
					->orderBy('name');
		}

		public function materials(): HasMany
		{
			return $this->hasMany(Material::class);
		}

		/**
		 * Включена ли иерархия каталогов.
		 */
		public static function hierarchyEnabled(): bool
		{
			return Setting::enabled(static::HIERARCHY_SETTING_KEY, true);
		}

		/**
		 * Каталоги, доступные для выбора родителя.
		 * При выключенной иерархии — только корневые.
		 */
		public static function selectableParents(?int $excludeId = null): Collection
		{
			$query = static::query()
					->orderBy('sort_order')
					->orderBy('name');

			if (!static::hierarchyEnabled()) {
				$query->whereNull('parent_id');
			}

			$catalogs = $query->get();

			if ($excludeId !== null && static::hierarchyEnabled()) {
				// Исключаем сам каталог и всех его потомков.
				$excluded = static::descendantIds($excludeId)->push($excludeId);

				$catalogs = $catalogs->reject(
						static fn (Catalog $catalog) => $excluded->contains($catalog->id)
				);
			}

			return $catalogs;
		}

		/**
		 * Все идентификаторы потомков каталога.
		 */
		public static function descendantIds(int $catalogId): Collection
		{
			$children = static::query()
					->where('parent_id', $catalogId)
					->pluck('id');

			foreach ($children as $childId) {
				$children = $children->merge(static::descendantIds($childId));
			}

			return $children->unique();
		}

		/**
		 * Карта «id каталога => путь» вида «Родитель / Потомок».
		 */
		public static function pathMap(): array
		{
			$all = static::query()
					->orderBy('sort_order')
					->orderBy('name')
					->get()
					->keyBy('id');

			$paths = [];

			foreach ($all as $catalog) {
				$segments = [$catalog->name];
				$parent = $catalog->parent_id !== null ? ($all[$catalog->parent_id] ?? null) : null;

				while ($parent !== null) {
					array_unshift($segments, $parent->name);
					$parent = $parent->parent_id !== null ? ($all[$parent->parent_id] ?? null) : null;
				}

				$paths[$catalog->id] = implode(' / ', $segments);
			}

			return $paths;
		}
	}
