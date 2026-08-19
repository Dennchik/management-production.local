@extends('layouts.app')

@section('title', 'Новое оприходование')

@section('content')
	@include('partials.message')

	<h1 class="main-content__title">Приходный ордер</h1>

	<form
			class="receipt-order"
			method="POST"
			action="{{ route('material-receipts.store') }}">
		@csrf
		<div class="receipt-order__body">
			{{-- Материал выбирается из справочника --}}
			<div class="receipt-order__line">

				<fieldset class="receipt-order__field">
					<label class="receipt-order__label"
							for="material_select">
						Материал
					</label>

					<div class="material-select">
						<input
								id="material_id"
								name="material_select"
								type="hidden"
								value="{{ old('material_id') }}">

						<button class="material-select__select-button select-button"
								id="material_select"
								type="button"
								aria-haspopup="listbox"
								aria-expanded="false">
								 <span class="material-select__select-value">
									  Выберите материал
								 </span>

							<span class="material-select__select-arrow"
									aria-hidden="true"></span>
						</button>

						<div class="material-select__select-list _collapse"
								role="listbox">
							<div class="material-select__select-search">
								<input
										class="material-select__select-search-input"
										id="material_search"
										type="search"
										name="material_search"
										placeholder="Поиск материала..."
										autocomplete="off">
								<button
										class="material-select__select-search-clear"
										type="button"
										aria-label="Очистить поиск" hidden>
									<i class="icon icon-close" aria-hidden="true"></i>
								</button>
							</div>

							@foreach ($materials as $material)
								<button
										class="material-select__select-option"
										type="button"
										role="option"
										data-value="{{ $material->id }}"
										data-grammage="{{ $material->grammage }}"
										data-thickness="{{ $material->thickness }}"
										data-format="{{ $material->format }}"
										data-identifier="{{ $material->identifier }}"
										aria-selected="{{ old('material_id') ==
                                        $material->id ? 'true' : 'false' }}">
											<span> {{ preg_replace('/\s*гр\.?\s*$/ui', '',
											$material->name) }}
												@if ($material->grammage)
													| {{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }}
													гр
												@endif | {{ $material->format }}
											</span>
								</button>
							@endforeach

							<div class="material-select__select-empty" hidden>
								Ничего не найдено
							</div>
						</div>
					</div>
				</fieldset>

				{{-- Граммаж определяется автоматически --}}
				<fieldset class="receipt-order__field">
					<label
							class="receipt-order__label" for="grammage">
						Граммаж
					</label>

					<input
							class="receipt-order__input"
							id="grammage"
							type="text"
							readonly>
				</fieldset>

				{{-- Толщина определяется автоматически --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="thickness">
						Толщина
					</label>

					<input
							class="receipt-order__input"
							id="thickness"
							type="text"
							readonly>
				</fieldset>


				{{-- Формат определяется автоматически --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="format">
						Формат
					</label>

					<input
							class="receipt-order__input"
							id="format"
							type="text"
							readonly>
				</fieldset>

				{{-- Идентификатор определяется автоматически --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="identifier">
						Идентификатор
					</label>

					<input
							class="receipt-order__input"
							id="identifier"
							type="text"
							readonly>
				</fieldset>

				{{-- Номер физического рулона --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="roll_number">
						Номер рулона
					</label>

					<input
							class="receipt-order__input"
							id="roll_number"
							name="roll_number"
							type="text"
							value="{{ old('roll_number') }}">
				</fieldset>

				{{-- Вес физического рулона --}}
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="weight">
						Вес, кг
					</label>

					<input
							class="receipt-order__input"
							id="weight"
							name="weight"
							type="number"
							step="0.001"
							min="0"
							value="{{ old('weight') }}">
				</fieldset>

			</div>

			{{-- Дополнительный комментарий --}}
			<div class="receipt-order__line">
				<fieldset class="receipt-order__field">
					<label class="receipt-order__label" for="comment">
						Комментарий
					</label>

					<textarea
							class="receipt-order__input receipt-order__textarea"
							id="comment"
							name="comment">{{ old('comment') }}
                </textarea>
				</fieldset>
			</div>

			<div class="receipt-order__actions">
				<button
						class="receipt-order__button main-content__button"
						type="submit">
					Оприходовать
				</button>

				<button
						class="receipt-order__button receipt-order__button--reset"
						type="button">
					Очистить
				</button>
			</div>

		</div>


	</form>
@endsection