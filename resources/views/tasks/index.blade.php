@extends('layouts.app')

@section('title', 'Производственные задачи')

@section('content')
	<div class="main-content__content materials">
		<div class="main-content__header">
			<h1 class="main-content__title">Производственные задачи</h1>

			<a class="button button--primary materials__create" href="{{ route('tasks.create') }}">
				<i class="icon icon-plus" aria-hidden="true"></i>
				<span>Создать задачу</span>
			</a>
		</div>

		@include('partials.message')

		@if ($ownMachineOnly)
			<p>Показаны задачи вашего станка.</p>
		@endif

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>Заказ</th>
						<th>Материал</th>
						<th>Количество, кг</th>
						<th>Станок</th>
						<th>Статус</th>
					</tr>
					</thead>

					<tbody>
					@if ($tasks->isEmpty())
						<tr class="materials__empty">
							<td colspan="6">Задач нет.</td>
						</tr>
					@else
						@foreach ($tasks as $task)
							<tr style="cursor: pointer;" onclick="window.location='{{ route('tasks.show', $task) }}'">
								<td>{{ $task->id }}</td>
								<td>{{ $task->order_id !== null ? '№' . $task->order_id : '—' }}</td>
								<td>{{ $task->material?->name }}</td>
								<td>{{ rtrim(rtrim($task->quantity, '0'), '.') }}</td>
								<td>{{ $task->machine?->name ?? '—' }}</td>
								<td>{{ $task->statusLabel() }}</td>
							</tr>
						@endforeach
					@endif
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
