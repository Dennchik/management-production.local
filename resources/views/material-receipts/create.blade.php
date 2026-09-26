@extends('layouts.app')

@section('title', 'Новое оприходование')

@section('content')

	@include('partials.message')

	<form class="receipt-order" method="POST" action="{{ route('material-receipts.store') }}">
		<div class="receipt-order__header">
			<h1 class="main-content__title">Приходный ордер</h1>
		</div>
		@csrf

		<div class="receipt-order__body">

			{{-- Режим учёта: рулонами или общим весом --}}
			<div class="receipt-order__line receipt-order__line--modes">
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label">Режим учёта</label>

					<div class="receipt-order__modes" data-receipt-modes>
						<button class="receipt-order__mode-button button" type="button"
								data-receipt-mode-button data-mode="rolls">
							<span>Учёт рулонами</span>
						</button>

						<button class="receipt-order__mode-button button" type="button"
								data-receipt-mode-button data-mode="total_weight">
							<span>Общий вес</span>
						</button>
					</div>

					<input type="hidden" name="mode" data-receipt-mode-input value="{{ old('mode', 'rolls') }}">
				</fieldset>
			</div>

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
								<span class="material-select__select-value select__button-text">Выберите материал</span>

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
									@php
										/*
										 * Один пункт на материал; форматы выбираются
										 * отдельным селектом после выбора материала.
										 * Форматы берутся из material_formats и не
										 * зависят от наличия рулонов.
										 */
										$materialFormats = $material->formatValues();
									@endphp

									<button class="material-select__select-option select__item"
											type="button" role="option" data-value="{{ $material->id }}"
											data-grammage="{{ $material->grammage }}" data-thickness="{{ $material->thickness }}"
											data-code="{{ $material->code }}" data-type="{{ $material->material_type }}"
											data-formats="{{ $materialFormats->toJson() }}"
											aria-selected="{{ old('material_id') == $material->id ? 'true' : 'false' }}">

										<span>{{ preg_replace('/\s*гр\.?\s*$/ui', '', $material->name) }}@if ($material->grammage)
												| {{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }} гр
											@endif @if ($material->thickness)
												| {{ $material->thickness }} мкм
											@endif</span>
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

				{{-- Идентификатор --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="identifier">Идентификатор</label>
					<input class="receipt-order__input" id="identifier" type="text" readonly>
				</fieldset>
			</div>

			{{-- Формат --}}
			<div class="receipt-order__line">
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="format_select">Формат</label>

					<div data-select>
						<div class="select material-select receipt-order" data-format-select>
							<input class="select__value" id="format" name="format"
									type="hidden" value="{{ old('format') }}">

							<button class="material-select__select-button select__button select-button"
									id="format_select" type="button" aria-haspopup="listbox" aria-expanded="false">
								<span class="material-select__select-value select__button-text">Сначала выберите материал</span>

								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse" role="listbox">
								<div class="material-select__select-search">
									<input class="material-select__select-search-input select__search"
											id="format_search" type="search"
											placeholder="Поиск формата..." autocomplete="off">

									<button class="material-select__select-search-clear select__search-clear"
											type="button" aria-label="Очистить поиск" hidden>
										<i class="icon icon-close" aria-hidden="true"></i>
									</button>
								</div>

								{{-- Пункты форматов рендерятся из JS по выбранному материалу --}}
								<div data-format-options></div>

								<button class="material-select__select-option select__item"
										type="button" role="option" data-value="__new__" data-format-new-option
										aria-selected="false">
									<span>Новый формат…</span>
								</button>

								<div class="material-select__select-empty select__empty" hidden>
									Сначала выберите материал
								</div>
							</div>
						</div>
					</div>
				</fieldset>

				{{-- Новый формат: свободный ввод --}}
				<fieldset class="receipt-order__field" data-format-new-field hidden>
					<label class="receipt-order__label" for="format_new">Новый формат, мм</label>
					<input class="receipt-order__input" id="format_new" data-format-new-input
							type="number" min="0" step="1" inputmode="numeric"
							value="{{ old('format') }}">
				</fieldset>
			</div>

			{{-- Рулоны --}}
			<div class="receipt-order__line">
				<div class="receipt-order__rolls">
					<div class="receipt-order__rolls-header">
						<h2 class="receipt-order__rolls-title" data-receipt-rolls-title> Рулоны </h2>

						<button class="receipt-order__roll-add button" type="button" data-receipt-roll-add>
							<span>Добавить рулон</span>
						</button>
					</div>

					<p class="receipt-order__rolls-hint" data-receipt-total-hint hidden>
						Весь указанный вес будет приходован на рулон «Общий вес» выбранного материала и формата.
					</p>

					<table class="receipt-order__rolls-table">
						<thead>
							<tr>
								<th data-receipt-roll-number-column>Номер рулона</th>
								<th>Вес, кг</th>
								<th></th>
							</tr>
						</thead>
						<tbody data-receipt-rolls>

							@php
								$oldRolls = old('rolls', [
									 [
										  'roll_number' => '',
										  'weight' => '',
									 ],
								]);
							@endphp

							@foreach ($oldRolls as $index => $roll)

								<tr data-receipt-roll>

									<td data-receipt-roll-number-field>
										<input class="receipt-order__input" id="roll_number_{{ $index }}"
												name="rolls[{{ $index }}][roll_number]" data-receipt-roll-number type="text"
												value="{{ $roll['roll_number'] ?? '' }}"
												aria-label="Номер рулона">
									</td>

									<td>
										<input class="receipt-order__input" id="weight_{{ $index }}"
												name="rolls[{{ $index }}][weight]" data-receipt-roll-weight
												type="number" step="0.001" min="0" value="{{ $roll['weight'] ?? '' }}"
												aria-label="Вес, кг">
									</td>

									<td>
										<button class="receipt-order__roll-remove button" type="button"
												data-receipt-roll-remove aria-label="Удалить рулон" hidden>
											<span>Удалить</span>
										</button>
									</td>
								</tr>

							@endforeach
						</tbody>
					</table>
				</div>
			</div>


			{{-- Комментарий --}}
			<div class="receipt-order__line">
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="comment"> Комментарий </label>
					<textarea class="receipt-order__input receipt-order__textarea"
							id="comment"
							name="comment">{{ old('comment') }}</textarea>
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
