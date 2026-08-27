@extends('layouts.app')

@section('title', 'Движение материалов')

@section('content')

	<div class="material-movements">
		<div class="material-movements__content">
			{{-- Фильтр --}}
			<div class="material-movements">
				@include('layouts.filters-actions', [
				  'filterType' => 'material-movements',
				  'filterAction' => route('material-movements.index'),
				  'filterReset' => route('material-movements.index'),
				])
			</div>

			<h1 class="main-content__title">
				Движение материалов
			</h1>

			{{-- Таблица движений --}}
			<div class="material-movements__table-wrapper">
				<table class="material-movements__table">

					<thead>
					<tr>
						<th>Дата</th>
						<th>Операция</th>
						<th>Материал</th>
						<th>Идентификатор</th>
						<th>Рулон</th>
						<th>Изменение</th>
						<th>Пользователь</th>
					</tr>
					</thead>

					<tbody>

					@forelse ($movements as $movement)

						<tr>
							{{-- Дата --}}
							<td class="material-movements__date"> {{ $movement['date']->format('d.m.Y H:i') }} </td>

							{{-- Операция --}}
							<td>
								@if ($movement['type'] === 'receipt')
									Приход
								@else
									Расход
								@endif
							</td>

							{{-- Материал --}}
							<td> {{ $movement['material']->name }} </td>

							{{-- Идентификатор --}}
							<td> {{ $movement['material']->identifier }} </td>

							{{-- Рулон --}}
							<td> {{ $movement['roll']->roll_number }} </td>

							{{-- Изменение веса --}}
							<td>
								@if ($movement['type'] === 'receipt')
									+{{ number_format($movement['weight'], 3, '.', '') }}
								@else
									-{{ number_format($movement['weight'], 3, '.', '') }}
								@endif
								кг
							</td>
							{{-- Пользователь --}}
							<td> {{ $movement['user']?->name ?? '—' }} </td>
						</tr>

					@empty
						<tr>
							<td class="material-movements__empty" colspan="7">
								Движений материалов пока нет
							</td>
						</tr>
					@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>

@endsection