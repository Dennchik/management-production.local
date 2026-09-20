@php
	// Строки материалов: null — пустая строка для выбора.
	// Материал всегда выбирается с конкретным форматом:
	// скрытые поля materials[] и materials_formats[] идут парами по индексу строки.
	if (!isset($rows)) {
		$rows = collect([null]);
	} elseif ($rows->isEmpty()) {
		$rows = collect([null]);
	}
@endphp

<div class="operation-form__section">
	<div class="operation-form__section-header">
		<div>
			<h2 class="operation-form__section-title">{{ $title }}</h2>
			<p class="operation-form__section-description">{{ $description }}</p>
		</div>

		@if (($allowAdd ?? true))
			<button class="button button--secondary" type="button" data-production-line-add-material>
				Добавить материал
			</button>
		@endif
	</div>

	<div class="table">
		<div class="table__row-line table__row--header">
			<div class="table__cell">№</div>
			<div class="table__cell">Материал</div>
			<div class="table__cell">Идентификатор</div>
			<div class="table__cell">Формат</div>
			<div class="table__cell materials__table-edit">
				<i class="icon-settings-cogs icon"></i>
			</div>
		</div>

		<div class="table__body" data-production-line-materials>
			@foreach ($rows as $row)
				@php
					$rowFormat = $row?->pivot->format ?? null;
					$rowIdentifier = $row ? $row->identifierForFormat($rowFormat) : null;
				@endphp

				<div class="table__row-line" data-production-line-material>
					<div class="table__cell" data-line-index>{{ $loop->iteration }}</div>
					<div class="table__cell">
						<div data-select>
							<div class="select material-select operation-form">
								<input class="select__value" type="hidden" name="{{ $inputName }}[]" value="{{ $row->id ?? '' }}">
								<input class="select__format-value" type="hidden" name="{{ $inputName }}_formats[]" value="{{ $rowFormat ?? '' }}">

								<button class="material-select__select-button select__button select-button"
										type="button" aria-haspopup="listbox" aria-expanded="false">
									<span class="material-select__select-value select__button-text">{{ $row->name ?? 'Выберите материал' }}</span>
									<span class="material-select__select-arrow" aria-hidden="true"></span>
								</button>

								<div class="select__dropdown material-select__select-list _collapse" role="listbox">
									<div class="material-select__select-search">
										<input class="material-select__select-search-input select__search"
												type="search" placeholder="Поиск материала..." autocomplete="off">
										<button class="material-select__select-search-clear select__search-clear"
												type="button" aria-label="Очистить поиск" hidden>
											<i class="icon icon-close" aria-hidden="true"></i>
										</button>
									</div>

									@foreach ($materials as $material)

										@php
											/*
											 * Отдельный пункт на каждый формат;
											 * без рулонов — один пункт без формата.
											 */
											$formatGroups = $material->rolls
												->groupBy(fn ($roll) => $roll->format ?? '')
												->sortBy(fn ($group, $format) => (int) $format);
										@endphp

										@foreach ($formatGroups->isEmpty() ? collect([null]) : $formatGroups as $group)
											@php
												$groupFormat = $group?->pluck('format')->filter()->unique()->first();
												$groupIdentifier = $group?->pluck('identifier')->filter()->unique()->sort(SORT_STRING)->first();
												$groupFormatsLabel = $group?->pluck('format')->filter()->unique()->values()->implode(', ');
												$isSelected = ($row?->id ?? null) === $material->id
													&& (string) ($rowFormat ?? '') === (string) ($groupFormat ?? '');
											@endphp

											<button class="material-select__select-option select__item" type="button" role="option"
													data-value="{{ $material->id }}"
													aria-selected="{{ $isSelected ? 'true' : 'false' }}"
													data-name="{{ $material->name }}"
													data-identifier="{{ $groupIdentifier ?? '' }}"
													data-format="{{ $groupFormat ?? '' }}">
												<span>
													{{ $material->name }}
													@if ($material->grammage) | {{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }} гр @endif
													@if ($material->thickness) | {{ $material->thickness }} мкм @endif
													@if ($groupFormatsLabel) | {{ $groupFormatsLabel }} @endif
												</span>
											</button>

										@endforeach

									@endforeach

									<div class="material-select__select-empty select__empty" hidden>
										Ничего не найдено
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="table__cell" data-cell-identifier>{{ $rowIdentifier ?? '' }}</div>
					<div class="table__cell" data-cell-format>{{ $rowFormat ?? '' }}</div>
					<div class="table__cell production-line-materials__actions">
						<button type="button" class="production-line-materials__remove"
								data-production-line-remove-material
								aria-label="Удалить материал" title="Удалить материал">
							<i class="icon icon-trash" aria-hidden="true"></i>
						</button>
					</div>
				</div>
			@endforeach
		</div>
	</div>

	@if ($materials->isEmpty())
		<p class="operation-form__section-description">{{ $emptyText }}</p>
	@endif
</div>
