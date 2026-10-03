<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
			'order_id',
			'material_id',
			'quantity',
			'unit',
			'sort_order',
	])]
	class OrderItem extends Model
	{
		protected function casts(): array
		{
			return [
					'quantity' => 'decimal:3',
			];
		}

		public function order(): BelongsTo
		{
			return $this->belongsTo(Order::class);
		}

		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}
	}
