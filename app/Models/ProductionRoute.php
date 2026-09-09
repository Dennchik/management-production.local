<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class ProductionRoute extends Model
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

		public function steps(): HasMany
		{
			return $this->hasMany(ProductionRouteStep::class, 'route_id')
					->orderBy('step_number');
		}

		public function jobs(): HasMany
		{
			return $this->hasMany(ProductionJob::class, 'route_id');
		}
	}