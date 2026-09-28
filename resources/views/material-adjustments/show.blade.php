@extends('layouts.app')

@section('title', 'Ордер корректировки')

@section('content')

	<div class="main-content__content">
		<div class="main-content__header">
			<h1 class="main-content__title">Ордер корректировки</h1>

			<a class="material__back button" href="{{ route('material-adjustments.index') }}">
				<span>К списку ордеров</span>
			</a>
		</div>

		<div class="material">
			<div class="material__content">

				{{-- Шапка ордера --}}
				<section class="material__section material__section--format">
					<h2 class="material__section-title">Информация об ордере</h2>

					<div class="material__column">
						<div class="material__rows">
							<div class="material__row">
								<div class="material__label">Дата</div>
								<div class="material__value">{{ $adjustment->created_at->format('d.m.Y H:i') }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Пользователь</div>
								<div class="material__value">{{ $adjustment->user->name ?? '—' }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Позиций</div>
								<div class="material__value">{{ $rows->count() }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Комментарий</div>
								<div class="material__value">{{ $adjustment->comment ?? '—' }}</div>
							</div>
						</div>
					</div>
				</section>

				{{-- Позиции ордера --}}
				<section class="material__section material__section--format">
					<h2 class="material__section-title">Позиции</h2>

					<div class="material__column material__column--full">
						@if ($rows->isEmpty())
							<p class="material__empty">В ордере нет позиций.</p>
						@else
							<div class="material__table-wrapper">
								<table class="material__table">
									<thead>
									<tr>
										<th>Позиция</th>
										<th>Рулон</th>
										<th>Учётный остаток, кг</th>
										<th>Отклонение, кг</th>
										<th>Новый остаток, кг</th>
									</tr>
									</thead>

									<tbody>
									@foreach ($rows as $row)
										<tr>
											<td>{{ $row->material->name }}</td>
											<td>{{ $row->roll->roll_number }}</td>
											<td>{{ rtrim(rtrim(number_format((float) $row->weight_before, 3, '.', ''), '0'), '.') }}</td>
											<td class="{{ $row->adjustment < 0 ? 'text-red-soft' : '' }}">
												{{ ($row->adjustment > 0 ? '+' : '') . rtrim(rtrim(number_format((float) $row->adjustment, 3, '.', ''), '0'), '.') }}
											</td>
											<td>{{ rtrim(rtrim(number_format((float) $row->weight_after, 3, '.', ''), '0'), '.') }}</td>
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
