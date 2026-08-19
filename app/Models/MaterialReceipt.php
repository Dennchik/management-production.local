<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
			'material_id',
			'roll_id',
			'weight',
			'comment',
			'user_id',
	])]
	class MaterialReceipt extends Model
	{
		/**
		 * Материал, который был оприходован.
		 */
		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		/**
		 * Физический рулон, созданный при оприходовании.
		 */
		public function roll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class);
		}

		/**
		 * Пользователь, выполнивший операцию оприходования.
		 */
		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}
	}