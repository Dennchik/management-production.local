@extends('layouts.app')

@section('title', $material->name)

@section('content')

	<div class="warehouse-material">

		<div class="warehouse-material__header">

			<h1 class="main-content__title">
				{{ $material->name }}
			</h1>

			<a
					class="warehouse-material__back button"
					href="{{ route('warehouse.index') }}">
				<span>Назад на склад</span>
			</a>

		</div>

		<div class="warehouse-material__content">

			{{-- Основная информация --}}
			<section class="warehouse-material__section">

				<h2 class="warehouse-material__section-title">
					Информация о материале
				</h2>

				<div class="warehouse-material__rows">

					<div class="warehouse-material__row">
						<div class="warehouse-material__label">
							Наименование
						</div>

						<div class="warehouse-material__value">
							{{ $material->name }}
						</div>
					</div>

					<div class="warehouse-material__row">
						<div class="warehouse-material__label">
							Идентификатор
						</div>

						<div class="warehouse-material__value">
							{{ $material->identifier }}
						</div>
					</div>

					<div class="warehouse-material__row">
						<div class="warehouse-material__label">
							Толщина
						</div>

						<div class="warehouse-material__value">
							{{ $material->thickness ?? '—' }}
						</div>
					</div>

					<div class="warehouse-material__row">
						<div class="warehouse-material__label">
							Граммаж
						</div>

						<div class="warehouse-material__value">
							@if ($material->grammage !== null)
								{{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }}
								гр
							@else
								—
							@endif
						</div>
					</div>

					<div class="warehouse-material__row">
						<div class="warehouse-material__label">
							Формат
						</div>

						<div class="warehouse-material__value">
							{{ $material->format ?? '—' }}
						</div>
					</div>

				</div>

			</section>

			{{-- Остаток --}}
			<section class="warehouse-material__section">

				<h2 class="warehouse-material__section-title">
					Остаток на складе
				</h2>

				<div class="warehouse-material__stats">

					<div class="warehouse-material__stat">

						<span class="warehouse-material__stat-label">
						Рулонов
						</span>
						<strong class="warehouse-material__stat-value">
							{{ $rollsCount }}
						</strong>

					</div>

					<div class="warehouse-material__stat">

						<span class="warehouse-material__stat-label">
							Общий вес
						</span>

						<strong class="warehouse-material__stat-value">
							{{ number_format($totalWeight, 3, '.', '') }}
							кг
						</strong>

					</div>
				</div>
			</section>

			{{-- Физические рулоны --}}
			<section class="warehouse-material__section">

				<h2 class="warehouse-material__section-title">
					Физические рулоны
				</h2>

				@if ($rolls->isEmpty())

					<p class="warehouse-material__empty">
						Рулонов этого материала на складе нет.
					</p>

				@else

					<div class="warehouse-material__table-wrapper">

						<table class="warehouse-material__table">

							<thead>
							<tr>
								<th>Номер рулона</th>
								<th>Остаток, кг</th>
								<th>Дата поступления</th>
							</tr>
							</thead>

							<tbody>

							@foreach ($rolls as $roll)

								<tr
										class="warehouse-material__roll-row"
										data-row-link="{{ route('material-rolls.show', $roll) }}"
										tabindex="0"
										role="link">

									<td>
										{{ $roll->roll_number }}
									</td>

									<td>
										{{ number_format($roll->weight, 3, '.', '') }}
									</td>

									<td>
										{{ $roll->created_at->format('d.m.Y H:i') }}
									</td>

								</tr>

							@endforeach

							</tbody>
						</table>
					</div>

				@endif
			</section>
		</div>
	</div>

@endsection