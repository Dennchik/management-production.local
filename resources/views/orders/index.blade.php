@extends('layouts.app')

@section('title', 'Заказы клиентов')

@section('content')
	<div class="main-content__content materials">
		<div class="main-content__header">
			<h1 class="main-content__title">Заказы клиентов</h1>

			<a class="button button--primary materials__create" href="{{ route('orders.create') }}">
				<i class="icon icon-plus" aria-hidden="true"></i>
				<span>Создать заказ</span>
			</a>
		</div>

		@include('partials.message')

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>Клиент</th>
						<th>Адрес</th>
						<th>Позиции</th>
						<th>Статус</th>
						<th>Дата</th>
					</tr>
					</thead>

					<tbody>
					@if ($orders->isEmpty())
						<tr class="materials__empty">
							<td colspan="6">Заказов нет.</td>
						</tr>
					@else
						@foreach ($orders as $order)
							<tr style="cursor: pointer;"
									onclick="window.location='{{ route('orders.show', $order) }}'">
								<td>{{ $order->id }}</td>
								<td>{{ $order->client_name }}</td>
								<td>{{ $order->address ?? '—' }}</td>
								<td>
									@foreach ($order->items as $item)
										{{ $loop->first ? '' : '; ' }}
										{{ $item->material?->name }} —
										{{ rtrim(rtrim($item->quantity, '0'), '.') }} {{ $item->unit }}
									@endforeach
								</td>
								<td>{{ $order->statusLabel() }}</td>
								<td>{{ $order->created_at->format('d.m.Y') }}</td>
							</tr>
						@endforeach
					@endif
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
