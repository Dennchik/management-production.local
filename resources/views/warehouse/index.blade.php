@extends('layouts.app')

@section('title', 'Склад')

@section('content')

	<div class="warehouse">

		{{-- Фильтр --}}
		<div class="warehouse__filters">

			<form
					class="warehouse__filters-form"
					method="GET"
					action="{{ route('warehouse.index') }}">
				<div class="warehouse__filters-body">
					{{-- Поиск --}}
					<div class="warehouse__filter">

						<label
								class="warehouse__filter-label"
								for="warehouse-search">
							Поиск
						</label>

						<input
								class="warehouse__filter-input"
								id="warehouse-search"
								name="search"
								type="search"
								placeholder="Материал или идентификатор..."
								value="{{ $search }}"
								autocomplete="off">

					</div>

					{{-- Формат --}}
					<div class="warehouse__filter">

						<label
								class="warehouse__filter-label"
								for="warehouse-format">
							Формат
						</label>

						<div data-select>

							<div class="select warehouse__filter-select">

								<input
										class="select__button"
										id="warehouse-format"
										type="text"
										value="{{ $format ?: 'Все форматы' }}"
										readonly
										autocomplete="off">

								<input
										class="select__value"
										name="format"
										type="hidden"
										value="{{ $format }}">

								<div class="select__dropdown _collapse">

									<div
											class="select__item {{ $format === '' ? '_selected' : '' }}"
											tabindex="0"
											data-value="">
										Все форматы
									</div>

									@foreach ($formats as $materialFormat)

										<div
												class="select__item {{ (string) $format === (string) $materialFormat ? '_selected' : '' }}"
												tabindex="0"
												data-value="{{ $materialFormat }}">
											{{ $materialFormat }}
										</div>

									@endforeach

								</div>

							</div>

						</div>

					</div>

					{{-- Остаток --}}
					<div class="warehouse__filter">

						<label
								class="warehouse__filter-label"
								for="warehouse-stock">
							Остаток
						</label>

						<div data-select>

							<div class="select warehouse__filter-select">

								<input
										class="select__button"
										id="warehouse-stock"
										type="text"
										value="@if ($stock === 'available')Только в наличии@elseif ($stock === 'empty')Без остатка@elseВсе материалы@endif"
										readonly
										autocomplete="off">

								<input
										class="select__value"
										name="stock"
										type="hidden"
										value="{{ $stock }}">

								<div class="select__dropdown _collapse">

									<div
											class="select__item {{ $stock === '' ? '_selected' : '' }}"
											tabindex="0"
											data-value="">
										Все материалы
									</div>

									<div
											class="select__item {{ $stock === 'available' ? '_selected' : '' }}"
											tabindex="0"
											data-value="available">
										Только в наличии
									</div>

									<div
											class="select__item {{ $stock === 'empty' ? '_selected' : '' }}"
											tabindex="0"
											data-value="empty">
										Без остатка
									</div>

								</div>

							</div>

						</div>

					</div>
				</div>
				{{-- Действия --}}
				<div class="warehouse__filters-actions">

					<button
							class="warehouse__filter-button"
							type="submit">
						<span>Применить</span>
					</button>

					<a
							class="warehouse__filter-reset"
							href="{{ route('warehouse.index') }}">
						<span>Сбросить</span>
					</a>

				</div>

			</form>

		</div>
		<h1 class="main-content__title">Склад</h1>
		{{-- Таблица склада --}}

		<table class="warehouse__table">

			<thead>
			<tr>
				<th>Материал</th>
				<th>Идентификатор</th>
				<th>Формат</th>
				<th>Рулонов</th>
				<th>Остаток, кг</th>
			</tr>
			</thead>

			<tbody>

			@forelse ($materials as $material)

				<tr>
					<td>{{ $material->name }}</td>
					<td>{{ $material->identifier }}</td>
					<td>{{ $material->format }}</td>
					<td>{{ $material->rolls_count }}</td>
					<td>
						{{ number_format($material->total_weight ?? 0, 3, '.', '') }}
					</td>
				</tr>

			@empty

				<tr>
					<td colspan="5">
						Склад пуст
					</td>
				</tr>

			@endforelse

			</tbody>

		</table>
	</div>
@endsection