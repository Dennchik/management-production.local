<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
		'material_id',
		'roll_id',
		'weight',
		'batch_id',
		'comment',
		'user_id',
	])]
	class MaterialIssue extends Model
	{
		/**
		 * Строки того же расходного ордера (включая эту).
		 */
		public function orderRows()
		{
			return static::query()
				->where('batch_id', $this->batch_id)
				->when($this->batch_id === null, fn ($query) => $query->where('id', $this->id))
				->orderBy('id')
				->get();
		}

		/**
		 * Материал, который был списан со склада.
		 */
		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class)->withTrashed();
		}

		/**
		 * Физический рулон, с которого был списан материал.
		 */
		public function roll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class)->withTrashed();
		}

		/**
		 * Пользователь, выполнивший списание.
		 */
		public function user(): BelongsTo
		{
			return $this->belongsTo(User::class);
		}
	}