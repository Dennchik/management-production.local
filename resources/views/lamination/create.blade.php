@extends('layouts.app')

@section('title', 'Новое задание на ламинацию')

@section('content')

	<div class="lamination-create">
		<h1 class="main-content__title">Новое задание на ламинацию</h1>
		<form class="lamination-form" action="{{ route('lamination.store') }}" method="POST">
			@csrf

			{{-- Основа --}}
			<div class="lamination-form__section">
				<h2 class="lamination-form__title">Основа</h2>

				<div class="lamination-form__row">
					<fieldset class="lamination-form__field">
						<label class="lamination-form__label" for="base_material_select">Материал</label>

						<div class="select material-select">
							<input class="select__value" id="base_material_id" name="base_material_id"
									type="hidden" value="{{ old('base_material_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="base_material_select" type="button" aria-haspopup="listbox"
									aria-expanded="false" aria-controls="base_material_list">
             <span class="material-select__select-value select__button-text">
               Выберите материал
             </span>
								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse">
								<div class="material-select__select-search">
									<!-- ИСПРАВЛЕНО: Добавлены id и name -->
									<input class="material-select__select-search-input select__search"
											id="base_material_search" name="base_material_search"
											type="search" placeholder="Поиск материала..." autocomplete="off">

									<button class="material-select__select-search-clear select__search-clear"
											type="button" aria-label="Очистить поиск" hidden>
										<i class="icon icon-close" aria-hidden="true"></i>
									</button>
								</div>

								<div id="base_material_list" role="listbox" aria-labelledby="base_material_select">
									@foreach ($baseMaterials as $material)
										<div class="material-select__select-option select__item"
												role="option"
												tabindex="0"
												data-value="{{ $material->id }}"
												data-identifier="{{ $material->identifier }}"
												aria-selected="{{ old('base_material_id') == $material->id ? 'true' : 'false' }}">
                   <span>
                     {{ $material->name }}
							 @if ($material->grammage) | {{ number_format($material->grammage, 2, '.', '') }} гр @endif
							 @if ($material->thickness) | {{ $material->thickness }} мкм @endif
                     | {{ $material->format }}
                   </span>
										</div>
									@endforeach
								</div>

								<div class="material-select__select-empty select__empty" hidden>
									Ничего не найдено
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="lamination-form__field">
						<label class="lamination-form__label" for="base_roll_select">Рулон</label>

						<div class="select material-select roll-select">
							<input class="select__value" id="base_roll_id" name="base_roll_id"
									type="hidden" value="{{ old('base_roll_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="base_roll_select" type="button" aria-haspopup="listbox"
									aria-expanded="false" aria-controls="base_roll_list">
             <span class="material-select__select-value select__button-text">
               Выберите рулон
             </span>
								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse">
								<div class="material-select__select-search">
									<!-- ИСПРАВЛЕНО: Добавлены id и name -->
									<input class="material-select__select-search-input select__search"
											id="base_roll_search" name="base_roll_search"
											type="search" placeholder="Поиск рулона..." autocomplete="off">

									<button class="material-select__select-search-clear select__search-clear"
											type="button" aria-label="Очистить поиск" hidden>
										<i class="icon icon-close" aria-hidden="true"></i>
									</button>
								</div>

								<div id="base_roll_list" class="roll-select__options" data-base-roll-options role="listbox" aria-labelledby="base_roll_select"></div>

								<div class="material-select__select-empty select__empty" hidden>
									Рулонов нет
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="lamination-form__field">
						<label class="lamination-form__label" for="base_remaining_weight">Остаток, кг</label>
						<!-- ИСПРАВЛЕНО: Добавлен name -->
						<input class="lamination-form__input" id="base_remaining_weight" name="base_remaining_weight"
								type="text" readonly tabindex="-1">
					</fieldset>
				</div>
			</div>

			{{-- Материал для ламинации --}}
			<div class="lamination-form__section">
				<h2 class="lamination-form__title">Материал для ламинации</h2>

				<div class="lamination-form__row">
					<fieldset class="lamination-form__field">
						<label class="lamination-form__label" for="lamination_material_select">Материал</label>

						<div class="select material-select">
							<input class="select__value" id="lamination_material_id"
									name="lamination_material_id" type="hidden"
									value="{{ old('lamination_material_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="lamination_material_select" type="button"
									aria-haspopup="listbox" aria-expanded="false" aria-controls="lamination_material_list">
             <span class="material-select__select-value select__button-text">
              Выберите материал
             </span>
								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse">
								<div class="material-select__select-search">
									<!-- ИСПРАВЛЕНО: Добавлены id и name -->
									<input class="material-select__select-search-input select__search"
											id="lamination_material_search" name="lamination_material_search"
											type="search" placeholder="Поиск материала..." autocomplete="off">

									<button class="material-select__select-search-clear select__search-clear"
											type="button" aria-label="Очистить поиск" hidden>
										<i class="icon icon-close" aria-hidden="true"></i>
									</button>
								</div>

								<div id="lamination_material_list" role="listbox" aria-labelledby="lamination_material_select">
									@foreach ($laminationMaterials as $material)
										<div class="material-select__select-option select__item"
												role="option"
												tabindex="0"
												data-value="{{ $material->id }}"
												data-identifier="{{ $material->identifier }}"
												aria-selected="{{ old('lamination_material_id') == $material->id ? 'true' : 'false' }}">
                   <span>
                     {{ $material->name }}
							 @if ($material->grammage) | {{ number_format($material->grammage, 2, '.', '') }} гр @endif
							 @if ($material->thickness) | {{ $material->thickness }} мкм @endif
                     | {{ $material->format }}
                   </span>
										</div>
									@endforeach
								</div>

								<div class="material-select__select-empty select__empty" hidden>
									Ничего не найдено
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="lamination-form__field">
						<label class="lamination-form__label" for="lamination_roll_select">Рулон</label>

						<div class="select material-select roll-select">
							<input class="select__value" id="lamination_roll_id" name="lamination_roll_id" type="hidden"
									value="{{ old('lamination_roll_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="lamination_roll_select" type="button" aria-haspopup="listbox" aria-expanded="false" aria-controls="lamination_roll_list">
								<span class="material-select__select-value select__button-text">Выберите рулон</span>
								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse">
								<div class="material-select__select-search">
									<!-- ИСПРАВЛЕНО: Добавлены id и name -->
									<input class="material-select__select-search-input select__search"
											id="lamination_roll_search" name="lamination_roll_search"
											type="search" placeholder="Поиск рулона..." autocomplete="off">

									<button class="material-select__select-search-clear select__search-clear"
											type="button" aria-label="Очистить поиск" hidden>
										<i class="icon icon-close" aria-hidden="true"></i>
									</button>
								</div>

								<div id="lamination_roll_list" class="roll-select__options" data-lamination-roll-options role="listbox" aria-labelledby="lamination_roll_select"></div>

								<div class="material-select__select-empty select__empty" hidden>
									Рулонов нет
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="lamination-form__field">
						<label class="lamination-form__label" for="lamination_remaining_weight">Остаток, кг</label>

						<!-- ИСПРАВЛЕНО: Добавлен name -->
						<input class="lamination-form__input" id="lamination_remaining_weight" name="lamination_remaining_weight"
								type="text" readonly tabindex="-1">
					</fieldset>
				</div>
			</div>

			{{-- Параметры задания --}}
			<div class="lamination-form__section">
				<h2 class="lamination-form__title">Параметры задания</h2>

				<div class="lamination-form__row">
					<fieldset class="lamination-form__field">
						<label class="lamination-form__label" for="planned_weight">Плановый вес, кг</label>

						<input class="lamination-form__input lamination-form__input-weight" id="planned_weight"
								name="planned_weight" type="number" step="0.001" min="0"
								value="{{ old('planned_weight') }}" required>
					</fieldset>
				</div>

				<div class="lamination-form__textarea-row">
					<fieldset class="lamination-form__field">
						<label class="lamination-form__label" for="comment">Комментарий</label>

						<textarea class="lamination-form__textarea" id="comment" name="comment">{{ old('comment') }}</textarea>
					</fieldset>
				</div>
			</div>

			{{-- Действия --}}
			<div class="lamination-form__actions">
				<button class="button button--primary" type="submit">
					Создать задание
				</button>
			</div>
		</form>
	</div>

@endsection