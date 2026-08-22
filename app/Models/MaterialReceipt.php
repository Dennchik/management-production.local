<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
		'comment',
		'user_id',
	])]
	class MaterialReceipt extends Model
	{
		/**
		 * Позиции приходного ордера.
		 *
		 * Один приходный ордер может содержать несколько физических рулонов.
		 */
		public function items(): HasMany
		{
			return $this->hasMany(MaterialReceiptItem::class);
		}

		/**
		 * Пользователь, выполнивший оприходование.
		 */
		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}
	}