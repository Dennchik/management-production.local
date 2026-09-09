<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class ProductionJob extends Model
	{
		protected $fillable = [
				'job_number',
				'route_id',
				'product_name',
				'planned_weight',
				'status',
				'comment',
		];

		protected $casts = [
				'planned_weight' => 'decimal:3',
		];

		public function route(): BelongsTo
		{
			return $this->belongsTo(ProductionRoute::class, 'route_id');
		}

		public function executions(): HasMany
		{
			return $this->hasMany(
					ProductionOperationExecution::class,
					'production_job_id'
			);
		}
	}