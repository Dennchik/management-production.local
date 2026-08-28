@extends('layouts.app')

@section('title', 'Новый расход')

@section('content')

	@include('partials.message')

	<h1 class="main-content__title">Расходный ордер</h1>

	<form
			class="issue-order"
			method="POST"
			action="{{ route('material-issues.store') }}"
			novalidate>

		@csrf

		<div class="issue-order__body">

			{{-- Материал --}}
			<div class="issue-order__line">

				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="material_select">
						Материал
					</label>

					<div data-select>

						<div class="select material-select">

							<input
									class="select__value"
									id="material_id"
									name="material_id"
									type="hidden"
									value="{{ old('material_id') }}">

							<button
									class="material-select__select-button select__button select-button"
									id="material_select"
									type="button"
									aria-haspopup="listbox"
									aria-expanded="false">

                 <span class="material-select__select-value select__button-text">
                   Выберите материал
                 </span>

								<span
										class="material-select__select-arrow"
										aria-hidden="true">
                 </span>

							</button>

							<div
									class="select__dropdown material-select__select-list _collapse"
									role="listbox">

								<div class="material-select__select-search">

									<input
											class="material-select__select-search-input select__search"
											type="search"
											placeholder="Поиск материала..."
											autocomplete="off">

									<button
											class="material-select__select-search-clear select__search-clear"
											type="button"
											aria-label="Очистить поиск"
											hidden>

										<i
												class="icon icon-close"
												aria-hidden="true">
										</i>

									</button>

								</div>

								@foreach ($materials ?? [] as $material)

									<button
											class="material-select__select-option select__item"
											type="button"
											role="option"
											data-value="{{ $material->id }}"
											data-name="{{ $material->name }}"
											data-identifier="{{ $material->identifier }}"
											data-search="{{ strtolower($material->name . ' ' . $material->identifier . ' ' . $material->format . ' ' . $material->thickness . ' ' . $material->grammage) }}"
											aria-selected="{{ old('material_id') == $material->id ? 'true' : 'false' }}">

									<span>
										{{ $material->name }}

										@if ($material->thickness)
											| {{ $material->thickness }} мкм
										@endif

										@if ($material->grammage)
											| {{ $material->grammage }} гр
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


				{{-- Выбранный материал --}}
				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="material_name">
						Материал
					</label>

					<input
							class="issue-order__input"
							id="material_name"
							name="material_name"
							type="text"
							readonly>

				</fieldset>


				{{-- Идентификатор --}}
				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="material_identifier">
						Идентификатор
					</label>

					<input
							class="issue-order__input"
							id="material_identifier"
							name="material_identifier"
							type="text"
							readonly>

				</fieldset>

			</div>


			{{-- Рулон --}}
			<div class="issue-order__line">

				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="roll_select">
						Номер рулона
					</label>

					<div data-select>

						<div class="select material-select">

							{{-- ID физического рулона --}}
							<input
									class="select__value"
									id="roll_id"
									name="roll_id"
									type="hidden"
									value="{{ old('roll_id') }}">

							<button
									class="material-select__select-button select__button select-button"
									id="roll_select"
									type="button"
									aria-haspopup="listbox"
									aria-expanded="false">

                 <span
							  class="material-select__select-value select__button-text">
                   Сначала выберите материал
                 </span>

								<span
										class="material-select__select-arrow"
										aria-hidden="true">
                 </span>

							</button>

							<div
									class="select__dropdown material-select__select-list _collapse"
									role="listbox">

								<div class="material-select__select-search">

									<input
											class="material-select__select-search-input select__search"
											type="search"
											placeholder="Поиск рулона..."
											autocomplete="off">

									<button
											class="material-select__select-search-clear select__search-clear"
											type="button"
											aria-label="Очистить поиск"
											hidden>

										<i
												class="icon icon-close"
												aria-hidden="true">
										</i>

									</button>

								</div>

								{{-- Список рулонов заполняется через JS --}}
								<div id="rolls-list"></div>

								<div
										class="material-select__select-empty select__empty"
										hidden>
									Нет доступных рулонов
								</div>

							</div>

						</div>

					</div>

				</fieldset>


				{{-- Остаток выбранного рулона --}}
				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="remaining_weight">
						Остаток, кг
					</label>

					<input class="issue-order__input" id="remaining_weight" type="text"
							readonly>
				</fieldset>
			</div>

			{{-- Вес расхода --}}
			<div class="issue-order__line">
				<fieldset class="issue-order__field">
					<label class="issue-order__label" for="weight">
						Вес расхода, кг
					</label>

					<input class="issue-order__input" id="weight" name="weight" type="number"
							step="0.001" min="0" value="{{ old('weight') }}">
				</fieldset>
			</div>


			{{-- Комментарий --}}
			<div class="issue-order__line">

				<fieldset class="issue-order__field">

					<label class="issue-order__label" for="comment">Комментарий
					</label>

					<textarea class="issue-order__input issue-order__textarea" id="comment"
							name="comment">{{ old('comment') }}
					</textarea>
				</fieldset>
			</div>

			{{-- Действия --}}
			<div class="issue-order__actions">
				<button class="issue-order__button main-content__button button" type="submit">
					<span>Списать </span>
				</button>

				<button class="issue-order__button issue-order__button--reset button" type="button">
					<span>Очистить</span>
				</button>

			</div>
		</div>
	</form>

@endsection

