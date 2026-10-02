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

			{{-- Позиции ордера: материал + рулон + вес --}}
			<div class="receipt-order__line">
				<div class="receipt-order__rolls">
					<div class="receipt-order__rolls-header">
						<h2 class="receipt-order__rolls-title" data-receipt-rolls-title> Рулоны </h2>

						<button class="receipt-order__roll-add button" type="button" data-receipt-roll-add>
							<span data-receipt-roll-add-text>Добавить рулон</span>
						</button>
					</div>

					<p class="receipt-order__rolls-hint" data-receipt-total-hint hidden>
						Весь указанный вес будет приходован на рулон «Общий вес» каждого выбранного материала.
					</p>

					<table class="receipt-order__rolls-table">
						<thead>
							<tr>
								<th>Материал</th>
								<th data-receipt-roll-number-column>Номер рулона</th>
								<th>Вес, кг</th>
								<th class="materials__table-edit">
									<i class="icon-settings-cogs icon"></i>
								</th>
							</tr>
						</thead>
						<tbody data-receipt-rolls>

							@php
								$oldRolls = old('rolls', [
									 [
										  'material_id' => '',
										  'roll_number' => '',
										  'weight' => '',
									 ],
								]);
							@endphp

							@foreach ($oldRolls as $index => $roll)

								<tr data-receipt-roll>

									<td>
										<div data-select>
											<div class="select material-select" data-receipt-material-select>
												<input class="select__value" type="hidden"
														name="rolls[{{ $index }}][material_id]" data-receipt-roll-material
														value="{{ $roll['material_id'] ?? '' }}">

												<button class="material-select__select-button select__button select-button"
														type="button" aria-haspopup="listbox" aria-expanded="false">
													<span class="material-select__select-value select__button-text">Выберите материал</span>

													<i class="material-select__select-arrow" aria-hidden="true"></i>
												</button>

												<div class="select__dropdown material-select__select-list _collapse" role="listbox">
													<div class="material-select__select-search">
														<input class="material-select__select-search-input select__search"
																type="search"
																placeholder="Поиск материала..." autocomplete="off">

														<button class="material-select__select-search-clear select__search-clear"
																type="button" aria-label="Очистить поиск" hidden>
															<i class="icon icon-close" aria-hidden="true"></i>
														</button>
													</div>

													@foreach ($materials as $material)
														<button class="material-select__select-option select__item"
																type="button" role="option" data-value="{{ $material->id }}"
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
												data-receipt-roll-remove aria-label="Удалить рулон">
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

	{{-- Шаблон строки табличной части; JS клонирует и переиндексирует rolls[0] --}}
	<template data-receipt-roll-template>
		<tr data-receipt-roll>
			<td>
				<div data-select>
					<div class="select material-select" data-receipt-material-select>
						<input class="select__value" type="hidden"
								name="rolls[0][material_id]" data-receipt-roll-material value="">

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

							@foreach ($materials as $material)
								<button class="material-select__select-option select__item"
										type="button" role="option" data-value="{{ $material->id }}"
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

			<td data-receipt-roll-number-field>
				<input class="receipt-order__input" id="roll_number_0"
						name="rolls[0][roll_number]" data-receipt-roll-number type="text"
						value="" aria-label="Номер рулона">
			</td>

			<td>
				<input class="receipt-order__input" id="weight_0"
						name="rolls[0][weight]" data-receipt-roll-weight
						type="number" step="0.001" min="0" value="" aria-label="Вес, кг">
			</td>

			<td>
				<button class="receipt-order__roll-remove button" type="button"
						data-receipt-roll-remove aria-label="Удалить рулон">
					<span>Удалить</span>
				</button>
			</td>
		</tr>
	</template>

@endsection
