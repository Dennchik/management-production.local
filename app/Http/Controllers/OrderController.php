<?php

	namespace App\Http\Controllers;

	use App\Models\Material;
	use App\Models\Order;
	use App\Models\OrderItem;
	use App\Models\ProductionTask;
	use Illuminate\Http\Request;
	use Illuminate\Support\Facades\DB;
	use Illuminate\View\View;

	class OrderController extends Controller
	{
		public function index(): View
		{
			$orders = Order::query()
					->with('items.material')
					->orderByDesc('id')
					->get();

			return view('orders.index', [
					'orders' => $orders,
					'statuses' => Order::STATUSES,
			]);
		}

		public function create(): View
		{
			return view('orders.create', [
					'products' => $this->productOptions(),
			]);
		}

		public function store(Request $request)
		{
			$validated = $this->validateOrder($request);

			$order = DB::transaction(static function () use ($validated) {
				$order = Order::create([
						'client_name' => $validated['client_name'],
						'address' => $validated['address'] ?? null,
						'comment' => $validated['comment'] ?? null,
				]);

				foreach ($validated['items'] as $index => $item) {
					$order->items()->create($item + ['sort_order' => $index]);
				}

				return $order;
			});

			return redirect()
					->route('orders.show', $order)
					->with('success', 'Заказ создан.');
		}

		public function show(Order $order): View
		{
			return view('orders.show', [
					'order' => $order->load('items.material'),
					'recipes' => $order->productionRecipes(),
					'statuses' => Order::STATUSES,
					'tasks' => ProductionTask::query()
							->where('order_id', $order->id)
							->with(['material', 'machine'])
							->orderBy('id')
							->get(),
			]);
		}

		public function updateStatus(Request $request, Order $order)
		{
			$validated = $request->validate([
					'status' => ['required', 'in:' . implode(',', array_keys(Order::STATUSES))],
			]);

			$order->update(['status' => $validated['status']]);

			return redirect()
					->route('orders.show', $order)
					->with('success', 'Статус заказа обновлён.');
		}

		/**
		 * Создаёт производственные задачи по позициям заказа.
		 */
		public function createTasks(Order $order)
		{
			$existing = ProductionTask::query()
					->where('order_id', $order->id)
					->pluck('material_id');

			$created = 0;

			foreach ($order->items as $item) {
				if ($existing->contains($item->material_id)) {
					continue;
				}

				ProductionTask::create([
						'order_id' => $order->id,
						'material_id' => $item->material_id,
						'quantity' => $item->quantity,
						'status' => 'pending',
						'created_by' => auth()->id(),
						'comment' => 'Заказ №' . $order->id,
				]);

				$created++;
			}

			return redirect()
					->route('orders.show', $order)
					->with('success', $created > 0
							? "Создано задач: {$created}."
							: 'Задачи по этому заказу уже существуют.');
		}

		/**
		 * Материалы типа «Продукция» для выбора в заказе.
		 */
		private function productOptions(): \Illuminate\Support\Collection
		{
			return Material::query()
					->where('material_type', 'product')
					->where('is_active', true)
					->orderBy('name')
					->get();
		}

		private function validateOrder(Request $request): array
		{
			return $request->validate([
					'client_name' => ['required', 'string', 'max:255'],
					'address' => ['nullable', 'string', 'max:500'],
					'comment' => ['nullable', 'string'],
					'items' => ['required', 'array', 'min:1'],
					'items.*.material_id' => ['required', 'integer', 'exists:materials,id'],
					'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
					'items.*.unit' => ['nullable', 'string', 'max:20'],
			]);
		}
	}
