<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
		'material_id',
		'roll_id',
		'weight_before',
		'adjustment',
		'weight_after',
		'comment',
		'user_id',
	])]
	class MaterialAdjustment extends Model
	{
		protected function casts(): array
		{
			return [
				'weight_before' => 'decimal:3',
				'adjustment' => 'decimal:3',
				'weight_after' => 'decimal:3',
			];
		}

		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		public function roll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class);
		}

		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}
	}
