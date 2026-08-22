@extends('layouts.app')

@section('title', 'Расходные ордера')

@section('content')

	<div class="material-issues">

		{{-- Фильтр --}}
		@include('layouts.filters-actions', [
			'filterType' => 'issues',
			'filterAction' => route('material-issues.index'),
			'filterReset' => route('material-issues.index'),
		])

		<h1 class="main-content__title">Расходные ордера</h1>

		{{-- Действия --}}
		<div class="material-issues__actions">

			<a
					class="material-issues__create button"
					href="{{ route('material-issues.create') }}">
				<span>Новый расход</span>
			</a>

		</div>

		{{-- Список расходных ордеров --}}
		<div class="material-issues__table-wrapper">

			<table class="material-issues__table">

				<thead>
				<tr>
					<th>Дата</th>
					<th>Материал</th>
					<th>Рулон</th>
					<th>Пользователь</th>
				</tr>
				</thead>

				<tbody>

				@forelse ($issues as $issue)

					<tr class="material-issues__row"
							data-receipt-modal-open
							data-receipt-id="{{ $issue->id }}">

						{{-- Дата --}}
						<td> {{ $issue->created_at->format('d.m.Y H:i') }} </td>
						<td> {{ $issue->material->name }} </td>
						<td> {{ $issue->roll->roll_number }} </td>
						<td> {{ $issue->user->name }} </td>
					</tr>

				@empty

					<tr>
						<td class="material-issues__empty" colspan="4">
							Расходных ордеров пока нет
						</td>
					</tr>

				@endforelse

				</tbody>

			</table>

		</div>

	</div>

@endsection