{{--
	Поля формы заказа (создание и редактирование).
	Переменные: $order (Order|null), $products, $submitLabel, $cancelUrl.
--}}
@php
	$order = $order ?? null;

	$defaultItems = $order
			? $order->items->map(static fn ($item) => [
					'material_id' => $item->material_id,
					'quantity' => rtrim(rtrim((string) $item->quantity, '0'), '.'),
			])->all()
			: [];

	$formItems = old('items', $defaultItems ?: [['material_id' => '', 'quantity' => '']]);
@endphp

<div class="issue-order__body">
	{{-- Шапка заказа --}}
	<div class="issue-order__line">
		<fieldset class="issue-order__field">
			<label class="issue-order__label" for="client_name">Клиент</label>
			<input class="issue-order__input" id="client_name" name="client_name" type="text"
					value="{{ old('client_name', $order?->client_name) }}" required>
		</fieldset>

		<fieldset class="issue-order__field">
			<label class="issue-order__label" for="address">Адрес</label>
			<input class="issue-order__input" id="address" name="address" type="text"
					value="{{ old('address', $order?->address) }}">
		</fieldset>
	</div>

	<div class="issue-order__line-textarea">
		<fieldset class="issue-order__field">
			<label class="issue-order__label" for="comment">Комментарий</label>
			<textarea class="issue-order__input issue-order__textarea" id="comment"
					name="comment">{{ old('comment', $order?->comment) }}</textarea>
		</fieldset>
	</div>

	{{-- Позиции заказа --}}
	<div class="issue-order__line">
		<fieldset class="issue-order__field" style="flex: 1;">

			<div data-order-items>
				@foreach ($formItems as $index => $item)
					<div class="issue-order__line" data-order-item>
						<fieldset class="issue-order__field">
							<label class="issue-order__label">Материал</label>
							<div data-select>
								<div class="select material-select">
									<input class="select__value" name="items[{{ $index }}][material_id]" type="hidden"
											value="{{ $item['material_id'] ?? '' }}">

									<button class="material-select__select-button select__button select-button"
											type="button" aria-haspopup="listbox" aria-expanded="false">
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
												<span>
													{{ $product->name }}
													@if ($product->grammage) | {{ $product->grammage }} гр @endif
													@if ($product->thickness) | {{ $product->thickness }} мкм @endif
												</span>
											</button>
										@endforeach

										<div class="material-select__select-empty select__empty" hidden>
											Ничего не найдено
										</div>
									</div>
								</div>
							</div>
						</fieldset>

						<fieldset class="issue-order__field">
							<label class="issue-order__label">Количество, кг</label>
							<input class="issue-order__input" type="number" name="items[{{ $index }}][quantity]"
									step="0.001" min="0.001" value="{{ $item['quantity'] ?? '' }}">
						</fieldset>

						<button class="button button--danger" type="button" data-order-item-remove
								@if (count($formItems) <= 1) hidden @endif>Удалить</button>
					</div>
				@endforeach
			</div>

			<button class="button button--secondary" type="button" data-order-item-add>
				+ Добавить позицию
			</button>
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
