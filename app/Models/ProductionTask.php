<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\BelongsTo;
	use Illuminate\Database\Eloquent\Relations\BelongsToMany;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
			'order_id',
			'material_id',
			'production_line_id',
			'number',
			'quantity',
			'operator_id',
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

		/**
		 * Задачу можно редактировать, пока она не завершена и не отменена.
		 */
		public function isEditable(): bool
		{
			return in_array($this->status, ['pending', 'in_progress'], true);
		}

		public function order(): BelongsTo
		{
			return $this->belongsTo(Order::class);
		}

		public function material(): BelongsTo
		{
			return $this->belongsTo(Material::class);
		}

		/**
		 * Шаблон производства, по которому создана задача.
		 */
		public function productionLine(): BelongsTo
		{
			return $this->belongsTo(ProductionLine::class);
		}

	public function operator(): BelongsTo
	{
		return $this->belongsTo(User::class, 'operator_id');
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

		/**
		 * Материалы этой задачи (входы и выход) — состав
		 * копируется из шаблона, но может быть изменён для задачи.
		 * Материал всегда входит с конкретным форматом.
		 */
		public function materials(): BelongsToMany
		{
			return $this->belongsToMany(
				Material::class,
				'production_task_material',
				'task_id',
				'material_id'
			)
				->withPivot('direction', 'format')
				->orderBy('id');
		}

		public function inputMaterials(): BelongsToMany
		{
			return $this->materials()->wherePivot('direction', 'input');
		}

		public function outputMaterials(): BelongsToMany
		{
			return $this->materials()->wherePivot('direction', 'output');
		}

	public function statusLabel(): string
	{
		// Выполнена с недобором — «Завершена», чтобы отличать от полноценного выполнения
		if ($this->status === 'done' && $this->isShort()) {
			return 'Завершена';
		}

		return static::STATUSES[$this->status] ?? $this->status;
	}

	/**
	 * Модификатор чипа статуса: серый / жёлтый / зелёный / красный.
	 * Выполненная с недобором — светло-красная, чтобы отличать от отменённой.
	 */
	public function statusClass(): string
	{
		return match ($this->status) {
			'in_progress' => 'yellow',
			'done' => $this->isShort() ? 'red-soft' : 'green',
			'cancelled' => 'red',
			default => 'gray',
		};
	}

	/**
	 * Задача завершена с недобором планового веса.
	 * В списках используется алиас produced_weight (withSum), чтобы не грузить выходы.
	 */
	public function isShort(): bool
	{
		return $this->status === 'done'
				&& (float) ($this->produced_weight ?? $this->producedWeight()) < (float) $this->quantity;
	}

	/**
	 * Сколько продукции уже произведено, кг.
	 */
	public function producedWeight(): float
	{
		return round((float) $this->outputs->sum('actual_weight'), 3);
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
