<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	#[Fillable([
			'task_id',
			'material_id',
			'roll_id',
			'actual_weight',
	])]
	class ProductionTaskInput extends Model
	{
		protected function casts(): array
		{
			return [
					'actual_weight' => 'decimal:3',
			];
		}

		public function task(): BelongsTo
		{
			return $this->belongsTo(ProductionTask::class, 'task_id');
		}

		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		public function roll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class, 'roll_id');
		}
	}
