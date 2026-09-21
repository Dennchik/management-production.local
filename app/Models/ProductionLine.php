<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;

	#[Fillable([
			'name',
			'production_operation_id',
	])]
	class ProductionLine extends Model
	{
		/**
		 * Технологическая линия (шаблон), к которой относится линия.
		 */
		public function operation(): BelongsTo
		{
			return $this->belongsTo(ProductionOperation::class, 'production_operation_id');
		}

		/**
		 * Все материалы, обрабатываемые на линии.
		 * Материал всегда входит в состав с конкретным форматом.
		 */
		public function materials(): BelongsToMany
		{
			return $this->belongsToMany(
					Material::class,
					'production_line_material'
			)
				->withPivot('direction', 'format')
				->orderBy('id');
		}

		/**
		 * Входные материалы линии (сырьё).
		 */
		public function inputMaterials(): BelongsToMany
		{
			return $this->materials()->wherePivot('direction', 'input');
		}

		/**
		 * Выходные материалы линии (продукция).
		 */
		public function outputMaterials(): BelongsToMany
		{
			return $this->materials()->wherePivot('direction', 'output');
		}
	}
