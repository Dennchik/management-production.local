@extends('layouts.app')

@section('title', 'Задача №' . $task->id)

@section('content')
	<div class="main-content__content materials">
		<div class="main-content__header">
			<h1 class="main-content__title">Задача №{{ $task->id }} — {{ $task->material?->name }}</h1>

			{{--			@if ($task->isEditable() && auth()->user()?->may('tasks', 'edit'))--}}
			{{--				<a class="button button--primary" href="{{ route('tasks.edit', $task) }}">--}}
			{{--					<span>Редактировать</span>--}}
			{{--				</a>--}}
			{{--			@endif--}}

			<a class="button button--secondary" href="{{ route('tasks.index') }}">
				<span>К списку задач</span>
			</a>
		</div>

		@include('partials.message')

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<tbody>
					<tr>
						<th style="width: 220px; text-align: left;">Заказ</th>
						<td>
							@if ($task->order_id !== null)
								№{{ $task->order_id }}
							@else
								—
							@endif
						</td>
					</tr>
					<tr>
						<th style="text-align: left;">Материал (продукция)</th>
						<td>{{ $task->material?->name }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Количество, кг</th>
						<td>{{ rtrim(rtrim($task->quantity, '0'), '.') }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Станок</th>
						<td>{{ $task->machine?->name ?? '—' }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Статус</th>
						<td>{{ $task->statusLabel() }}</td>
					</tr>
					@if ($task->started_at)
						<tr>
							<th style="text-align: left;">Начата</th>
							<td>{{ $task->started_at->format('d.m.Y H:i') }}</td>
						</tr>
					@endif
					@if ($task->completed_at)
						<tr>
							<th style="text-align: left;">Завершена</th>
							<td>{{ $task->completed_at->format('d.m.Y H:i') }}</td>
						</tr>
					@endif
					@if ($task->comment)
						<tr>
							<th style="text-align: left;">Комментарий</th>
							<td>{{ $task->comment }}</td>
						</tr>
					@endif
					</tbody>
				</table>
			</div>
			<div class="materials-action">
				@if ($task->status === 'pending')
					<a class="button button--primary" href="{{ route('tasks.start-form', $task) }}">
						<span>Начать задачу</span>
					</a>
				@elseif ($task->status === 'in_progress')
					<a class="button button--primary" href="{{ route('tasks.complete-form', $task) }}">
						<span>Завершить задачу</span>
					</a>
				@endif

				@if ($task->isEditable() && auth()->user()?->may('tasks', 'edit'))
					<a class="button button--primary" href="{{ route('tasks.edit', $task) }}">
						<span>Редактировать</span>
					</a>
				@endif
			</div>
		</div>

		{{-- Входные рулоны --}}
		@if ($task->inputs->isNotEmpty())
			<div class="main-content__header" style="margin-top: 2rem;">
				<h2 class="main-content__title" style="font-size: 1.3rem;">Взятое сырьё (рулоны)</h2>
			</div>

			<div class="materials__content">
				<div class="materials__table">
					<table>
						<thead>
						<tr>
							<th>Материал</th>
							<th>Рулон</th>
							<th>Остаток рулона, кг</th>
							<th>Планируемый вес, кг</th>
							@if ($task->status === 'done')
								<th>Фактически, кг</th>
							@endif
						</tr>
						</thead>
						<tbody>
						@foreach ($task->inputs as $input)
							<tr>
								<td>{{ $input->material?->name }}</td>
								<td>{{ $input->roll?->roll_number ?? '—' }}</td>
								<td>{{ $input->roll ? rtrim(rtrim($input->roll->weight, '0'), '.') : '—' }}</td>
								<td>{{ $input->planned_weight !== null ? rtrim(rtrim($input->planned_weight, '0'), '.') : '—' }}</td>
								@if ($task->status === 'done')
									<td>{{ $input->actual_weight !== null ? rtrim(rtrim($input->actual_weight, '0'), '.') : '—' }}</td>
								@endif
							</tr>
						@endforeach
						</tbody>
					</table>
				</div>
			</div>
		@endif

		{{-- Выходные рулоны --}}
		@if ($task->outputs->isNotEmpty())
			<div class="main-content__header" style="margin-top: 2rem;">
				<h2 class="main-content__title" style="font-size: 1.3rem;">Произведённые рулоны</h2>
			</div>

			<div class="materials__content">
				<div class="materials__table">
					<table>
						<thead>
						<tr>
							<th>Номер рулона</th>
							<th>Вес, кг</th>
						</tr>
						</thead>
						<tbody>
						@foreach ($task->outputs as $output)
							<tr>
								<td>{{ $output->roll_number }}</td>
								<td>{{ rtrim(rtrim($output->actual_weight, '0'), '.') }}</td>
							</tr>
						@endforeach
						</tbody>
					</table>
				</div>
			</div>
		@endif
	</div>
@endsection
