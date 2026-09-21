<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
		'material_id',
		'roll_number',
		'weight',
		'format',
		'identifier',
	])]
	class MaterialRoll extends Model
	{
		protected function casts(): array
		{
			return [
				'format' => 'integer',
			];
		}

		/**
		 * Вычисляет идентификатор рулона: код материала + граммаж (для бумаги)
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
		 * Тип материала, к которому относится физический рулон.
		 */
		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		/**
		 * Позиции приходных ордеров,
		 * связанные с этим рулоном.
		 */
		public function receiptItems(): HasMany
		{
			return $this->hasMany(
				MaterialReceiptItem::class,
				'roll_id'
			);
		}

		/**
		 * Операции расхода,
		 * связанные с этим рулоном.
		 */
		public function issues(): HasMany
		{
			return $this->hasMany(
				MaterialIssue::class,
				'roll_id'
			);
		}
	}