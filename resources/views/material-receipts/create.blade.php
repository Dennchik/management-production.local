@extends('layouts.app')

@section('title', 'Новое оприходование')

@section('content')

	@include('partials.message')

	<h1 class="main-content__title">Приходный ордер</h1>

	<form class="receipt-order" method="POST" action="{{ route('material-receipts.store') }}">

		@csrf

		<div class="receipt-order__body">

			{{-- Материал --}}
			<div class="receipt-order__line">
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="material_select">Материал</label>

					<div data-select>
						<div class="select material-select receipt-order">
							<input class="select__value" id="material_id" name="material_id"
									type="hidden" value="{{ old('material_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="material_select" type="button" aria-haspopup="listbox" aria-expanded="false">
								<span class="material-select__select-value select__button-text">
								Выберите материал
								</span>

								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse" role="listbox">
								<div class="material-select__select-search">
									<input class="material-select__select-search-input select__search"
											id="material_search" type="search"
											placeholder="Поиск материала..." autocomplete="off">

									<button class="material-select__select-search-clear select__search-clear"
											type="button" aria-label="Очистить поиск" hidden>
										<i class="icon icon-close" aria-hidden="true"></i>
									</button>
								</div>

								@foreach ($materials as $material)

									<button class="material-select__select-option select__item"
											type="button" role="option" data-value="{{ $material->id }}"
											data-grammage="{{ $material->grammage }}" data-thickness="{{ $material->thickness }}"
											data-format="{{ $material->format }}" data-identifier="{{ $material->identifier }}"
											aria-selected="{{ old('material_id') == $material->id ? 'true' : 'false' }}">

										<span>
										  {{ preg_replace('/\s*гр\.?\s*$/ui', '', $material->name) }}
											@if ($material->grammage)
												|
												{{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }}
												гр
											@endif

											@if ($material->thickness)
												| {{ $material->thickness }} мкм
											@endif

										  | {{ $material->format }}
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

				{{-- Граммаж --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="grammage">Граммаж</label>
					<input class="receipt-order__input" id="grammage" type="text" readonly>
				</fieldset>

				{{-- Толщина --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="thickness">Толщина</label>
					<input class="receipt-order__input" id="thickness" type="text" readonly>
				</fieldset>

				{{-- Формат --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="format">Формат</label>
					<input class="receipt-order__input" id="format" type="text" readonly>
				</fieldset>

				{{-- Идентификатор --}}
				<fieldset class="receipt-order__field">

					<label class="receipt-order__label" for="identifier">Идентификатор</label>

					<input class="receipt-order__input" id="identifier" type="text" readonly>
				</fieldset>
			</div>

			{{-- Рулоны --}}
			<div class="receipt-order__line">
				<div class="receipt-order__rolls">
					<div class="receipt-order__rolls-header">
						<h2 class="receipt-order__rolls-title"> Рулоны </h2>

						<button class="receipt-order__roll-add button" type="button" data-receipt-roll-add>
							<span>Добавить рулон</span>
						</button>
					</div>

					<div class="receipt-order__rolls-list" data-receipt-rolls>

						@php
							$oldRolls = old('rolls', [
								 [
									  'roll_number' => '',
									  'weight' => '',
								 ],
							]);
						@endphp

						@foreach ($oldRolls as $index => $roll)

							<div class="receipt-order__roll" data-receipt-roll>

								<fieldset class="receipt-order__field">
									<label class="receipt-order__label" for="roll_number_{{ $index }}"
											data-receipt-roll-number-label>
										Номер рулона
									</label>

									<input class="receipt-order__input" id="roll_number_{{ $index }}"
											name="rolls[{{ $index }}][roll_number]" data-receipt-roll-number type="text"
											value="{{ $roll['roll_number'] ?? '' }}">

								</fieldset>

								<fieldset class="receipt-order__field">
									<label class="receipt-order__label" for="weight_{{ $index }}" data-receipt-roll-weight-label>
										Вес, кг
									</label>

									<input class="receipt-order__input" id="weight_{{ $index }}"
											name="rolls[{{ $index }}][weight]" data-receipt-roll-weight
											type="number" step="0.001" min="0" value="{{ $roll['weight'] ?? '' }}">

								</fieldset>
							</div>

						@endforeach
					</div>
				</div>
			</div>


			{{-- Комментарий --}}
			<div class="receipt-order__line">
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="comment"> Комментарий </label>
					<textarea class="receipt-order__input receipt-order__textarea" id="comment" name="comment">
						{{ old('comment') }}
					</textarea>
				</fieldset>
			</div>

			{{-- Действия --}}
			<div class="receipt-order__actions">
				<button class="receipt-order__button main-content__button button" type="submit">
					<span>Оприходовать</span>
				</button>

				<button class="receipt-order__button receipt-order__button--reset button"
						type="button" data-receipt-form-reset>
					<span>Очистить</span>
				</button>
			</div>
		</div>
	</form>

@endsection