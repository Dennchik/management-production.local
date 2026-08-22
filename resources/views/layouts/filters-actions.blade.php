@php
	$filterAction = $filterAction ?? route('warehouse.index');
	$filterReset = $filterReset ?? route('warehouse.index');
	$filterType = $filterType ?? 'warehouse';

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
				@if ($filterType === 'warehouse')

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

				@endif

				@if ($filterType === 'warehouse')

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

				@elseif (in_array($filterType, ['receipts', 'issues'], true))

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

				@elseif ($filterType === 'material-movements')

					{{-- Дата от --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="material-movements-date-from">
							Дата от
						</label>

						<input
								class="filters-actions__filter-input"
								id="material-movements-date-from"
								name="date_from"
								type="date"
								value="{{ $dateFrom ?? '' }}">

					</div>

					{{-- Дата до --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="material-movements-date-to">
							Дата до
						</label>

						<input
								class="filters-actions__filter-input"
								id="material-movements-date-to"
								name="date_to"
								type="date"
								value="{{ $dateTo ?? '' }}">

					</div>

					{{-- Тип операции --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="material-movements-type">
							Операция
						</label>

						<div data-select>

							<div class="select filters-actions__filter-select">

								<input
										class="select__button"
										id="material-movements-type"
										type="text"
										value="@if (($type ?? '') === 'receipt')Приход@elseif (($type ?? '') === 'issue')Расход@elseВсе операции@endif"
										readonly
										autocomplete="off">

								<input
										class="select__value"
										name="type"
										type="hidden"
										value="{{ $type ?? '' }}">

								<div class="select__dropdown _collapse">

									<div class="select__wrapper">

										<div
												class="select__item {{ ($type ?? '') === '' ? '_selected' : '' }}"
												tabindex="0"
												data-value="">
											Все операции
										</div>

										<div
												class="select__item {{ ($type ?? '') === 'receipt' ? '_selected' : '' }}"
												tabindex="0"
												data-value="receipt">
											Приход
										</div>

										<div
												class="select__item {{ ($type ?? '') === 'issue' ? '_selected' : '' }}"
												tabindex="0"
												data-value="issue">
											Расход
										</div>

									</div>

								</div>

							</div>

						</div>

					</div>

					{{-- Материал --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="material-movements-material-button">
							Материал
						</label>

						<div class="material-select">

							<input
									id="material-movements-material"
									name="material_id"
									type="hidden"
									value="{{ $materialId ?? '' }}">

							<button
									class="material-select__select-button select-button"
									id="material-movements-material-button"
									type="button"
									aria-haspopup="listbox"
									aria-expanded="false">

                 <span class="material-select__select-value">

                   @if (($materialId ?? '') !== '')
							  {{ $materials->firstWhere('id', $materialId)?->name ?? 'Все материалы' }}
						  @else
							  Все материалы
						  @endif

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
											id="material-movements-material-search"
											type="search"
											name="material_search"
											placeholder="Поиск материала..."
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

								<button
										class="material-select__select-option"
										type="button"
										role="option"
										data-value=""
										aria-selected="{{ ($materialId ?? '') === '' ? 'true' : 'false' }}">

                   <span>
                     Все материалы
                   </span>

								</button>

								@foreach ($materials ?? [] as $material)

									<button
											class="material-select__select-option"
											type="button"
											role="option"
											data-value="{{ $material->id }}"
											aria-selected="{{ (string) ($materialId ?? '') === (string) $material->id ? 'true' : 'false' }}">

                     <span>
                       {{ preg_replace('/\s*гр\.?\s*$/ui', '', $material->name) }}

								@if ($material->grammage)
									|
									{{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }}
									гр
								@endif

                       | {{ $material->format }}
                     </span>

									</button>

								@endforeach

								<div
										class="material-select__select-empty"
										hidden>
									Ничего не найдено
								</div>

							</div>

						</div>

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