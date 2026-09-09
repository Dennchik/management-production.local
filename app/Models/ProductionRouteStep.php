<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class ProductionRouteStep extends Model
	{
		protected $fillable = [
				'route_id',
				'operation_id',
				'step_number',
				'name',
				'description',
				'is_required',
		];

		protected $casts = [
				'step_number' => 'integer',
				'is_required' => 'boolean',
		];

		public function route(): BelongsTo
		{
			return $this->belongsTo(ProductionRoute::class, 'route_id');
		}

		public function operation(): BelongsTo
		{
			return $this->belongsTo(ProductionOperation::class, 'operation_id');
		}

		public function executions(): HasMany
		{
			return $this->hasMany(
					ProductionOperationExecution::class,
					'route_step_id'
			);
		}
	}