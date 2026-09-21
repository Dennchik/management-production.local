@extends('layouts.app')

@section('title', 'Задача №' . $task->number)

@section('content')
	@php
		$made = rtrim(rtrim(number_format($task->producedWeight(), 3, '.', ''), '0'), '.');
		$plan = rtrim(rtrim($task->quantity, '0'), '.');
		$left = rtrim(rtrim(number_format($task->quantity - $task->producedWeight(), 3, '.', ''), '0'), '.');
		$isInProgress = $task->status === 'in_progress';
	@endphp

	<div class="main-content__content materials">
		<div class="main-content__header">
			<h1 class="main-content__title">Задача №{{ $task->number }} — {{ $task->material?->name }}</h1>

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
						<th style="width: 220px; text-align: left;">Линия</th>
						<td>{{ $task->productionLine?->operation?->name ?? '—' }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Шаблон производства</th>
						<td>{{ $task->productionLine?->name ?? '—' }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Материал (продукция)</th>
						<td>{{ $task->material?->name }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Количество, кг</th>
						<td data-task-progress>
							<span data-task-progress-made>{{ $made }}</span> из {{ $plan }}
							<span class="{{ $task->isShort() ? 'text-red-soft' : '' }}"
									style="{{ $task->isShort() ? '' : 'color: var(--text-muted);' }}"
									data-task-progress-left>осталось {{ $left }}</span>
						</td>
					</tr>
					<tr>
						<th style="text-align: left;">Оператор</th>
						<td>{{ $task->operator?->name ?? '—' }}</td>
					</tr>
					<tr>
						<th style="text-align: left;">Статус</th>
						<td>
							<span class="status-chip status-chip--{{ $task->statusClass() }}">{{ $task->statusLabel() }}</span>
						</td>
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
				@if ($task->status === 'pending' && auth()->user()?->may('tasks', 'execute'))
					<form method="POST" action="{{ route('tasks.start', $task) }}" style="display: inline;">
						@csrf
						<button class="button button--primary" type="submit">
							<span>Начать задачу</span>
						</button>
					</form>

					<p style="margin: 0.5rem 0 0; color: var(--text-muted);">
						Рулоны берутся прямо на странице задачи после старта.
					</p>
				@endif

				@if ($task->isEditable() && auth()->user()?->may('tasks', 'edit'))
					<a class="button button--primary" href="{{ route('tasks.edit', $task) }}">
						<span>Редактировать</span>
					</a>
				@endif

				@if ($task->status === 'pending' && auth()->user()?->may('tasks', 'cancel'))
					<form method="POST" action="{{ route('tasks.cancel', $task) }}" style="display: inline;"
							onsubmit="return confirm('Отменить задачу №{{ $task->number }}?');">
						@csrf
						<button class="button button--secondary" type="submit">
							<span>Отменить задачу</span>
						</button>
					</form>
				@endif
			</div>
		</div>

		@if ($isInProgress || $task->inputs->isNotEmpty())
			@if ($isInProgress)
				{{-- Завершение задачи — прямо на её странице; расход и продукция сохраняются через AJAX сразу --}}
				<form method="POST"
						action="{{ route('tasks.complete', $task) }}"
						data-task-complete
						data-task-id="{{ $task->id }}"
						onsubmit="return confirm('Завершить задачу? Сырьё будет списано, продукция — оприходована.');">
					@csrf

					<div data-task-inputs-section id="task-inputs-section">
						<div class="main-content__header" style="margin-top: 2rem;">
							<h2 class="main-content__title" style="font-size: 1.3rem;">Взятое сырьё (рулоны)</h2>
							<span data-task-save-status
									style="font-size: var(--font-size-small); color: var(--text-muted);"></span>
						</div>

						<p style="margin: 0 0 1rem; color: var(--text-muted);">
							Укажите расход или остаток по каждому рулону — второе поле пересчитается само и сразу сохранится.
							Если расход больше веса рулона, остаток уходит в минус.
							Рулон, взятый другой задачей, показан внизу списка и недоступен.
						</p>

						<div data-task-add-error hidden style="margin: 0 0 1rem; color: var(--alarm);"></div>

						<div class="materials__content">
							<div class="materials__table">
								<table>
									<thead>
									<tr>
										<th>Рулон</th>
										<th>Вес рулона, кг</th>
										<th>Расход, кг</th>
										<th>Остаток, кг</th>
									</tr>
									</thead>
									<tbody>
									@foreach ($task->inputMaterials as $material)
										@php
											$materialInputs = $task->inputs->where('material_id', $material->id)->values();
											$materialRolls = $availableRolls[$material->id] ?? collect();
										@endphp

										<tr>
											<th colspan="4" style="text-align: left;">
												{{ $material->name }}@if ($material->pivot->format)
													— {{ $material->pivot->format }}
												@endif
											</th>
										</tr>

										@foreach ($materialInputs as $input)
											@php
												$rollWeight = (string) ($input->roll?->weight ?? 0);
												$savedUsed = (float) ($input->actual_weight ?? 0);
												$index = $input->id;
											@endphp

											<tr data-task-roll data-roll-weight="{{ $rollWeight }}">
												<td>{{ $input->roll?->roll_number ?? '—' }}</td>
												<td>{{ $input->roll ? rtrim(rtrim(number_format((float) $rollWeight, 3, '.', ''), '0'), '.') : '—' }}</td>
												<td>
													<input class="issue-order__input" type="number" step="0.001" min="0"
															style="min-height: 36px; width: 100%;"
															name="inputs[{{ $index }}][used]" data-roll-used
															value="{{ old('inputs.' . $index . '.used', rtrim(rtrim(number_format($savedUsed, 3, '.', ''), '0'), '.')) }}">
												</td>
												<td>
													<input class="issue-order__input" type="number" step="0.001"
															style="min-height: 36px; width: 100%;"
															name="inputs[{{ $index }}][remaining]" data-roll-remaining
															value="{{ old('inputs.' . $index . '.remaining', rtrim(rtrim(number_format((float) $rollWeight - $savedUsed, 3, '.', ''), '0'), '.')) }}">
													<input type="hidden" name="inputs[{{ $index }}][id]" value="{{ $input->id }}">
												</td>
											</tr>
										@endforeach

										@if ($materialRolls->isNotEmpty())
											<tr>
												<td colspan="4" style="padding: 6px 8px;">
													<div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
														<div class="select material-select"
																style="flex: 1 1 220px;"
																data-task-roll-select>
															<input class="select__value" type="hidden" name="inputs[0][roll_id]"
																	value="" form="task-input-form-{{ $material->id }}">

															<button class="material-select__select-button select__button select-button"
																	type="button" aria-haspopup="listbox" aria-expanded="false">
																<span class="material-select__select-value select__button-text">— Рулон не выбран —</span>
																<span class="material-select__select-arrow" aria-hidden="true"></span>
															</button>

															<div class="select__dropdown material-select__select-list _collapse"
																	role="listbox">
																@foreach ($materialRolls as $roll)
																	@php $takenBy = $roll->taken_by ?? null; @endphp
																	<button class="material-select__select-option select__item"
																			type="button"
																			role="option"
																			data-value="{{ $takenBy ? '' : $roll->id }}"
																			data-search="{{ strtolower($roll->roll_number) }}"
																			@if ($takenBy) disabled @endif>
																		<span>{{ $roll->roll_number }} ({{ rtrim(rtrim($roll->weight, '0'), '.') }} кг)</span>
																		@if ($takenBy)
																			<small style="display: block; font-size: 1.1rem; color: var(--text-muted);">
																				взята в задачу №{{ $takenBy }}
																			</small>
																		@endif
																	</button>
																@endforeach
																<div class="material-select__select-empty select__empty" hidden>Ничего
																	не найдено
																</div>
															</div>
														</div>

														<button class="button"
																type="submit"
																form="task-input-form-{{ $material->id }}">
															<span>Взять рулон</span>
														</button>
													</div>
												</td>
											</tr>
										@else
											<tr>
												<td colspan="4" style="padding: 6px 8px; color: var(--text-muted);">
													Свободных рулонов этого формата нет.
												</td>
											</tr>
										@endif
									@endforeach
									</tbody>
								</table>
							</div>
						</div>

						<div class="main-content__header" style="margin-top: 2rem;">
							<h2 class="main-content__title" style="font-size: 1.3rem;">Произведённые рулоны</h2>
							<span data-output-save-status
									style="font-size: var(--font-size-small); color: var(--text-muted);"></span>
						</div>

					</div>


					<p style="margin: 0 0 1rem; color: var(--text-muted);">
						Каждый рулон сохраняется сразу — правки не теряются при обновлении страницы.
					</p>

					<div class="task-outputs-section" data-task-outputs-section id="task-outputs-section">
						<div class="issue-order__body">
							<div data-output-rolls data-task-number="{{ $task->number }}">
								@foreach ($task->outputs as $output)
									<div class="issue-order__line" data-output-roll data-output-id="{{ $output->id }}">
										<fieldset class="issue-order__field">
											<label class="issue-order__label">Номер рулона</label>
											<input class="issue-order__input" type="text" data-output-number
													value="{{ $output->roll_number }}">
										</fieldset>

										<fieldset class="issue-order__field">
											<label class="issue-order__label">Вес, кг</label>
											<input class="issue-order__input" type="number" step="0.001" min="0"
													data-output-weight
													value="{{ $output->actual_weight !== null ? rtrim(rtrim(number_format((float) $output->actual_weight, 3, '.', ''), '0'), '.') : '' }}">
										</fieldset>

										<fieldset class="issue-order__field">
											<button class="button" type="button" data-output-roll-remove>
												<span>Убрать</span>
											</button>
										</fieldset>
									</div>
								@endforeach
							</div>

							<button class="button button--secondary" type="button" data-output-roll-add>
								+ Ещё рулон
							</button>
						</div>
					</div>

					<div class="main-content__actions">
						<button class="issue-order__button main-content__button button" type="submit">
							<span>Завершить задачу</span>
						</button>

						@if (auth()->user()?->may('tasks', 'edit'))
							<p style="margin: 0; color: var(--text-muted);">
								Задачу можно завершить с недобором — тогда статус будет помечен светло-красным.
							</p>
						@endif
					</div>
				</form>

				{{-- Формы дозабора: поля живут в таблице выше и привязаны атрибутом form --}}
				@foreach ($task->inputMaterials as $material)
					@if (($availableRolls[$material->id] ?? collect())->isNotEmpty())
						<form id="task-input-form-{{ $material->id }}" method="POST"
								action="{{ route('tasks.inputs.store', $task) }}" data-task-input-form>
							@csrf
							<input type="hidden" name="inputs[0][material_id]" value="{{ $material->id }}">
						</form>
					@endif
				@endforeach
			@else
				{{-- Завершённая задача: фактический расход по рулонам --}}
				<div class="main-content__header" style="margin-top: 2rem;">
					<h2 class="main-content__title" style="font-size: 1.3rem;">Взятое сырьё (рулоны)</h2>
				</div>

				<div class="materials__content">
					<div class="materials__table">
						<table>
							<thead>
							<tr>
								<th>Рулон</th>
								<th>Остаток рулона, кг</th>
								<th>Расход, кг</th>
							</tr>
							</thead>
							<tbody>
							@foreach ($task->inputMaterials as $material)
								<tr>
									<th colspan="3" style="text-align: left;">
										{{ $material->name }}@if ($material->pivot->format)
											— {{ $material->pivot->format }}
										@endif
									</th>
								</tr>

								@foreach ($task->inputs->where('material_id', $material->id) as $input)
									<tr>
										<td>{{ $input->roll?->roll_number ?? '—' }}</td>
										<td>{{ $input->roll ? rtrim(rtrim($input->roll->weight, '0'), '.') : '—' }}</td>
										<td>{{ $input->actual_weight !== null ? rtrim(rtrim($input->actual_weight, '0'), '.') : '—' }}</td>
									</tr>
								@endforeach
							@endforeach
							</tbody>
						</table>
					</div>
				</div>
			@endif
		@endif

		@if ($task->outputs->isNotEmpty() && !$isInProgress)
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
