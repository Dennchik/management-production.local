@extends('layouts.app')

@section('title', $material->name)

@section('content')

	<div class="main-content__content">
		<div class="main-content__header">
			<h1 class="main-content__title">{{ $material->name }}</h1>

			<a class="material__back button" href="{{ route('warehouse.index') }}">
				<span>Назад на склад</span>
			</a>

		</div>
		<div class="material">
			<div class="material__content">

				<section class="material__section">
					{{-- Основная информация --}}
					<div class="material__column">

						<h2 class="material__section-title">Информация о материале</h2>

						<div class="material__rows">
							<div class="material__row">
								<div class="material__label">Наименование</div>
								<div class="material__value">{{ $material->name }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Идентификатор</div>
								<div class="material__value">{{ $material->identifier }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Толщина</div>

								<div class="material__value">{{ $material->thickness ?? '—' }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Граммаж</div>
								<div class="material__value">
									@if ($material->grammage !== null)
										{{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }}
										гр
									@else
										—
									@endif
								</div>
							</div>

							<div class="material__row">
								<div class="material__label">Формат</div>
								<div class="material__value">{{ $material->format ?? '—' }}</div>
							</div>
						</div>
					</div>

					{{-- Остаток --}}
					<div class="material__column">
						<h2 class="material__section-title">Остаток на складе</h2>
						<div class="material__stats">
							<div class="material__stat">
								<span class="material__stat-label">Рулонов</span>
								<strong class="material__stat-value">{{ $rollsCount }}</strong>
							</div>

							<div class="material__stat">
								<span class="material__stat-label">Общий вес</span>
								<strong class="material__stat-value">{{ number_format($totalWeight, 3, '.', '') }}кг</strong>
							</div>
						</div>
					</div>
				</section>
				{{-- Физические рулоны --}}
				<section class="material__section">
					<div class="material__column">
						<h2 class="material__section-title">Физические рулоны</h2>

						@if ($rolls->isEmpty())
							<p class="material__empty">Рулонов этого материала на складе нет.</p>
						@else

							<div class="material__table-wrapper">
								<table class="material__table">
									<thead>
									<tr>
										<th>Номер рулона</th>
										<th>Остаток, кг</th>
										<th>Дата поступления</th>
									</tr>
									</thead>

									<tbody>

									@foreach ($rolls as $roll)
										<tr class="material__roll-row" data-row-link="{{ route('material-rolls.show', $roll) }}"
												tabindex="0" role="link">
											<td>{{ $roll->roll_number }}</td>
											<td>{{ number_format($roll->weight, 3, '.', '') }}</td>
											<td>{{ $roll->created_at->format('d.m.Y H:i') }}</td>
										</tr>
									@endforeach

									</tbody>
								</table>
							</div>

						@endif
					</div>
				</section>
			</div>
		</div>
	</div>

@endsection