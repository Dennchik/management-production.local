@extends('layouts.app')

@section('title', 'Приходные ордера')

@section('content')

	<div class="material-receipts">

		{{-- Фильтр --}}
		@include('layouts.filters-actions', [
			'filterType' => 'receipts',
			'filterAction' => route('material-receipts.index'),
			'filterReset' => route('material-receipts.index'),
		])

		<h1 class="main-content__title">Приходные ордера</h1>

		{{-- Действия --}}
		<div class="material-receipts__actions">

			<a class="material-receipts__create button"
					href="{{ route('material-receipts.create') }}">
				<span>Новый приход</span>
			</a>

		</div>

		{{-- Список приходных ордеров --}}
		<div class="material-receipts__table-wrapper">

			<table class="material-receipts__table">

				<thead>
				<tr>
					<th>Дата</th>
					<th>Материал</th>
					<th>Рулоны</th>
					<th>Общий вес</th>
					<th>Пользователь</th>
				</tr>
				</thead>

				<tbody>
				{{-- @var \App\Models\MaterialReceipt $receipt --}}
				@forelse ($receipts as $receipt)

					<tr class="material-receipts__row"
							data-receipt-modal-open
							data-receipt-id="{{ $receipt->getKey()}}">

						{{-- Дата --}}
						<td> {{ $receipt->created_at->format('d.m.Y H:i') }} </td>

						{{-- Материалы --}}
						<td>

							@foreach ($receipt->items->unique('material_id') as $item)

								<div>
									{{ $item->material->name }}
								</div>

							@endforeach

						</td>

						{{-- Количество рулонов --}}
						<td>
							{{ $receipt->items->count() }}
						</td>

						{{-- Общий вес --}}
						<td>
							{{ number_format($receipt->items->sum('weight'), 3, '.', '') }}
							кг
						</td>

						{{-- Пользователь --}}
						<td>
							{{ $receipt->user->name }}
						</td>

					</tr>

				@empty

					<tr>
						<td class="material-receipts__empty" colspan="5">
							Приходных ордеров пока нет
						</td>
					</tr>

				@endforelse

				</tbody>

			</table>

		</div>

	</div>

@endsection