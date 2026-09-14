<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
			'order_id',
			'material_id',
			'quantity',
			'machine_id',
			'status',
			'started_at',
			'completed_at',
			'created_by',
			'comment',
	])]
	class ProductionTask extends Model
	{
		public const STATUSES = [
				'pending' => 'Ожидает',
				'in_progress' => 'В работе',
				'done' => 'Выполнена',
				'cancelled' => 'Отменена',
		];

		protected function casts(): array
		{
			return [
					'quantity' => 'decimal:3',
					'started_at' => 'datetime',
					'completed_at' => 'datetime',
			];
		}

		public function order(): BelongsTo
		{
			return $this->belongsTo(Order::class);
		}

		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		public function machine(): BelongsTo
		{
			return $this->belongsTo(Machine::class);
		}

		public function author(): BelongsTo
		{
			return $this->belongsTo(User::class, 'created_by');
		}

		public function inputs(): HasMany
		{
			return $this->hasMany(ProductionTaskInput::class, 'task_id');
		}

		public function outputs(): HasMany
		{
			return $this->hasMany(ProductionTaskOutput::class, 'task_id');
		}

		public function statusLabel(): string
		{
			return static::STATUSES[$this->status] ?? $this->status;
		}

		/**
		 * Входные материалы по рецепту операции, выпускающей материал задачи.
		 */
		public function recipeInputs(): \Illuminate\Support\Collection
		{
			$materialId = $this->material_id;

			return ProductionOperation::query()
					->where('is_active', true)
					->whereHas('components', static function ($query) use ($materialId) {
						$query->where('direction', 'output')
								->where('material_id', $materialId);
					})
					->with(['components' => static function ($query) {
						$query->where('direction', 'input')->orderBy('sort_order');
					}])
					->get()
					->flatMap(static fn ($operation) => $operation->components);
		}
	}
