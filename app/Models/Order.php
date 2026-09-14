<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasMany;

	#[Fillable([
			'client_name',
			'address',
			'status',
			'comment',
	])]
	class Order extends Model
	{
		public const STATUSES = [
				'new' => 'Новый',
				'in_production' => 'В производстве',
				'done' => 'Выполнен',
				'cancelled' => 'Отменён',
		];

		public function statusLabel(): string
		{
			return static::STATUSES[$this->status] ?? $this->status;
		}

		public function items(): HasMany
		{
			return $this->hasMany(OrderItem::class)
					->orderBy('sort_order')
					->orderBy('id');
		}

		/**
		 * Операции, выпускающие материалы заказа (рецепты производства).
		 */
		public function productionRecipes(): \Illuminate\Support\Collection
		{
			$materialIds = $this->items->pluck('material_id');

			return ProductionOperation::query()
					->where('is_active', true)
					->whereHas('components', static function ($query) use ($materialIds) {
						$query->where('direction', 'output')
								->whereIn('material_id', $materialIds);
					})
					->with(['components' => static function ($query) {
						$query->orderBy('sort_order');
					}])
					->get();
		}
	}
