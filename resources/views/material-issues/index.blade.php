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
						<th>Материал</th>
						<th>Рулон</th>
						<th>Пользователь</th>
					</tr>
					</thead>
					<tbody>

					@forelse ($issues as $issue)
						<tr class="material__material-row" data-issue-modal-open data-issue-id="{{ $issue->getKey()}}">
							{{-- Дата --}}
							<td> {{ $issue->created_at->format('d.m.Y H:i') }} </td>
							<td> {{ $issue->material->name }} </td>
							<td> {{ $issue->roll->roll_number }} </td>
							<td> {{ $issue->user->name }} </td>
						</tr>

					@empty
						<tr>
							<td class="material__empty" colspan="4">
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