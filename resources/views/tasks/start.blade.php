@extends('layouts.app')

@section('title', 'Начало задачи №' . $task->id)

@section('content')
	<form class="issue-order" method="POST" action="{{ route('tasks.start', $task) }}" data-order-form>
		<div class="issue-order__header">
			<h1 class="main-content__title">
				Начало задачи №{{ $task->id }} — {{ $task->material?->name }} ({{ rtrim(rtrim($task->quantity, '0'), '.') }} кг)
			</h1>
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
			<p>Укажите, какой рулон берёте по каждому входному материалу:</p>

			@foreach ($recipeInputs as $input)
				@php
					$rolls = $rollsByMaterial[$input->material_id] ?? collect();
					$planned = $input->quantity !== null
							? round((float) $input->quantity * (float) $task->quantity, 3)
							: null;
				@endphp
				<div class="issue-order__line">
					<fieldset class="issue-order__field">
						<label class="issue-order__label">{{ $input->material?->name }}</label>

						<select class="catalogs__parent-select" name="inputs[{{ $loop->index }}][roll_id]">
							<option value="">— Не выбран —</option>
							@foreach ($rolls as $roll)
								<option value="{{ $roll->id }}">
									{{ $roll->roll_number }} ({{ rtrim(rtrim($roll->weight, '0'), '.') }} кг)
								</option>
							@endforeach
						</select>
					</fieldset>

					<fieldset class="issue-order__field">
						<label class="issue-order__label">Плановый вес, кг</label>
						<input class="issue-order__input" type="number" step="0.001" min="0"
								name="inputs[{{ $loop->index }}][planned_weight]" value="{{ $planned }}">
						<input type="hidden" name="inputs[{{ $loop->index }}][material_id]" value="{{ $input->material_id }}">
					</fieldset>
				</div>
			@endforeach

			@if ($recipeInputs->isEmpty())
				<p>Для этого материала нет рецепта с входным сырьём — задача будет начата без входов.</p>
			@endif

			{{-- Скрытые поля, чтобы validation «inputs required» проходила --}}
			@if ($recipeInputs->isEmpty())
				<input type="hidden" name="inputs[0][material_id]" value="{{ $task->material_id }}">
			@endif

			<div class="issue-order__actions">
				<button class="issue-order__button main-content__button button" type="submit">
					<span>Начать</span>
				</button>

				<a class="issue-order__button issue-order__button--reset button" href="{{ route('tasks.show', $task) }}">
					<span>Отмена</span>
				</a>
			</div>
		</div>
	</form>
@endsection
