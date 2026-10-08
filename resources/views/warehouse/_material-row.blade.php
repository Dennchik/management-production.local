@php
/*
 * Строка материала в таблице склада: тире вместо маркера,
 * отступ по глубине вложенности.
 *
 * Переменные: $material, $depth, $canMaterials, $canMaterialsCreate,
 * $canMaterialsEdit, $canMaterialsDelete.
 */
@endphp

<div class="table__row-line" style="--tree-depth: {{ $depth }}"
		data-material-id="{{ $material->id }}"
		data-row-link="{{ route('warehouse.material', $material) }}"
		tabindex="0" role="link">

	<div class="table__cell table__cell--stack">
		— {{ $material->name }}
	</div>
	<div class="table__cell"> {{ $material->identifier ?? '—' }} </div>
	<div class="table__cell"> {{ $material->format ?? '—' }} </div>
	<div class="table__cell"> {{ $material->rolls->count() }} </div>
	<div class="table__cell"> {{ number_format($material->rolls->sum('weight'), 3, '.', '') }} </div>

	@if ($canMaterials)
		<div class="table__cell materials__actions-icons">
			<button type="button" data-action="view" aria-label="Просмотр" title="Просмотр">
				<i class="icon icon-eye" aria-hidden="true"></i>
			</button>

			@if ($canMaterialsEdit)
				<button type="button" data-action="edit" aria-label="Редактировать" title="Редактировать">
					<i class="icon icon-edit" aria-hidden="true"></i>
				</button>
			@endif

			@if ($canMaterialsCreate)
				<button type="button" data-action="copy" aria-label="Копировать" title="Копировать">
					<i class="icon icon-copy-files" aria-hidden="true"></i>
				</button>
			@endif

			@if ($canMaterialsDelete)
				<button type="button" data-action="delete" aria-label="Удалить" title="Удалить">
					<i class="icon icon-trash" aria-hidden="true"></i>
				</button>
			@endif
		</div>
	@endif
</div>
