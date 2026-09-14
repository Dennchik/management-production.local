@extends('layouts.app')

@section('title', 'Заказ №' . $order->id)

@section('content')
	<div class="main-content__content materials">
		<div class="main-content__header">
			<h1 class="main-content__title">Заказ №{{ $order->id }} — {{ $order->client_name }}</h1>

			<a class="button button--secondary" href="{{ route('orders.index') }}">
				<span>К списку заказов</span>
			</a>
		</div>

		@include('partials.message')

		{{-- Шапка заказа --}}
		<div class="materials__content">
			<div class="materials__table">
				<table>
					<tbody>
					<tr>
						<th style="width: 220px; text-align: left;">Клиент</th>
						<td>{{ $order->client_name }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Адрес</th>
						<td>{{ $order->address ?? '—' }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Статус</th>
						<td>{{ $order->statusLabel() }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Комментарий</th>
						<td>{{ $order->comment ?? '—' }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Создан</th>
						<td>{{ $order->created_at->format('d.m.Y H:i') }}</td>
					</tr>
					</tbody>
				</table>
			</div>
		</div>

		{{-- Позиции заказа --}}
		<div class="main-content__header" style="margin-top: 2rem;">
			<h2 class="main-content__title" style="font-size: 1.3rem;">Позиции заказа</h2>
		</div>

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>Материал</th>
						<th>Количество</th>
					</tr>
					</thead>
					<tbody>
					@foreach ($order->items as $item)
						<tr>
							<td>{{ $loop->iteration }}</td>
							<td>{{ $item->material?->name }}</td>
							<td>{{ rtrim(rtrim($item->quantity, '0'), '.') }} {{ $item->unit }}</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			</div>
		</div>

		{{-- Задачи заказа --}}
		<div class="main-content__header" style="margin-top: 2rem;">
			<h2 class="main-content__title" style="font-size: 1.3rem;">Производственные задачи</h2>

			<form method="POST" action="{{ route('orders.tasks', $order) }}">
				@csrf
				<button class="button button--primary" type="submit">
					<span>Создать задачи по позициям</span>
				</button>
			</form>
		</div>

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>Материал</th>
						<th>Количество</th>
						<th>Станок</th>
						<th>Статус</th>
					</tr>
					</thead>
					<tbody>
					@if ($tasks->isEmpty())
						<tr class="materials__empty">
							<td colspan="5">Задач по заказу ещё нет.</td>
						</tr>
					@else
						@foreach ($tasks as $task)
							<tr style="cursor: pointer;" onclick="window.location='{{ route('tasks.show', $task) }}'">
								<td>{{ $task->id }}</td>
								<td>{{ $task->material?->name }}</td>
								<td>{{ rtrim(rtrim($task->quantity, '0'), '.') }} кг</td>
								<td>{{ $task->machine?->name ?? '—' }}</td>
								<td>{{ $task->statusLabel() }}</td>
							</tr>
						@endforeach
					@endif
					</tbody>
				</table>
			</div>
		</div>

		{{-- Смена статуса --}}
		<div class="main-content__header" style="margin-top: 2rem;">
			<h2 class="main-content__title" style="font-size: 1.3rem;">Смена статуса</h2>
		</div>

		<form class="issue-order" method="POST" action="{{ route('orders.status', $order) }}" style="max-width: 480px;">
			@csrf

			<div class="issue-order__body">
				<div class="issue-order__line">
					<fieldset class="issue-order__field">
						<label class="issue-order__label" for="status">Статус заказа</label>
						<select class="catalogs__parent-select" id="status" name="status">
							@foreach ($statuses as $statusValue => $statusLabel)
								<option value="{{ $statusValue }}" {{ $order->status === $statusValue ? 'selected' : '' }}>
									{{ $statusLabel }}
								</option>
							@endforeach
						</select>
					</fieldset>

					<fieldset class="issue-order__field" style="align-self: flex-end;">
						<button class="button" type="submit">
							<span>Сохранить</span>
						</button>
					</fieldset>
				</div>
			</div>
		</form>

		{{-- Рецепты производства --}}
		<div class="main-content__header" style="margin-top: 2rem;">
			<h2 class="main-content__title" style="font-size: 1.3rem;">Как производятся материалы заказа</h2>
		</div>

		<div class="materials__content">
			@if ($recipes->isEmpty())
				<p>Для материалов заказа пока нет технологических операций с их выпуском на выходе.</p>
			@else
				@foreach ($recipes as $recipe)
					<div class="materials__table" style="margin-bottom: 1.5rem;">
						<table>
							<thead>
							<tr>
								<th colspan="4" style="text-align: left;">
									Операция: {{ $recipe->name }} @if ($recipe->code) ({{ $recipe->code }}) @endif
								</th>
							</tr>
							<tr>
								<th style="text-align: left;">Направление</th>
								<th style="text-align: left;">Материал</th>
								<th style="text-align: left;">Количество</th>
								<th style="text-align: left;">Обязательный</th>
							</tr>
							</thead>
							<tbody>
							@foreach ($recipe->components->sortBy('direction') as $component)
								<tr>
									<td>{{ $component->direction === 'input' ? 'Вход (сырьё)' : 'Выход (продукция)' }}</td>
									<td>{{ $component->material?->name }}</td>
									<td>
										@if ($component->quantity !== null)
											{{ rtrim(rtrim($component->quantity, '0'), '.') }} {{ $component->unit }}
										@else
											—
										@endif
									</td>
									<td>{{ $component->is_required ? 'Да' : 'Нет' }}</td>
								</tr>
							@endforeach
							</tbody>
						</table>
					</div>
				@endforeach
			@endif
		</div>
	</div>
@endsection
