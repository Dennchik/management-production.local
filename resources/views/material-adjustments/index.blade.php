@extends('layouts.app')

@section('title', 'Ордера корректировки')

@section('content')

	<div class="main-content__content">
		{{-- Фильтр --}}
		@include('layouts.filters-actions', [
			'filterType' => 'adjustments',
			'filterAction' => route('material-adjustments.index'),
			'filterReset' => route('material-adjustments.index'),
		])

		{{-- Действия --}}
		<div class="main-content__header">
			<h1 class="main-content__title">Ордера корректировки</h1>
			<a class="material__create button" href="{{ route('material-adjustments.create') }}">
				<span>Новая корректировка</span>
			</a>
		</div>
		<div class="material">
			{{-- Список ордеров корректировки --}}
			<div class="material__table-wrapper">
				<table class="material__table">
					<thead>
					<tr>
						<th>Дата</th>
						<th>Материал</th>
						<th>Рулон</th>
						<th>Текущий вес, кг</th>
						<th>Корректировка, кг</th>
						<th>Конечный вес, кг</th>
						<th>Пользователь</th>
					</tr>
					</thead>
					<tbody>

					@forelse ($adjustments as $adjustment)
						<tr class="material__material-row" data-adjustment-modal-open data-adjustment-id="{{ $adjustment->getKey()}}">
							<td> {{ $adjustment->created_at->format('d.m.Y H:i') }} </td>
							<td> {{ $adjustment->material->name }} </td>
							<td> {{ $adjustment->roll->roll_number }} </td>
							<td> {{ rtrim(rtrim(number_format((float) $adjustment->weight_before, 3, '.', ''), '0'), '.') }} </td>
							<td class="{{ $adjustment->adjustment < 0 ? 'text-red-soft' : '' }}">
								{{ ($adjustment->adjustment > 0 ? '+' : '') . rtrim(rtrim(number_format((float) $adjustment->adjustment, 3, '.', ''), '0'), '.') }}
							</td>
							<td> {{ rtrim(rtrim(number_format((float) $adjustment->weight_after, 3, '.', ''), '0'), '.') }} </td>
							<td> {{ $adjustment->user->name ?? '—' }} </td>
						</tr>

					@empty
						<tr>
							<td class="material__empty" colspan="7">
								Ордеров корректировки пока нет
							</td>
						</tr>
					@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>

@endsection
