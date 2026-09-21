@extends('layouts.app')

@section('title', 'Рулон ' . $roll->roll_number)

@section('content')

	<div class="main-content__content">

		<div class="main-content__header">

			<h1 class="main-content__title">Рулон {{ $roll->roll_number }}</h1>

			<a class="material__back button" href="{{ route('warehouse.material', $roll->material) }}">
				<span>Назад к материалу</span>
			</a>

		</div>
		<div class="material">
			<div class="material__content">

				{{-- Основная информация --}}

				<section class="material__section">
					<div class="material__column-roll">
						<h2 class="material__section-title">Информация о рулоне</h2>

						<div class="material__rows show-roll">
							<div class="material__row">
								<div class="material__label">Материал</div>
								<div class="material__value">{{ $roll->material->name }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Идентификатор</div>
								<div class="material__value">{{ $roll->identifier ?? '—' }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Формат</div>
								<div class="material__value">{{ $roll->format ?? '—' }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">№ рулона</div>
								<div class="material__value">{{ $roll->roll_number }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Перв. вес</div>
								<div class="material__value">{{ number_format($initialWeight, 3, '.', '') }} кг</div>
							</div>

							<div class="material__row">
								<div class="material__label">Израсходовано</div>
								<div class="material__value">{{ number_format($issuedWeight, 3, '.', '') }} кг</div>
							</div>

							<div class="material__row">
								<div class="material__label">Остаток</div>
								<div class="material__value">{{ number_format($currentWeight, 3, '.', '') }} кг</div>
							</div>

							<div class="material__row">
								<div class="material__label">Опер. расхода</div>
								<div class="material__value">{{ $issuesCount }}</div>
							</div>
						</div>
					</div>
				</section>

				{{-- История движений --}}
				<section class="material__section">
					<div class="material__column-roll">
						<h2 class="material__section-title">История движений</h2>

						@if ($movements->isEmpty())

							<p class="main-content__empty">Движений по рулону пока нет.</p>

						@else

							<div class="material__table-wrapper">
								<table class="material__table">
									<thead>
									<tr>
										<th>Дата</th>
										<th>Операция</th>
										<th>Вес</th>
										<th>Остаток</th>
										<th>Пользователь</th>
										<th>Комментарий</th>
									</tr>
									</thead>

									<tbody>

									@foreach ($movements as $movement)

										<tr>
											<td>{{ $movement['date']->format('d.m.Y H:i') }}</td>

											<td>
												@if ($movement['type'] === 'receipt')
													Приход
												@else
													Расход
												@endif
											</td>

											<td>
												@if ($movement['type'] === 'receipt')
													+{{ number_format($movement['weight'], 3, '.', '') }}
												@else
													-{{ number_format($movement['weight'], 3, '.', '') }}
												@endif
												кг
											</td>

											<td>{{ number_format($movement['balance'], 3, '.', '') }}кг</td>

											<td>{{ $movement['user'] ?? '—' }}</td>

											<td>{{ $movement['comment'] ?? '—' }}</td>
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