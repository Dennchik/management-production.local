{{--
	Поля формы производственной задачи (создание и редактирование).
	Переменные: $task (ProductionTask|null), $orders, $products, $machines, $submitLabel, $cancelUrl.
	У начатой задачи заказ и продукцию менять нельзя — они выводятся только для чтения.
--}}
@php
	$task = $task ?? null;
	$lockProduct = $task !== null && $task->status !== 'pending';
@endphp

<div class="issue-order__body">
	<div class="issue-order__line">
		<fieldset class="issue-order__field">
			<label class="issue-order__label" for="order_id">Заказ (необязательно)</label>

			@if ($lockProduct)
				<input class="issue-order__input" id="order_id" type="text" disabled
						value="{{ $task->order_id !== null ? '№' . $task->order_id . ' — ' . $task->order?->client_name : '— Без заказа —' }}">
			@else
				<select class="catalogs__parent-select" id="order_id" name="order_id" data-task-order>
					<option value="">— Без заказа —</option>
					@foreach ($orders as $order)
						<option value="{{ $order->id }}" {{ old('order_id', $task?->order_id) == $order->id ? 'selected' : '' }}
								data-materials='@json($order->items->pluck('material_id')->unique()->values())'>
							№{{ $order->id }} — {{ $order->client_name }}
						</option>
					@endforeach
				</select>
			@endif
		</fieldset>

		<fieldset class="issue-order__field">
			<label class="issue-order__label" for="machine_id">Станок</label>
			<select class="catalogs__parent-select" id="machine_id" name="machine_id">
				<option value="">— Не назначен —</option>
				@foreach ($machines as $machine)
					<option value="{{ $machine->id }}" {{ old('machine_id', $task?->machine_id) == $machine->id ? 'selected' : '' }}>
						{{ $machine->name }}
					</option>
				@endforeach
			</select>
		</fieldset>
	</div>

	<div class="issue-order__line">
		<fieldset class="issue-order__field">
			<label class="issue-order__label">Материал (продукция)</label>

			@if ($lockProduct)
				<input class="issue-order__input" type="text" disabled value="{{ $task->material?->name }}">
			@else
				<div data-select>
					<div class="select material-select" data-task-product-select>
						<input class="select__value" id="material_id" name="material_id" type="hidden"
								value="{{ old('material_id', $task?->material_id) }}">

						<button class="material-select__select-button select__button select-button" type="button"
								aria-haspopup="listbox" aria-expanded="false">
							<span class="material-select__select-value select__button-text">
								Выберите материал
							</span>
							<span class="material-select__select-arrow" aria-hidden="true"></span>
						</button>

						<div class="select__dropdown material-select__select-list _collapse" role="listbox">
							<div class="material-select__select-search">
								<input class="material-select__select-search-input select__search" type="search"
										placeholder="Поиск материала..." autocomplete="off">
								<button class="material-select__select-search-clear select__search-clear" type="button"
										aria-label="Очистить поиск" hidden>
									<i class="icon icon-close" aria-hidden="true"></i>
								</button>
							</div>

							@foreach ($products as $product)
								<button class="material-select__select-option select__item" type="button" role="option"
										data-value="{{ $product->id }}"
										data-search="{{ strtolower($product->name . ' ' . $product->identifier) }}"
										aria-selected="false">
									<span>{{ $product->name }}</span>
								</button>
							@endforeach

							<div class="material-select__select-empty select__empty" hidden>Ничего не найдено</div>
						</div>
					</div>
				</div>
			@endif
		</fieldset>

		<fieldset class="issue-order__field">
			<label class="issue-order__label" for="quantity">Количество, кг</label>
			<input class="issue-order__input" id="quantity" name="quantity" type="number"
					step="0.001" min="0.001"
					value="{{ old('quantity', $task ? rtrim(rtrim((string) $task->quantity, '0'), '.') : null) }}" required>
		</fieldset>
	</div>

	<div class="issue-order__line-textarea">
		<fieldset class="issue-order__field">
			<label class="issue-order__label" for="comment">Комментарий</label>
			<textarea class="issue-order__input issue-order__textarea" id="comment"
					name="comment">{{ old('comment', $task?->comment) }}</textarea>
		</fieldset>
	</div>

	<div class="issue-order__actions">
		<button class="issue-order__button main-content__button button" type="submit">
			<span>{{ $submitLabel }}</span>
		</button>

		<a class="issue-order__button issue-order__button--reset button" href="{{ $cancelUrl }}">
			<span>Отмена</span>
		</a>
	</div>
</div>
