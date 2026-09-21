@extends('layouts.app')

@section('title', 'Склад')

@section('content')

	<div class="main-content__content">

		{{-- Фильтр --}}
		@include('layouts.filters-actions')
		<div class="main-content__header">
			<h1 class="main-content__title">Склад</h1>
		</div>
		<div class="material">
			{{-- Таблица склада --}}
			<table class="material__table">

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
					@php
						/*
						 * Группы рулонов по формату: отдельная строка
						 * на каждый формат; без рулонов — одна строка «—».
						 */
						$formatGroups = $material->rolls
							->groupBy(fn ($roll) => $roll->format ?? '')
							->sortBy(fn ($group, $format) => (int) $format);
					@endphp

					@foreach ($formatGroups->isEmpty() ? collect([null]) : $formatGroups as $group)
						@php
							$groupRolls = $group ?? collect();
							$groupFormats = $groupRolls->pluck('format')->filter()->unique()->values();
							$groupIdentifiers = $groupRolls->pluck('identifier')->filter()->unique()->sort(SORT_STRING)->values();
						@endphp

						<tr class="material__material-row" data-row-link="{{ route('warehouse.material', $material) }}"
								tabindex="0" role="link">

							<td> {{ $material->name }} </td>
							<td> {{ $groupIdentifiers->implode(', ') ?: '—' }} </td>
							<td> {{ $groupFormats->implode(', ') ?: '—' }} </td>
							<td> {{ $groupRolls->count() }} </td>
							<td>
								{{ number_format($groupRolls->sum('weight'), 3, '.', '') }}
							</td>

						</tr>
					@endforeach

				@empty

					<tr>
						<td colspan="5">Склад пуст</td>
					</tr>

				@endforelse
				</tbody>
			</table>
		</div>
	</div>

@endsection