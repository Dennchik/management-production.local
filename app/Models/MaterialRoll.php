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
	])]
	class MaterialRoll extends Model
	{
		/**
		 * Тип материала, к которому относится физический рулон.
		 */
		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		/**
		 * Операции оприходования, связанные с этим рулоном.
		 */
		public function receipts(): HasMany
		{
			return $this->hasMany(MaterialReceipt::class, 'roll_id');
		}
	}