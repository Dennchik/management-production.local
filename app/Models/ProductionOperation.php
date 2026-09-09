<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class ProductionOperation extends Model
	{
		protected $fillable = [
				'name',
				'code',
				'description',
				'is_active',
		];

		protected $casts = [
				'is_active' => 'boolean',
		];

		public function routeSteps(): HasMany
		{
			return $this->hasMany(ProductionRouteStep::class, 'operation_id');
		}

		public function components(): HasMany
		{
			return $this->hasMany(ProductionOperationComponent::class, 'operation_id')
					->orderBy('direction')
					->orderBy('sort_order');
		}
	}