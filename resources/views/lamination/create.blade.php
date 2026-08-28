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
						<label class="form-label" for="base_material_id">Материал</label>

						<div class="select material-select">
							<input class="select__value" id="base_material_id" name="base_material_id"
									type="hidden" value="{{ old('base_material_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="base_material_select" type="button" aria-haspopup="listbox"
									aria-expanded="false">

                 <span class="material-select__select-value select__button-text">
                   Выберите материал
                 </span>

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

								{{-- «Основа» --}}
								@foreach ($baseMaterials as $material)

									<button class="material-select__select-option select__item" type="button"
											role="option" data-value="{{ $material->id }}"
											data-identifier="{{ $material->identifier }}"
											aria-selected="{{ old('base_material_id') == $material->id ? 'true' : 'false' }}">

									  <span>
											{{ $material->name }}

										  @if ($material->grammage)
											  | {{ number_format($material->grammage, 2, '.', '') }} гр
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
					</fieldset>

					<fieldset class="lamination-form__field">
						<label class="form-label" for="base_roll_id">Рулон</label>

						<div class="select material-select roll-select">
							<input class="select__value" id="base_roll_id" name="base_roll_id"
									type="hidden" value="{{ old('base_roll_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="base_roll_select" type="button" aria-haspopup="listbox"
									aria-expanded="false">

                 <span class="material-select__select-value select__button-text">
                   Выберите рулон
                 </span>

								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse" role="listbox">

								<div class="material-select__select-search">
									<input class="material-select__select-search-input select__search"
											type="search" placeholder="Поиск рулона..." autocomplete="off">

									<button class="material-select__select-search-clear select__search-clear"
											type="button" aria-label="Очистить поиск" hidden>

										<i class="icon icon-close" aria-hidden="true"></i>
									</button>
								</div>

								<div class="roll-select__options" data-base-roll-options></div>

								<div class="material-select__select-empty select__empty" hidden>
									Рулонов нет
								</div>

							</div>
						</div>
					</fieldset>

					<fieldset class="lamination-form__field">
						<label class="form-label" for="base_remaining_weight">Остаток, кг</label>

						<input class="lamination-form__input" id="base_remaining_weight"
								type="text" readonly>
					</fieldset>
				</div>
			</div>

			{{-- Материал для ламинации --}}
			<div class="lamination-form__section">
				<h2 class="lamination-form__title">Материал для ламинации</h2>

				<div class="lamination-form__row">
					<fieldset class="lamination-form__field">
						<label class="form-label" for="lamination_material_id">Материал</label>

						<div class="select material-select">
							<input class="select__value" id="lamination_material_id"
									name="lamination_material_id" type="hidden"
									value="{{ old('lamination_material_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="lamination_material_select" type="button"
									aria-haspopup="listbox" aria-expanded="false">

                 <span class="material-select__select-value select__button-text">
                   Выберите материал
                 </span>

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

								{{-- «Материал для ламинации» --}}
								@forceach ($laminationMaterials as $material)

									<button class="material-select__select-option select__item" type="button"
											role="option" data-value="{{ $material->id }}" data-identifier="{{
											$material->identifier }}" aria-selected="{{ old('lamination_material_id') ==
											$material->id ? 'true' : 'false' }}">
										<span>
											{{ $material->name }}

										  @if ($material->grammage)
											  | {{ number_format($material->grammage, 2, '.', '') }} гр
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
					</fieldset>

					<fieldset class="lamination-form__field">
						<label class="form-label" for="lamination_roll_id">Рулон</label>

						<div class="select material-select roll-select">
							<input class="select__value" id="lamination_roll_id"
									name="lamination_roll_id" type="hidden"
									value="{{ old('lamination_roll_id') }}">

							<button class="material-select__select-button select__button select-button"
									id="lamination_roll_select" type="button"
									aria-haspopup="listbox" aria-expanded="false">

                 <span class="material-select__select-value select__button-text">
                   Выберите рулон
                 </span>

								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse" role="listbox">

								<div class="material-select__select-search">
									<input class="material-select__select-search-input select__search"
											type="search" placeholder="Поиск рулона..." autocomplete="off">

									<button class="material-select__select-search-clear select__search-clear"
											type="button" aria-label="Очистить поиск" hidden>

										<i class="icon icon-close" aria-hidden="true"></i>
									</button>
								</div>

								<div class="roll-select__options" data-lamination-roll-options></div>

								<div class="material-select__select-empty select__empty" hidden>
									Рулонов нет
								</div>
							</div>
						</div>
					</fieldset>

					<fieldset class="lamination-form__field">
						<label class="form-label" for="lamination_remaining_weight">Остаток, кг</label>

						<input class="lamination-form__input" id="lamination_remaining_weight"
								type="text" readonly>
					</fieldset>
				</div>
			</div>

			{{-- Параметры задания --}}
			<div class="lamination-form__section">
				<h2 class="lamination-form__title">Параметры задания</h2>

				<div class="lamination-form__row">
					<fieldset class="lamination-form__field">
						<label class="form-label" for="planned_weight">Плановый вес, кг</label>

						<input class="lamination-form__input" id="planned_weight"
								name="planned_weight" type="number" step="0.001" min="0"
								value="{{ old('planned_weight') }}">
					</fieldset>
				</div>

				<div class="lamination-form__row">
					<fieldset class="lamination-form__field">
						<label class="form-label" for="comment">Комментарий</label>

						<textarea class="lamination-form__input lamination-form__textarea" id="comment" name="comment">{{
						old('comment') }}</textarea>
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