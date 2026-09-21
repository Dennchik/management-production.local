<?php

	namespace App\Models;

	use Illuminate\Database\Eloquent\Attributes\Fillable;
	use Illuminate\Database\Eloquent\Builder;
	use Illuminate\Database\Eloquent\Model;
	use Illuminate\Database\Eloquent\Relations\HasMany;
	use Illuminate\Support\Facades\DB;

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

		/**
		 * Статусы закрытых заказов: по ним нельзя создавать задачи.
		 */
		public const CLOSED_STATUSES = ['done', 'cancelled'];

		/**
		 * Заказы, по которым ещё можно создавать производственные задачи.
		 */
		public function scopeOpen(Builder $query): void
		{
			$query->whereNotIn('status', static::CLOSED_STATUSES);
		}

		public function isClosed(): bool
		{
			return in_array($this->status, static::CLOSED_STATUSES, true);
		}

		/**
		 * Номер, который получит следующий созданный заказ.
		 * Значение последовательности читается без её сдвига.
		 */
		public static function nextNumber(): int
		{
			$table = (new static())->getTable();

			if (DB::connection()->getDriverName() === 'pgsql') {
				$sequence = DB::selectOne('SELECT pg_get_serial_sequence(?, ?) AS name', [$table, 'id'])?->name;

				if ($sequence !== null) {
					$state = DB::selectOne("SELECT last_value, is_called FROM {$sequence}");

					return $state->is_called ? (int) $state->last_value + 1 : (int) $state->last_value;
				}
			}

			return (int) static::query()->max('id') + 1;
		}

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
