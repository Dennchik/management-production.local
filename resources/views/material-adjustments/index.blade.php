@extends('layouts.app')

@section('title', 'Ордера корректировки')

@section('content')

	@php
		/*
		 * Строки одного сабмита с общим batch_id — один ордер.
		 * Без batch_id (старые записи) каждая строка показывается отдельно.
		 */
		$orders = $adjustments
			->groupBy(static fn ($adjustment) => $adjustment->batch_id ?? 'row-' . $adjustment->id)
			->map(static function ($rows) {
				$first = $rows->first();

				return (object) [
					'first' => $first,
					'rows' => $rows->sortBy('id')->values(),
					'date' => $rows->min('created_at'),
					'user' => $first->user,
					'comment' => $rows->pluck('comment')->filter()->first(),
				];
			})
			->sortByDesc('date')
			->values();
	@endphp

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
			{{-- Список ордеров корректировки: строка = ордер --}}
			<div class="material__table-wrapper">
				<table class="material__table">
					<thead>
					<tr>
						<th>Дата</th>
						<th>Позиций</th>
						<th>Материалы</th>
						<th>Корректировка, кг</th>
						<th>Комментарий</th>
						<th>Пользователь</th>
					</tr>
					</thead>
					<tbody>

					@forelse ($orders as $order)
						<tr class="material__material-row" data-row-link="{{ route('material-adjustments.show', $order->first) }}"
								tabindex="0" role="link">
							<td> {{ $order->date->format('d.m.Y H:i') }} </td>
							<td> {{ $order->rows->count() }} </td>
							<td>
								@foreach ($order->rows->pluck('material')->unique('id') as $material)
									<div>{{ $material->name }}</div>
								@endforeach
							</td>
							<td class="{{ $order->rows->sum('adjustment') < 0 ? 'text-red-soft' : '' }}">
								@php
									$total = round((float) $order->rows->sum('adjustment'), 3);
								@endphp
								{{ ($total > 0 ? '+' : '') . rtrim(rtrim(number_format($total, 3, '.', ''), '0'), '.') }}
							</td>
							<td> {{ $order->comment ?? '—' }} </td>
							<td> {{ $order->user?->name ?? '—' }} </td>
						</tr>

					@empty
						<tr>
							<td class="material__empty" colspan="6">
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
