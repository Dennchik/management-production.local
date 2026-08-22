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
	class MaterialIssue extends Model
	{
		/**
		 * Материал, который был списан со склада.
		 */
		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		/**
		 * Физический рулон, с которого был списан материал.
		 */
		public function roll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class);
		}

		/**
		 * Пользователь, выполнивший списание.
		 */
		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}
	}