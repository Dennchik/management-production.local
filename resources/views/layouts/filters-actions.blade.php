@php
	$filterAction = $filterAction ?? route('warehouse.index');
	$filterReset = $filterReset ?? route('warehouse.index');
	$filterSearch = $filterSearch ?? ($search ?? '');
	$filterSearchPlaceholder = $filterSearchPlaceholder ?? 'Материал или идентификатор...';
@endphp

<div class="filters-actions">
	<div class="filters-actions__content">

		<form
				class="filters-actions__filters-form"
				method="GET"
				action="{{ $filterAction }}">

			<div class="filters-actions__filters-body">

				{{-- Поиск --}}
				<div class="filters-actions__filter">

					<label
							class="filters-actions__filter-label"
							for="filter-search">
						Поиск
					</label>

					<input
							class="filters-actions__filter-input"
							id="filter-search"
							name="search"
							type="search"
							placeholder="{{ $filterSearchPlaceholder }}"
							value="{{ $filterSearch }}"
							autocomplete="off">

				</div>

				@if (($filterType ?? 'warehouse') === 'warehouse')

					{{-- Формат --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="warehouse-format">
							Формат
						</label>

						<div data-select>

							<div class="select filters-actions__filter-select">

								<input
										class="select__button"
										id="warehouse-format"
										type="text"
										value="{{ ($format ?? '') ?: 'Все форматы' }}"
										readonly
										autocomplete="off">

								<input
										class="select__value"
										name="format"
										type="hidden"
										value="{{ $format ?? '' }}">

								<div class="select__dropdown _collapse">

									<div class="select__wrapper">

										<div
												class="select__item {{ ($format ?? '') === '' ? '_selected' : '' }}"
												tabindex="0"
												data-value="">
											Все форматы
										</div>

										@foreach ($formats ?? [] as $materialFormat)

											<div
													class="select__item {{ (string) ($format ?? '') === (string) $materialFormat ? '_selected' : '' }}"
													tabindex="0"
													data-value="{{ $materialFormat }}">
												{{ $materialFormat }}
											</div>

										@endforeach

									</div>

								</div>

							</div>

						</div>

					</div>

					{{-- Остаток --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="warehouse-stock">
							Остаток
						</label>

						<div data-select>

							<div class="select filters-actions__filter-select">

								<input
										class="select__button"
										id="warehouse-stock"
										type="text"
										value="@if (($stock ?? '') === 'available')Только в наличии@elseif (($stock ?? '') === 'empty')Без остатка@elseВсе материалы@endif"
										readonly
										autocomplete="off">

								<input
										class="select__value"
										name="stock"
										type="hidden"
										value="{{ $stock ?? '' }}">

								<div class="select__dropdown _collapse">

									<div class="select__wrapper">

										<div
												class="select__item {{ ($stock ?? '') === '' ? '_selected' : '' }}"
												tabindex="0"
												data-value="">
											Все материалы
										</div>

										<div
												class="select__item {{ ($stock ?? '') === 'available' ? '_selected' : '' }}"
												tabindex="0"
												data-value="available">
											Только в наличии
										</div>

										<div
												class="select__item {{ ($stock ?? '') === 'empty' ? '_selected' : '' }}"
												tabindex="0"
												data-value="empty">
											Без остатка
										</div>

									</div>

								</div>

							</div>

						</div>

					</div>

				@elseif (in_array(($filterType ?? ''), ['receipts', 'issues'], true))

					{{-- Дата от --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="{{ $filterType }}-date-from">
							Дата от
						</label>

						<input
								class="filters-actions__filter-input"
								id="{{ $filterType }}-date-from"
								name="date_from"
								type="date"
								value="{{ $dateFrom ?? '' }}">

					</div>

					{{-- Дата до --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="{{ $filterType }}-date-to">
							Дата до
						</label>

						<input
								class="filters-actions__filter-input"
								id="{{ $filterType }}-date-to"
								name="date_to"
								type="date"
								value="{{ $dateTo ?? '' }}">
					</div>

				@endif

			</div>

			{{-- Действия --}}
			<div class="filters-actions__actions">

				<button
						class="filters-actions__filter-button"
						type="submit">
					<span>Применить</span>
				</button>

				<a
						class="filters-actions__filter-reset"
						href="{{ $filterReset }}">
					<span>Сбросить</span>
				</a>

			</div>

		</form>

	</div>
</div>