<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
			'lamination_order_id',
			'pf_roll_number',
			'paper_roll_id',
			'paper_weight',
			'foil_roll_id',
			'foil_weight',
			'paper_removed_weight',
			'foil_removed_weight',
			'pf_weight',
			'comment',
	])]
	class LaminationOrderItem extends Model
	{
		/**
		 * Задание на ламинацию.
		 */
		public function laminationOrder(): BelongsTo
		{
			return $this->belongsTo(LaminationOrder::class);
		}

		/**
		 * Физический рулон бумаги.
		 */
		public function paperRoll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class, 'paper_roll_id');
		}

		/**
		 * Физический рулон фольги.
		 */
		public function foilRoll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class, 'foil_roll_id');
		}
	}