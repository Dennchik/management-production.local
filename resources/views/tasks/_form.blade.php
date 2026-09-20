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
	 * Строки таблиц материалов: после ошибки валидации — из old()
	 * (материал + формат парами), иначе материалы задачи.
	 */
	$rowsFromOld = function (array $ids, array $formats, $source) {
		$ids = array_values(array_filter($ids, static fn ($value) => $value !== null && $value !== ''));
		$formats = array_values($formats ?? []);

		return collect($ids)->map(static function ($id, $index) use ($source, $formats) {
			$material = $source->first(static fn ($item) => (int) $item->id === (int) $id);

			if ($material === null) {
				return null;
			}

			$format = $formats[$index] ?? null;

			$clone = clone $material;
			$clone->setRelation('pivot', new \Illuminate\Database\Eloquent\Relations\Pivot([
				'direction' => 'input',
				'format' => $format !== null && $format !== '' ? (int) $format : null,
			]));

			return $clone;
		})->filter();
	};

	$oldInputs = old('materials');
	$oldOutputs = old('output_materials');

	$inputRows = $oldInputs !== null
		? $rowsFromOld($oldInputs, old('materials_formats', []), $allMaterials)
		: ($task?->inputMaterials ?? collect());

	$outputRows = $oldOutputs !== null
		? $rowsFromOld($oldOutputs, old('output_materials_formats', []), $products)
		: ($task?->outputMaterials ?? collect());

	$sectionsHidden = $inputRows->isEmpty() && $outputRows->isEmpty();
@endphp

<div class="issue-order__body">

	@if ($isPending)
		{{-- Номер / Линия / Шаблон / Оператор / Количество --}}
		<div class="issue-order__line">
			<fieldset class="issue-order__field">
				<label class="issue-order__label" for="task-number">Номер</label>

				<input class="issue-order__input" id="task-number" name="number" type="number"
						min="1" step="1" value="{{ old('number', $task->number ?? $nextNumber ?? '') }}" required>
			</fieldset>

			<fieldset class="issue-order__field">
				<label class="issue-order__label" for="task-operation">Линия</label>

				<div class="select material-select" data-task-operation-select>
					<input class="select__value" type="hidden" value="{{ $selectedOperationId ?? '' }}">

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
									data-value="{{ $operation->id }}">{{ $operation->name }}</button>
						@endforeach
					</div>
				</div>
			</fieldset>

			<fieldset class="issue-order__field">
				<label class="issue-order__label" for="task-template">Шаблон производства</label>

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

			<fieldset class="issue-order__field">
				<label class="issue-order__label" for="task-operator">Оператор</label>

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

			<fieldset class="issue-order__field">
				<label class="issue-order__label" for="quantity">Количество выходного материала, кг</label>

				<input class="issue-order__input" id="quantity" name="quantity" type="number"
						step="0.001" min="0.001"
						value="{{ old('quantity', $task ? rtrim(rtrim((string) $task->quantity, '0'), '.') : null) }}" required>
			</fieldset>
		</div>
	@else
		{{-- У начатой задачи состав материалов зафиксирован --}}
		<div class="issue-order__line">
			<fieldset class="issue-order__field">
				<label class="issue-order__label">Материал (продукция)</label>
				<input class="issue-order__input" type="text" disabled value="{{ $task->material?->name }}">
			</fieldset>

			<fieldset class="issue-order__field">
				<label class="issue-order__label" for="task-operator">Оператор</label>

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

			<fieldset class="issue-order__field">
				<label class="issue-order__label" for="quantity">Количество, кг</label>

				<input class="issue-order__input" id="quantity" name="quantity" type="number"
						step="0.001" min="0.001"
						value="{{ old('quantity', rtrim(rtrim((string) $task->quantity, '0'), '.')) }}" required>
			</fieldset>
		</div>
	@endif

	{{-- Комментарий --}}
	<div class="issue-order__line">
		<fieldset class="issue-order__field">
			<label class="issue-order__label" for="comment"> Комментарий </label>

			<textarea class="issue-order__input issue-order__textarea" id="comment"
					name="comment">{{ old('comment', $task?->comment) }}</textarea>
		</fieldset>
	</div>

	@if ($isPending)
		{{-- Разблокировка состава материалов задачи --}}
		<div class="issue-order__actions">
			<button class="issue-order__button button" type="button" data-task-unlock-materials>
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
						'description' => 'Выходной материал задачи.',
						'inputName' => 'output_materials',
						'materials' => $products,
						'rows' => $outputRows,
						'allowAdd' => false,
				])
			</div>
		</div>

		<script type="application/json" data-task-form-data>@json(['templates' => $templates, 'allowed' => $allowedMaterials])</script>
	@endif

	{{-- Действия --}}
	<div class="issue-order__actions">
		<button class="issue-order__button main-content__button button" type="submit">
			<span>{{ $submitLabel }}</span>
		</button>

		<a class="issue-order__button issue-order__button--reset button" href="{{ $cancelUrl }}">
			<span>Отмена</span>
		</a>
	</div>
</div>
