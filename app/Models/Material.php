<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
			'name',
			'code',
			'grammage',
			'thickness',
			'catalog_id',
			'material_type',
			'is_active',
	])]
	class Material extends Model
	{
		public const TYPES = [
				'raw' => 'Расходник',
				'product' => 'Продукция',
		];

		protected function casts(): array
		{
			return [
					'grammage' => 'decimal:2',
					'thickness' => 'decimal:2',
					'is_active' => 'boolean',
				'material_type' => 'string',
			];
		}

		/**
		 * Форматы этого материала по его рулонам, через запятую.
		 */
		public function getRollFormatsAttribute(): string
		{
			return $this->rolls
				->pluck('format')
				->filter()
				->unique()
				->sort()
				->map(static fn ($format) => (string) $format)
				->implode(', ');
		}

		/**
		 * Идентификаторы этого материала по его рулонам, через запятую.
		 */
		public function getRollIdentifiersAttribute(): string
		{
			return $this->rolls
				->pluck('identifier')
				->filter()
				->unique()
				->sort(SORT_STRING)
				->implode(', ');
		}

		/**
		 * Идентификатор рулонов заданного формата этого материала.
		 */
		public function identifierForFormat($format): ?string
		{
			return $this->rolls
				->first(static fn ($roll) => (string) $roll->format === (string) $format)
				?->identifier;
		}

		/**
		 * Каталог, к которому отнесён материал.
		 */
		public function catalog(): BelongsTo
		{
			return $this->belongsTo(Catalog::class);
		}

		/**
		 * Разрешённые технологические линии материала.
		 */
		public function allowedOperations(): BelongsToMany
		{
			return $this->belongsToMany(
					ProductionOperation::class,
					'material_production_operation'
			)->orderBy('id');
		}

		/**
		 * Физические рулоны этого типа материала.
		 */
		public function rolls(): HasMany
		{
			return $this->hasMany(MaterialRoll::class);
		}

		/**
		 * Операции оприходования этого материала.
		 */
		public function receipts(): HasMany
		{
			return $this->hasMany(MaterialReceipt::class);
		}

		/**
		 * Операции расхода этого материала.
		 */
		public function issues(): HasMany
		{
			return $this->hasMany(MaterialIssue::class);
		}
	}