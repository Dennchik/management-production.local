@php
	/*
	 * Форма производственной задачи.
	 *
	 * Поток: линия → шаблон производства → входы/выход по шаблону
	 * (заблокированы для правки) + количество выходного материала.
	 * Кнопка «Изменить материалы вручную» открывает состав задачи
	 * для правки — изменение действует только для этой задачи.
	 *
	 * Переменные: $task, $operations, $templates, $allowedMaterials,
	 * $allMaterials, $products, $operators, $submitLabel, $cancelUrl.
	 */
	$task = $task ?? null;
	$isPending = $task === null || $task->status === 'pending';

	$selectedLineId = old('production_line_id', $task?->production_line_id);
	$selectedOperationId = $selectedLineId
		? ($templates[(string) $selectedLineId]['operationId'] ?? null)
		: null;

	$selectedTemplateName = null;
	$selectedOperatorId = old('operator_id', $task?->operator_id);

	foreach (($operations ?? []) as $operation) {
		foreach ($operation->productionLines as $line) {
			if ((string) $selectedLineId === (string) $line->id) {
				$selectedTemplateName = $line->name;
			}
		}
	}

	/*
	 * Строки таблиц материалов: после ошибки валидации — из old(),
	 * иначе материалы задачи.
	 */
	$rowsFromOld = function (array $ids, $source) {
		$ids = array_values(array_filter($ids, static fn ($value) => $value !== null && $value !== ''));

		return collect($ids)->map(static function ($id) use ($source) {
			$material = $source->first(static fn ($item) => (int) $item->id === (int) $id);

			if ($material === null) {
				return null;
			}

			$clone = clone $material;
			$clone->setRelation('pivot', new \Illuminate\Database\Eloquent\Relations\Pivot([
				'direction' => 'input',
			]));

			return $clone;
		})->filter();
	};

	$oldInputs = old('materials');
	$oldOutputs = old('output_materials');

	$inputRows = $oldInputs !== null
		? $rowsFromOld($oldInputs, $allMaterials)
		: ($task?->inputMaterials ?? collect());

	$outputRows = $oldOutputs !== null
		? $rowsFromOld($oldOutputs, $products)
		: ($task?->outputMaterials ?? collect());

	$sectionsHidden = $inputRows->isEmpty() && $outputRows->isEmpty();
@endphp

<div class="production-task__body">

	@if ($isPending)
		{{-- Номер / Линия / Шаблон / Оператор / Количество --}}
		<div class="production-task__line">
			<fieldset class="production-task__field">
				<label class="production-task__label" for="task-number">Номер</label>

				<input class="production-task__input" id="task-number" name="number" type="number"
						min="1" step="1" value="{{ old('number', $task->number ?? $nextNumber ?? '') }}" required>
			</fieldset>

			<fieldset class="production-task__field">
				<label class="production-task__label" for="task-operation">Линия</label>

				<div class="select material-select" data-task-operation-select>
					<input class="select__value" type="hidden" name="operation_id" value="{{ $selectedOperationId ?? '' }}">

					<button class="material-select__select-button select__button select-button" id="task-operation"
							type="button" aria-haspopup="listbox" aria-expanded="false">
						<span class="material-select__select-value select__button-text">
							{{ $operations->firstWhere('id', (int) $selectedOperationId)?->name ?? '— Не выбрана —' }}
						</span>
						<span class="material-select__select-arrow" aria-hidden="true"></span>
					</button>

					<div class="select__dropdown material-select__select-list _collapse" role="listbox">
						<button class="material-select__select-option select__item" type="button" role="option"
								data-value="">— Не выбрана —</button>

						@foreach ($operations as $operation)
							<button class="material-select__select-option select__item" type="button" role="option"
									data-value="{{ $operation->id }}" @if ($operation->is_cutting) data-is-cutting="1" @endif>{{ $operation->name }}</button>
						@endforeach
					</div>
				</div>
			</fieldset>

			<fieldset class="production-task__field">
				<label class="production-task__label" for="task-template">Шаблон производства</label>

				<div class="select material-select" data-task-template-select>
					<input class="select__value" id="task-template" name="production_line_id" type="hidden"
							value="{{ $selectedLineId ?? '' }}">

					<button class="material-select__select-button select__button select-button" type="button"
							aria-haspopup="listbox" aria-expanded="false">
						<span class="material-select__select-value select__button-text">
							{{ $selectedTemplateName ?? '— Не выбран —' }}
						</span>
						<span class="material-select__select-arrow" aria-hidden="true"></span>
					</button>

					<div class="select__dropdown material-select__select-list _collapse" role="listbox">
						<button class="material-select__select-option select__item" type="button" role="option"
								data-value="">— Не выбран —</button>

						@foreach ($operations as $operation)
							@foreach ($operation->productionLines as $line)
								<button class="material-select__select-option select__item" type="button" role="option"
										data-value="{{ $line->id }}" data-operation-id="{{ $operation->id }}"
										data-search="{{ strtolower($line->name) }}">
									{{ $line->name }}
								</button>
							@endforeach
						@endforeach
					</div>
				</div>
			</fieldset>

			<fieldset class="production-task__field">
				<label class="production-task__label" for="task-operator">Оператор</label>

				<div class="select material-select">
					<input class="select__value" id="task-operator" name="operator_id" type="hidden"
							value="{{ $selectedOperatorId ?? '' }}">

					<button class="material-select__select-button select__button select-button" type="button"
							aria-haspopup="listbox" aria-expanded="false">
						<span class="material-select__select-value select__button-text">
							{{ $operators->firstWhere('id', (int) $selectedOperatorId)?->name ?? '— Не назначен —' }}
						</span>
						<span class="material-select__select-arrow" aria-hidden="true"></span>
					</button>

					<div class="select__dropdown material-select__select-list _collapse" role="listbox">
						<button class="material-select__select-option select__item" type="button" role="option"
								data-value="">— Не назначен —</button>

						@foreach ($operators as $operator)
							<button class="material-select__select-option select__item" type="button" role="option"
									data-value="{{ $operator->id }}">{{ $operator->name }}</button>
						@endforeach
					</div>
				</div>
			</fieldset>

			<fieldset class="production-task__field">
				<label class="production-task__label" for="quantity"><span data-task-quantity-label>Кол-во вых. материала, кг</span></label>

				<input class="production-task__input" id="quantity" name="quantity" type="number"
						step="0.001" min="0.001"
						value="{{ old('quantity', $task ? rtrim(rtrim((string) $task->quantity, '0'), '.') : null) }}" required>
			</fieldset>
		</div>
	@else
		{{-- У начатой задачи состав материалов зафиксирован --}}
		<div class="production-task__line">
			<fieldset class="production-task__field">
				<label class="production-task__label">Материал (продукция)</label>
				<input class="production-task__input" type="text" disabled value="{{ $task->material?->name }}">
			</fieldset>

			<fieldset class="production-task__field">
				<label class="production-task__label" for="task-operator">Оператор</label>

				<div class="select material-select">
					<input class="select__value" id="task-operator" name="operator_id" type="hidden"
							value="{{ $selectedOperatorId ?? '' }}">

					<button class="material-select__select-button select__button select-button" type="button"
							aria-haspopup="listbox" aria-expanded="false">
						<span class="material-select__select-value select__button-text">
							{{ $operators->firstWhere('id', (int) $selectedOperatorId)?->name ?? '— Не назначен —' }}
						</span>
						<span class="material-select__select-arrow" aria-hidden="true"></span>
					</button>

					<div class="select__dropdown material-select__select-list _collapse" role="listbox">
						<button class="material-select__select-option select__item" type="button" role="option"
								data-value="">— Не назначен —</button>

						@foreach ($operators as $operator)
							<button class="material-select__select-option select__item" type="button" role="option"
									data-value="{{ $operator->id }}">{{ $operator->name }}</button>
						@endforeach
					</div>
				</div>
			</fieldset>

			<fieldset class="production-task__field">
				<label class="production-task__label" for="quantity">{{ $task->isCutting() ? 'Резать, кг (вход)' : 'Количество, кг' }}</label>

				<input class="production-task__input" id="quantity" name="quantity" type="number"
						step="0.001" min="0.001"
						value="{{ old('quantity', rtrim(rtrim((string) $task->quantity, '0'), '.')) }}" required>
			</fieldset>
		</div>
	@endif

	{{-- Комментарий --}}
	<div class="production-task__line">
		<fieldset class="production-task__field">
			<label class="production-task__label" for="comment"> Комментарий </label>

			<textarea class="production-task__input production-task__textarea" id="comment"
					name="comment">{{ old('comment', $task?->comment) }}</textarea>
		</fieldset>
	</div>

	@if ($isPending)
		{{-- Разблокировка состава материалов задачи --}}
		<div class="production-task__actions">
			<button class="production-task__button button" type="button" data-task-unlock-materials>
				<span>Изменить материалы вручную</span>
			</button>
		</div>

		{{-- Материалы задачи --}}
		<div class="operation-form" data-task-materials-sections @if ($sectionsHidden) hidden @endif>
			<div data-task-material-table="input">
				@include('production.operations._line-materials-table', [
						'title' => 'Материалы (вход)',
						'description' => 'Заполняются из шаблона производства. Кнопка «Изменить материалы вручную» позволяет задать другой состав — изменение действует только для этой задачи.',
						'inputName' => 'materials',
						'materials' => $allMaterials,
						'rows' => $inputRows,
						'emptyText' => 'Нет доступных материалов.',
				])
			</div>

			<div data-task-material-table="output">
				@include('production.operations._line-materials-table', [
						'title' => 'Материалы (выход)',
						'description' => 'Выходной материал задачи. Для линии в режиме резки — тот же материал в других форматах.',
						'inputName' => 'output_materials',
						'materials' => $products,
						'rows' => $outputRows,
						'allowAdd' => true,
						'allowAddFormat' => true,
						'buttonsHidden' => true,
				])
			</div>
		</div>

		<script type="application/json" data-task-form-data>@json(['templates' => $templates, 'allowed' => $allowedMaterials])</script>
	@endif

	{{-- Действия --}}
	<div class="production-task__actions">
		<button class="production-task__button main-content__button button" type="submit">
			<span>{{ $submitLabel }}</span>
		</button>

		<a class="production-task__button production-task__button--reset button" href="{{ $cancelUrl }}">
			<span>Отмена</span>
		</a>
	</div>
</div>
