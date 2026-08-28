@extends('layouts.app')

@section('title', 'Ламинация')

@section('content')

	<div class="lamination">
		<h1 class="main-content__title">Ламинация</h1>

		{{-- Действия --}}
		<div class="lamination__actions">
			<a class="lamination__create button" href="{{ route('lamination.create') }}">
				<span>Новое задание</span>
			</a>
		</div>

		{{-- Список заданий --}}
		<div class="lamination__table-wrapper">
			<table class="lamination__table">
				<thead>
				<tr>
					<th>Дата</th>
					<th>Номер задания</th>
					<th>ПФ</th>
					<th>Статус</th>
					<th>Пользователь</th>
				</tr>
				</thead>
				<tbody>
				@forelse ($laminations as $lamination)
					<tr class="lamination__row">
						<td>{{ $lamination->created_at->format('d.m.Y H:i') }}</td>
						<td>{{ $lamination->job_number }}</td>
						<td>{{ $lamination->resultMaterial->name ?? '—' }}</td>
						<td>{{ $lamination->status }}</td>
						<td>{{ $lamination->user->name }}</td>
					</tr>
				@empty
					<tr>
						<td class="lamination__empty" colspan="5">Заданий на ламинацию пока нет</td>
					</tr>
				@endforelse
				</tbody>
			</table>
		</div>
	</div>

@endsection