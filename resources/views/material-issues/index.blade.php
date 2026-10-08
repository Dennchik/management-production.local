@extends('layouts.app')

@section('title', 'Расходные ордера')

@section('content')

	<div class="main-content__content">
		{{-- Фильтр --}}
		@include('layouts.filters-actions', [
			'filterType' => 'issues',
			'filterAction' => route('material-issues.index'),
			'filterReset' => route('material-issues.index'),
		])

		{{-- Действия --}}
		<div class="main-content__header">
			<h1 class="main-content__title">Расходные ордера</h1>
			<a class="material__create button" href="{{ route('material-issues.create') }}">
				<span>Новый расход</span>
			</a>
		</div>
		<div class="material">
			{{-- Список расходных ордеров --}}
			<div class="material__table-wrapper">
				<table class="material__table">
					<thead>
					<tr>
						<th>Дата</th>
						<th>Материалы</th>
						<th>Позиций</th>
						<th>Общий вес, кг</th>
						<th>Пользователь</th>
					</tr>
					</thead>
					<tbody>

					@forelse ($orders as $order)
						@php
							$first = $order->first();
						@endphp

						<tr class="material__material-row" data-row-link="{{ route('material-issues.show', $first) }}"
								tabindex="0" role="link">
							{{-- Дата --}}
							<td> {{ $first->created_at->format('d.m.Y H:i') }} </td>
							{{-- Материалы --}}
							<td>
								@foreach ($order->pluck('material.name')->unique() as $materialName)
									<div>{{ $materialName }}</div>
								@endforeach
							</td>
							{{-- Количество позиций --}}
							<td> {{ $order->count() }} </td>
							{{-- Общий вес --}}
							<td> {{ number_format($order->sum('weight'), 3, '.', '') }} </td>
							{{-- Пользователь --}}
							<td> {{ $first->user->name }} </td>
						</tr>

					@empty
						<tr>
							<td class="material__empty" colspan="5">
								Расходных ордеров пока нет
							</td>
						</tr>
					@endforelse
					</tbody>
				</table>
			</div>
		</div>
	</div>

@endsection
