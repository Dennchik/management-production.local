@extends('layouts.app')

@section('title', 'Новый расход')

@section('content')

	@include('partials.message')


	<form class="issue-order" method="POST" action="{{ route('material-issues.store') }}" data-issue-order novalidate>
		<div class="issue-order__header">
			<h1 class="main-content__title">Расходный ордер</h1>
		</div>
		@csrf

		<div class="issue-order__body">

			{{-- Табличная часть: позиции ордера --}}
			<div class="receipt-order__rolls">
				<div class="receipt-order__rolls-header">
					<label class="issue-order__label">Позиции</label>

					<button class="receipt-order__roll-add button" type="button" data-issue-row-add>
						<span>Добавить позицию</span>
					</button>
				</div>

				<table class="receipt-order__rolls-table">
					<thead>
						<tr>
							<th>Материал</th>
							<th>Рулон</th>
							<th>Остаток, кг</th>
							<th>Вес расхода, кг</th>
							<th></th>
						</tr>
					</thead>

					<tbody data-issue-rows data-old-rows='@json(old("rows"))'>
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
					<span>Списать</span>
				</button>

				<button class="issue-order__button issue-order__button--reset button" type="button">
					<span>Очистить</span>
				</button>
			</div>
		</div>
	</form>

	{{-- Шаблон строки табличной части; JS клонирует и переиндексирует rows[0] --}}
	<template data-issue-row-template>
		<tr data-issue-row>
			<td>
				<div data-select>
					<div class="select material-select" data-issue-material-select>
						<input class="select__value" type="hidden"
								name="rows[0][material_id]" data-issue-material value="">

						<button class="material-select__select-button select__button select-button"
								type="button" aria-haspopup="listbox" aria-expanded="false">
							<span class="material-select__select-value select__button-text">Выберите материал</span>
							<i class="material-select__select-arrow" aria-hidden="true"></i>
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

							@foreach ($materials ?? [] as $material)
								<button class="material-select__select-option select__item" type="button" role="option"
										data-value="{{ $material->id }}"
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
					<div class="select material-select" data-issue-roll-select data-select-initialized="true">
						<input class="select__value" type="hidden"
								name="rows[0][roll_id]" data-issue-roll value="">

						<button class="material-select__select-button select__button select-button"
								type="button" aria-haspopup="listbox" aria-expanded="false">
							<span class="material-select__select-value select__button-text">Сначала выберите материал</span>
							<i class="material-select__select-arrow" aria-hidden="true"></i>
						</button>

						<div class="select__dropdown material-select__select-list _collapse" role="listbox">
							<div class="material-select__select-search">
								<input class="material-select__select-search-input select__search"
										type="search" placeholder="Поиск рулона..." autocomplete="off">
								<button class="material-select__select-search-clear select__search-clear" type="button"
										aria-label="Очистить поиск" hidden>
									<i class="icon icon-close" aria-hidden="true"></i>
								</button>
							</div>

							{{-- Пункты рулонов подгружаются через JS --}}
							<div data-issue-rolls-list></div>

							<div class="material-select__select-empty select__empty" hidden>
								Нет доступных рулонов
							</div>
						</div>
					</div>
				</div>
			</td>

			<td>
				<input class="issue-order__input" type="text" readonly data-issue-weight-before>
			</td>

			<td>
				<input class="issue-order__input" type="number" step="0.001" min="0"
						name="rows[0][weight]" data-issue-input value="">
			</td>

			<td>
				<button class="receipt-order__roll-remove button" type="button"
						data-issue-row-remove aria-label="Удалить позицию">
					<span>Удалить</span>
				</button>
			</td>
		</tr>
	</template>

@endsection
