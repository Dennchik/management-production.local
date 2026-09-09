<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;

	class ProductionOperationOutput extends Model
	{
		protected $fillable = [
				'operation_execution_id',
				'component_id',
				'material_id',
				'roll_id',
				'output_type',
				'roll_number',
				'weight',
				'comment',
		];

		protected $casts = [
				'weight' => 'decimal:3',
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