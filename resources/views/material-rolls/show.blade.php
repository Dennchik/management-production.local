@extends('layouts.app')

@section('title', 'Рулон ' . $roll->roll_number)

@section('content')

	<div class="material-roll">

		<div class="material-roll__header">

			<h1 class="main-content__title">
				Рулон {{ $roll->roll_number }}
			</h1>

			<div class="material-roll__actions">

				<a class="material-roll__back button"
						href="{{ route('warehouse.material', $roll->material) }}">
					<span>Назад к материалу</span>
				</a>

			</div>

		</div>

		<div class="material-roll__content">

			{{-- Основная информация --}}
			<section class="material-roll__section">

				<h2 class="material-roll__section-title">
					Информация о рулоне
				</h2>

				<div class="warehouse-material__rows">

					<div class="material-roll__row">

						<div class="material-roll__label">
							Материал
						</div>

						<div class="material-roll__value">
							{{ $roll->material->name }}
						</div>

					</div>

					<div class="material-roll__row">

						<div class="material-roll__label">
							Идентификатор
						</div>

						<div class="material-roll__value">
							{{ $roll->material->identifier }}
						</div>

					</div>

					<div class="material-roll__row">

						<div class="material-roll__label">
							№ рулона
						</div>

						<div class="material-roll__value">
							{{ $roll->roll_number }}
						</div>

					</div>

					<div class="material-roll__row">

						<div class="material-roll__label">
							Перв. вес
						</div>

						<div class="material-roll__value">
							{{ number_format($initialWeight, 3, '.', '') }} кг
						</div>

					</div>

					<div class="material-roll__row">

						<div class="material-roll__label">
							Израсходовано
						</div>

						<div class="material-roll__value">
							{{ number_format($issuedWeight, 3, '.', '') }} кг
						</div>

					</div>

					<div class="material-roll__row">

						<div class="material-roll__label">
							Остаток
						</div>

						<div class="material-roll__value">
							{{ number_format($currentWeight, 3, '.', '') }} кг
						</div>

					</div>

					<div class="material-roll__row">

						<div class="material-roll__label">
							Опер. расхода
						</div>

						<div class="material-roll__value">
							{{ $issuesCount }}
						</div>

					</div>

				</div>

			</section>

			{{-- История движений --}}
			<section class="material-roll__section">

				<h2 class="material-roll__section-title">
					История движений
				</h2>

				@if ($movements->isEmpty())

					<p class="main-content__empty">
						Движений по рулону пока нет.
					</p>

				@else

					<div class="material-roll__table-wrapper">

						<table class="material-roll__table">

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

									<td>
										{{ $movement['date']->format('d.m.Y H:i') }}
									</td>

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

									<td>
										{{ number_format($movement['balance'], 3, '.', '') }}
										кг
									</td>

									<td>
										{{ $movement['user'] ?? '—' }}
									</td>

									<td>
										{{ $movement['comment'] ?? '—' }}
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