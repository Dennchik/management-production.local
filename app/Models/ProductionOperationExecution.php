<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class ProductionOperationExecution extends Model
	{
		protected $fillable = [
				'production_job_id',
				'route_step_id',
				'execution_number',
				'status',
				'started_at',
				'completed_at',
				'comment',
		];

		protected $casts = [
				'execution_number' => 'integer',
				'started_at' => 'datetime',
				'completed_at' => 'datetime',
		];

		public function productionJob(): BelongsTo
		{
			return $this->belongsTo(ProductionJob::class, 'production_job_id');
		}

		public function routeStep(): BelongsTo
		{
			return $this->belongsTo(ProductionRouteStep::class, 'route_step_id');
		}

		public function inputs(): HasMany
		{
			return $this->hasMany(
					ProductionOperationInput::class,
					'operation_execution_id'
			);
		}

		public function outputs(): HasMany
		{
			return $this->hasMany(
					ProductionOperationOutput::class,
					'operation_execution_id'
			);
		}
	}