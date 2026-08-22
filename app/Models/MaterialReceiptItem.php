<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
		'material_receipt_id',
		'material_id',
		'roll_id',
		'weight',
	])]
	class MaterialReceiptItem extends Model
	{
		/**
		 * Приходный ордер, к которому относится позиция.
		 */
		public function receipt(): BelongsTo
		{
			return $this->belongsTo(MaterialReceipt::class, 'material_receipt_id');
		}

		/**
		 * Материал позиции.
		 */
		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		/**
		 * Физический рулон позиции.
		 */
		public function roll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class, 'roll_id');
		}
	}