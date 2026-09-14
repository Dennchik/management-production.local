@extends('layouts.app')

@section('title', 'Завершение задачи №' . $task->id)

@section('content')
	<form class="issue-order" method="POST" action="{{ route('tasks.complete', $task) }}" data-order-form>
		<div class="issue-order__header">
			<h1 class="main-content__title">Завершение задачи №{{ $task->id }} — {{ $task->material?->name }}</h1>
		</div>
		@csrf

		@if ($errors->any())
			<div class="message message--error" role="alert">
				@foreach ($errors->all() as $error)
					<p style="margin: 0; color: #b00;">{{ $error }}</p>
				@endforeach
			</div>
		@endif

		<div class="issue-order__body">
			<p>Укажите фактический вес использованного сырья и произведённой продукции.
				При завершении сырьё будет списано, продукция оприходована на склад.</p>

			{{-- Фактические веса входов --}}
			@foreach ($task->inputs as $input)
				<div class="issue-order__line">
					<fieldset class="issue-order__field">
						<label class="issue-order__label">
							{{ $input->material?->name }} — рулон {{ $input->roll?->roll_number }}
							(остаток {{ $input->roll ? rtrim(rtrim($input->roll->weight, '0'), '.') : '—' }} кг)
						</label>
						<input class="issue-order__input" type="number" step="0.001" min="0"
								name="inputs[{{ $loop->index }}][actual_weight]"
								value="{{ $input->planned_weight ?? $input->actual_weight ?? '' }}">
						<input type="hidden" name="inputs[{{ $loop->index }}][id]" value="{{ $input->id }}">
					</fieldset>
				</div>
			@endforeach

			{{-- Выходные рулоны --}}
			<div class="issue-order__line">
				<fieldset class="issue-order__field" style="flex: 1;">
					<label class="issue-order__label">Произведённые рулоны</label>

					<div data-output-rolls>
						<div class="issue-order__line" data-output-roll>
							<fieldset class="issue-order__field">
								<label class="issue-order__label">Номер рулона</label>
								<input class="issue-order__input" type="text" name="outputs[0][roll_number]">
							</fieldset>

							<fieldset class="issue-order__field">
								<label class="issue-order__label">Вес, кг</label>
								<input class="issue-order__input" type="number" step="0.001" min="0.001"
										name="outputs[0][actual_weight]" value="{{ $plannedOutput }}">
							</fieldset>
						</div>
					</div>

					<button class="button button--secondary" type="button" data-output-roll-add>
						+ Ещё рулон
					</button>
				</fieldset>
			</div>

			<div class="issue-order__actions">
				<button class="issue-order__button main-content__button button" type="submit">
					<span>Завершить задачу</span>
				</button>

				<a class="issue-order__button issue-order__button--reset button" href="{{ route('tasks.show', $task) }}">
					<span>Отмена</span>
				</a>
			</div>
		</div>
	</form>
@endsection
