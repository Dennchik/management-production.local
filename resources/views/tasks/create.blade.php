@extends('layouts.app')

@section('title', 'Новая производственная задача')

@section('content')
	<form class="issue-order" method="POST" action="{{ route('tasks.store') }}" data-order-form>
		<div class="issue-order__header">
			<h1 class="main-content__title">Новая производственная задача</h1>
		</div>
		@csrf

		<div class="issue-order__body">
			<div class="issue-order__line">
				<fieldset class="issue-order__field">
					<label class="issue-order__label" for="order_id">Заказ (необязательно)</label>
					<select class="catalogs__parent-select" id="order_id" name="order_id">
						<option value="">— Без заказа —</option>
						@foreach ($orders as $order)
							<option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
								№{{ $order->id }} — {{ $order->client_name }}
							</option>
						@endforeach
					</select>
				</fieldset>

				<fieldset class="issue-order__field">
					<label class="issue-order__label" for="machine_id">Станок</label>
					<select class="catalogs__parent-select" id="machine_id" name="machine_id">
						<option value="">— Не назначен —</option>
						@foreach ($machines as $machine)
							<option value="{{ $machine->id }}" {{ old('machine_id') == $machine->id ? 'selected' : '' }}>
								{{ $machine->name }}
							</option>
						@endforeach
					</select>
				</fieldset>
			</div>

			<div class="issue-order__line">
				<fieldset class="issue-order__field">
					<label class="issue-order__label">Материал (продукция)</label>

					<div data-select>
						<div class="select material-select">
							<input class="select__value" id="material_id" name="material_id" type="hidden"
									value="{{ old('material_id') }}">

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
				</fieldset>

				<fieldset class="issue-order__field">
					<label class="issue-order__label" for="quantity">Количество, кг</label>
					<input class="issue-order__input" id="quantity" name="quantity" type="number"
							step="0.001" min="0.001" value="{{ old('quantity') }}" required>
				</fieldset>
			</div>

			<div class="issue-order__line-textarea">
				<fieldset class="issue-order__field">
					<label class="issue-order__label" for="comment">Комментарий</label>
					<textarea class="issue-order__input issue-order__textarea" id="comment"
							name="comment">{{ old('comment') }}</textarea>
				</fieldset>
			</div>

			<div class="issue-order__actions">
				<button class="issue-order__button main-content__button button" type="submit">
					<span>Создать задачу</span>
				</button>

				<a class="issue-order__button issue-order__button--reset button" href="{{ route('tasks.index') }}">
					<span>Отмена</span>
				</a>
			</div>
		</div>
	</form>
@endsection
