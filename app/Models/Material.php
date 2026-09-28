<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Database\Eloquent\SoftDeletes;

	#[Fillable([
			'name',
			'code',
			'grammage',
			'thickness',
			'format',
			'identifier',
			'catalog_id',
			'is_active',
	])]
	class Material extends Model
	{
		use SoftDeletes;
		protected function casts(): array
		{
			return [
					'grammage' => 'decimal:2',
					'thickness' => 'decimal:2',
					'format' => 'integer',
					'is_active' => 'boolean',
			];
		}

		/**
		 * Вычисляет идентификатор материала: код + граммаж (для бумаги)
		 * или толщина (для плёнки и фольги) + цифры формата.
		 * Возвращает null, если данных для вычисления недостаточно.
		 */
		public static function composeIdentifier(?string $code, $grammage, $thickness, $format): ?string
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