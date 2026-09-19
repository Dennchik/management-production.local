<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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

		/**
		 * Материалы, для которых линия разрешена.
		 */
		public function materials(): BelongsToMany
		{
			return $this->belongsToMany(
					Material::class,
					'material_production_operation'
			);
		}

		/**
		 * Производственные линии, относящиеся к этому шаблону.
		 */
		public function productionLines(): HasMany
		{
			return $this->hasMany(ProductionLine::class, 'production_operation_id')->orderBy('id');
		}

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