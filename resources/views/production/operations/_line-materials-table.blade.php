@php
	// Строки материалов: null — пустая строка для выбора.
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
				<div class="table__row-line" data-production-line-material>
					<div class="table__cell" data-line-index>{{ $loop->iteration }}</div>
					<div class="table__cell">
						<div data-select>
							<div class="select material-select operation-form">
								<input class="select__value" type="hidden" name="{{ $inputName }}[]" value="{{ $row->id ?? '' }}">
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
										<button class="material-select__select-option select__item" type="button" role="option"
												data-value="{{ $material->id }}"
												aria-selected="{{ ($row?->id ?? null) === $material->id ? 'true' : 'false' }}"
												data-identifier="{{ $material->identifier }}"
												data-format="{{ $material->format }}">
											<span>
												{{ $material->name }}
												@if ($material->grammage) | {{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }} гр @endif
												@if ($material->thickness) | {{ $material->thickness }} мкм @endif
												@if ($material->format) | {{ $material->format }} @endif
											</span>
										</button>
									@endforeach
									<div class="material-select__select-empty select__empty" hidden>
										Ничего не найдено
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="table__cell" data-cell-identifier>{{ $row->identifier ?? '' }}</div>
					<div class="table__cell" data-cell-format>{{ $row->format ?? '' }}</div>
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
