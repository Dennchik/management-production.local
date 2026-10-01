@extends('layouts.app')

@section('title', 'Новая корректировка')

@section('content')

	@include('partials.message')

	<form class="issue-order" method="POST" action="{{ route('material-adjustments.store') }}" data-adjustment-order novalidate>
		<div class="issue-order__header">
			<h1 class="main-content__title">Ордер корректировки</h1>
		</div>
		@csrf

		<div class="issue-order__body">

			{{-- Табличная часть: позиции ордера --}}
			<div class="receipt-order__rolls">
				<div class="receipt-order__rolls-header">
					<label class="issue-order__label">Позиции</label>

					<button class="receipt-order__roll-add button" type="button" data-adjustment-row-add>
						<span>Добавить позицию</span>
					</button>
				</div>

				<table class="receipt-order__rolls-table">
					<thead>
						<tr>
							<th>Позиция (материал)</th>
							<th>Рулон</th>
							<th>Учётный остаток, кг</th>
							<th>Отклонение, кг</th>
							<th>Новый остаток, кг</th>
							<th></th>
						</tr>
					</thead>

					<tbody data-adjustment-rows data-old-rows='@json(old("rows"))'>
						{{-- Строки строит JS из шаблона ниже --}}
					</tbody>
				</table>
			</div>

			{{-- Комментарий --}}
			<div class="issue-order__line-textarea">
				<fieldset class="issue-order__field">
					<label class="issue-order__label" for="comment">Комментарий</label>

					<textarea class="issue-order__input issue-order__textarea" id="comment"
							name="comment">{{ old('comment') }}
					</textarea>
				</fieldset>
			</div>

			{{-- Действия --}}
			<div class="issue-order__actions">
				<button class="issue-order__button main-content__button button" type="submit">
					<span>Скорректировать</span>
				</button>

				<button class="issue-order__button issue-order__button--reset button" type="button">
					<span>Очистить</span>
				</button>
			</div>
		</div>
	</form>

	{{-- Шаблон строки табличной части; JS клонирует и переиндексирует rows[0] --}}
	<template data-adjustment-row-template>
		<tr data-adjustment-row>
			<td>
				<div data-select>
					<div class="select material-select">
						<input class="select__value" type="hidden"
								name="rows[0][material_id]" data-adjustment-material value="">

						<button class="material-select__select-button select__button select-button"
								type="button" aria-haspopup="listbox" aria-expanded="false">
							<span class="material-select__select-value select__button-text">Выберите материал</span>
							<span class="material-select__select-arrow" aria-hidden="true"></span>
						</button>

						<div class="select__dropdown material-select__select-list _collapse" role="listbox">
							<div class="material-select__select-search">
								<input class="material-select__select-search-input select__search"
										type="search" placeholder="Поиск материала..." autocomplete="off">
								<button class="material-select__select-search-clear select__search-clear"
										type="button" aria-label="Очистить поиск" hidden>
									<i class="icon icon-close" aria-hidden="true"></i>
								</button>
							</div>

							@foreach ($materials as $material)
								<button class="material-select__select-option select__item" type="button" role="option"
										data-value="{{ $material->id }}" data-name="{{ $material->name }}"
										data-search="{{ strtolower($material->name . ' ' . $material->thickness . ' ' . $material->grammage . ' ' . ($material->identifier ?? '')) }}"
										data-identifier="{{ $material->identifier }}"
										data-format="{{ $material->format }}"
										aria-selected="false">
									<span>{{ $material->name }}</span>
								</button>
							@endforeach

							<div class="material-select__select-empty select__empty" hidden>
								Ничего не найдено
							</div>
						</div>
					</div>
				</div>
			</td>

			<td>
				<div data-select>
					<div class="select material-select" data-adjustment-roll-select>
						<input class="select__value" type="hidden"
								name="rows[0][roll_id]" data-adjustment-roll value="">

						<button class="material-select__select-button select__button select-button"
								type="button" aria-haspopup="listbox" aria-expanded="false">
							<span class="material-select__select-value select__button-text">Сначала выберите материал</span>
							<span class="material-select__select-arrow" aria-hidden="true"></span>
						</button>

						<div class="select__dropdown material-select__select-list _collapse" role="listbox">
							{{-- Пункты рулонов подгружаются через JS --}}
							<div data-adjustment-rolls-list></div>

							<div class="material-select__select-empty select__empty" hidden>
								Нет доступных рулонов
							</div>
						</div>
					</div>
				</div>
			</td>

			<td>
				<input class="issue-order__input" type="text" readonly data-adjustment-weight-before>
			</td>

			<td>
				<input class="issue-order__input" type="number" step="0.001"
						name="rows[0][adjustment]" data-adjustment-input value="">
			</td>

			<td>
				<input class="issue-order__input" type="text" readonly data-adjustment-weight-after>
			</td>

			<td>
				<button class="receipt-order__roll-remove button" type="button"
						data-adjustment-row-remove aria-label="Удалить позицию">
					<span>Удалить</span>
				</button>
			</td>
		</tr>
	</template>

@endsection
