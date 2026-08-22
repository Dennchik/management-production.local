@extends('layouts.app')

@section('title', 'Склад')

@section('content')

	<div class="warehouse">

		{{-- Фильтр --}}
		@include('layouts.filters-actions')

		<h1 class="main-content__title">Склад</h1>

		{{-- Таблица склада --}}
		<table class="warehouse__table">

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

				<tr class="warehouse__material-row"
						data-row-link="{{ route('warehouse.material', $material) }}"
						tabindex="0"
						role="link">

					<td> {{ $material->name }} </td>
					<td> {{ $material->identifier }} </td>
					<td> {{ $material->format }} </td>
					<td> {{ $material->rolls_count }} </td>
					<td>
						{{ number_format($material->total_weight ?? 0, 3, '.', '') }}
					</td>

				</tr>

			@empty

				<tr>
					<td colspan="5">
						Склад пуст
					</td>
				</tr>

			@endforelse

			</tbody>

		</table>
	</div>
@endsection