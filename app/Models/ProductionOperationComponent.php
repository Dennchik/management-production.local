<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	class ProductionOperationComponent extends Model
	{
		protected $fillable = [
				'operation_id',
				'material_id',
				'direction',
				'quantity',
				'unit',
				'is_required',
				'sort_order',
				'comment',
		];

		protected $casts = [
				'quantity' => 'decimal:3',
				'is_required' => 'boolean',
				'sort_order' => 'integer',
		];

		public function operation(): BelongsTo
		{
			return $this->belongsTo(ProductionOperation::class, 'operation_id');
		}

		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class, 'material_id');
		}

		public function inputs(): HasMany
		{
			return $this->hasMany(
					ProductionOperationInput::class,
					'component_id'
			);
		}

		public function outputs(): HasMany
		{
			return $this->hasMany(
					ProductionOperationOutput::class,
					'component_id'
			);
		}
	}