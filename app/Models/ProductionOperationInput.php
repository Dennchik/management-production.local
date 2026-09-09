<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	class ProductionOperationInput extends Model
	{
		protected $fillable = [
				'operation_execution_id',
				'component_id',
				'material_id',
				'roll_id',
				'input_type',
				'planned_weight',
				'actual_weight',
				'waste_weight',
				'comment',
		];

		protected $casts = [
				'planned_weight' => 'decimal:3',
				'actual_weight' => 'decimal:3',
				'waste_weight' => 'decimal:3',
		];

		public function operationExecution(): BelongsTo
		{
			return $this->belongsTo(
					ProductionOperationExecution::class,
					'operation_execution_id'
			);
		}

		public function component(): BelongsTo
		{
			return $this->belongsTo(
					ProductionOperationComponent::class,
					'component_id'
			);
		}

		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class, 'material_id');
		}

		public function roll(): BelongsTo
		{
			return $this->belongsTo(MaterialRoll::class, 'roll_id');
		}
	}