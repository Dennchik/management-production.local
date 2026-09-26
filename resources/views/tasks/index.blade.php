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

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>Материал</th>
						<th>Сделано / план, кг</th>
						<th>Оператор</th>
						<th>Статус</th>
					</tr>
					</thead>

					<tbody>
					@if ($tasks->isEmpty())
						<tr class="materials__empty">
							<td colspan="5">Задач нет.</td>
						</tr>
					@else
						@foreach ($tasks as $task)
							@php
								$made = rtrim(rtrim(number_format((float) ($task->produced_weight ?? 0), 3, '.', ''), '0'), '.');
								$plan = rtrim(rtrim($task->quantity, '0'), '.');
								// Перевыполнение не уводит «осталось» в минус
								$left = rtrim(rtrim(number_format(max(0, (float) $task->quantity - (float) ($task->produced_weight ?? 0)), 3, '.', ''), '0'), '.');
							@endphp

								<tr style="cursor: pointer;" onclick="window.location='{{ route('tasks.show', $task) }}'">
									<td>{{ $task->number }}</td>
									<td>{{ $task->material?->name }}</td>
									<td>{{ $made }} из {{ $plan }} <span class="{{ $task->isShort() ? 'text-red-soft' : '' }}"
											style="{{ $task->isShort() ? '' : 'color: var(--text-muted);' }}">осталось {{ $left }}</span></td>
									<td>{{ $task->operator?->name ?? '—' }}</td>
									<td><span class="status-chip status-chip--{{ $task->statusClass() }}">{{ $task->statusLabel() }}</span></td>
								</tr>
						@endforeach
					@endif
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
