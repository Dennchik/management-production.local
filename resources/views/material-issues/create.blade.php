@extends('layouts.app')

@section('title', 'Новый расход')

@section('content')

	@include('partials.message')

	<h1 class="main-content__title">Расходный ордер</h1>

	<form
			class="issue-order"
			method="POST"
			action="{{ route('material-issues.store') }}">

		@csrf

		<div class="issue-order__body">

			{{-- Выбор физического рулона --}}
			<div class="issue-order__line">

				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="roll_select">
						Рулон
					</label>

					<div class="material-select">

						<input
								id="roll_id"
								name="roll_id"
								type="hidden"
								value="{{ old('roll_id') }}">

						<button
								class="material-select__select-button select-button"
								id="roll_select"
								type="button"
								aria-haspopup="listbox"
								aria-expanded="false">

               <span class="material-select__select-value">
                 Выберите рулон
               </span>

							<span
									class="material-select__select-arrow"
									aria-hidden="true">
               </span>

						</button>

						<div
								class="material-select__select-list _collapse"
								role="listbox">

							<div class="material-select__select-search">

								<input
										class="material-select__select-search-input"
										id="roll_search"
										type="search"
										name="roll_search"
										placeholder="Поиск рулона..."
										autocomplete="off">

								<button
										class="material-select__select-search-clear"
										type="button"
										aria-label="Очистить поиск"
										hidden>
									<i
											class="icon icon-close"
											aria-hidden="true">
									</i>
								</button>

							</div>

							@foreach ($rolls as $roll)

								<button
										class="material-select__select-option"
										type="button"
										role="option"
										data-value="{{ $roll->id }}"
										data-material="{{ $roll->material->name }}"
										data-identifier="{{ $roll->material->identifier }}"
										data-roll="{{ $roll->roll_number }}"
										data-weight="{{ $roll->weight }}"
										aria-selected="{{ old('roll_id') == $roll->id ? 'true' : 'false' }}">

                   <span>
                     {{ $roll->material->name }}
                     | {{ $roll->roll_number }}
                     | {{ number_format($roll->weight, 3, '.', '') }} кг
                   </span>

								</button>

							@endforeach

							<div
									class="material-select__select-empty"
									hidden>
								Нет доступных рулонов
							</div>

						</div>

					</div>

				</fieldset>

				{{-- Материал определяется выбранным рулоном --}}
				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="material">
						Материал
					</label>

					<input
							class="issue-order__input"
							id="material"
							type="text"
							readonly>

				</fieldset>

				{{-- Идентификатор определяется автоматически --}}
				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="identifier">
						Идентификатор
					</label>

					<input
							class="issue-order__input"
							id="identifier"
							type="text"
							readonly>

				</fieldset>

				{{-- Номер выбранного рулона --}}
				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="roll_number">
						Номер рулона
					</label>

					<input
							class="issue-order__input"
							id="roll_number"
							type="text"
							readonly>

				</fieldset>

				{{-- Текущий остаток рулона --}}
				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="remaining_weight">
						Остаток, кг
					</label>

					<input
							class="issue-order__input"
							id="remaining_weight"
							type="text"
							readonly>

				</fieldset>

				{{-- Вес расхода --}}
				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="weight">
						Вес расхода, кг
					</label>

					<input
							class="issue-order__input"
							id="weight"
							name="weight"
							type="number"
							step="0.001"
							min="0"
							value="{{ old('weight') }}">

				</fieldset>

			</div>

			{{-- Дополнительный комментарий --}}
			<div class="issue-order__line">

				<fieldset class="issue-order__field">

					<label
							class="issue-order__label"
							for="comment">
						Комментарий
					</label>

					<textarea
							class="issue-order__input issue-order__textarea"
							id="comment"
							name="comment">{{ old('comment') }}</textarea>

				</fieldset>

			</div>

			<div class="issue-order__actions">

				<button
						class="issue-order__button main-content__button button"
						type="submit">
					<span>Списать</span>
				</button>

				<button
						class="issue-order__button issue-order__button--reset button"
						type="button">
					<span>Очистить</span>
				</button>

			</div>

		</div>

	</form>

@endsection