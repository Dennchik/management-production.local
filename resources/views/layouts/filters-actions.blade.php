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

				{{-- Поиск склада --}}
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

				{{-- Склад --}}
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
										value="@if (($stock ?? '') === 'available')Только в наличии@elseif (($stock ?? '') === 'empty')Без остатка@elseif (($stock ?? '') === 'low')Низкий остаток@elseВсе материалы@endif"
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

										<div
												class="select__item {{ ($stock ?? '') === 'low' ? '_selected' : '' }}"
												tabindex="0"
												data-value="low">
											Низкий остаток
										</div>

									</div>

								</div>

							</div>

						</div>

					</div>

					{{-- Приход / Расход --}}
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

					{{-- Рулоны --}}
				@elseif ($filterType === 'rolls')

					{{-- Поиск рулона --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="rolls-search">
							Поиск рулона
						</label>

						<input
								class="filters-actions__filter-input"
								id="rolls-search"
								name="search"
								type="search"
								placeholder="Номер рулона..."
								value="{{ $filterSearch }}"
								autocomplete="off">

					</div>

					{{-- Материал --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="rolls-material">
							Материал
						</label>

						<div data-select>

							<div class="select filters-actions__filter-select">

								<input
										class="select__button"
										id="rolls-material"
										type="text"
										value="{{ ($materialId ?? '') === '' ? 'Все материалы' : ($materials->firstWhere('id', $materialId)?->name ?? 'Все материалы') }}"
										readonly
										autocomplete="off">

								<input
										class="select__value"
										name="material_id"
										type="hidden"
										value="{{ $materialId ?? '' }}">

								<div class="select__dropdown _collapse">

									<div class="select__wrapper">

										<div class="material-select__select-search">

											<input
													class="material-select__select-search-input select__search"
													id="rolls-material-search"
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

										<div
												class="select__item {{ ($materialId ?? '') === '' ? '_selected' : '' }}"
												tabindex="0"
												data-value="">
											Все материалы
										</div>

										@foreach ($materials ?? [] as $material)

											<div
													class="select__item {{ (string) ($materialId ?? '') === (string) $material->id ? '_selected' : '' }}"
													tabindex="0"
													data-search="{{ strtolower($material->name) }}"
													data-value="{{ $material->id }}">
												{{ $material->name }}
											</div>

										@endforeach

										<div
												class="material-select__select-empty select__empty"
												hidden>
											Ничего не найдено
										</div>

									</div>

								</div>

							</div>

						</div>

					</div>

					{{-- Идентификатор --}}
					<div class="filters-actions__filter">

						<label
								class="filters-actions__filter-label"
								for="rolls-identifier">
							Идентификатор
						</label>

						<div data-select>

							<div class="select filters-actions__filter-select">

								<input
										class="select__button"
										id="rolls-identifier"
										type="text"
										value="{{ ($identifier ?? '') ?: 'Все идентификаторы' }}"
										readonly
										autocomplete="off">

								<input
										class="select__value"
										name="identifier"
										type="hidden"
										value="{{ $identifier ?? '' }}">

								<div class="select__dropdown _collapse">

									<div class="select__wrapper">

										<div class="material-select__select-search">

											<input
													class="material-select__select-search-input select__search"
													id="rolls-identifier-search"
													type="search"
													placeholder="Поиск идентификатора..."
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

										<div
												class="select__item {{ ($identifier ?? '') === '' ? '_selected' : '' }}"
												tabindex="0"
												data-value="">
											Все идентификаторы
										</div>

										@foreach ($identifiers ?? [] as $materialIdentifier)

											<div
													class="select__item {{ (string) ($identifier ?? '') === (string) $materialIdentifier ? '_selected' : '' }}"
													tabindex="0"
													data-value="{{ $materialIdentifier }}">
												{{ $materialIdentifier }}
											</div>

										@endforeach

										<div
												class="material-select__select-empty select__empty"
												hidden>
											Ничего не найдено
										</div>

									</div>

								</div>

							</div>

						</div>

					</div>

					{{-- Движение материалов --}}
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
								for="material-movements-material">
							Материал
						</label>

						<div data-select>

							<div class="select filters-actions__filter-select">

								<input
										class="select__button"
										id="material-movements-material"
										type="text"
										value="{{ ($materialId ?? '') === '' ? 'Все материалы' : ($materials->firstWhere('id', $materialId)?->name ?? 'Все материалы') }}"
										readonly
										autocomplete="off">

								<input
										class="select__value"
										name="material_id"
										type="hidden"
										value="{{ $materialId ?? '' }}">

								<div class="select__dropdown _collapse">

									<div class="select__wrapper">

										<div class="material-select__select-search">

											<input
													class="material-select__select-search-input select__search"
													id="material-movements-material-search"
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

										<div
												class="select__item {{ ($materialId ?? '') === '' ? '_selected' : '' }}"
												tabindex="0"
												data-value="">
											Все материалы
										</div>

										@foreach ($materials ?? [] as $material)

											<div
													class="select__item {{ (string) ($materialId ?? '') === (string) $material->id ? '_selected' : '' }}"
													tabindex="0"
													data-search="{{ strtolower($material->name) }}"
													data-value="{{ $material->id }}">
												{{ $material->name }}
											</div>

										@endforeach

										<div
												class="material-select__select-empty select__empty"
												hidden>
											Ничего не найдено
										</div>

									</div>

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

