@php
	/*
	 * Ветка каталога в таблице склада: строка-заголовок раскрывает
	 * вложенный блок с материалами и подкаталогами.
	 *
	 * Переменные: $node (id, name, materials, children), $depth,
	 * $ancestors, $activePath, $canMaterials, $canMaterialsEdit,
	 * $canMaterialsDelete.
	 */
	$inActivePath = $activePath->contains($node['id'])
			|| $node['children']->contains(static fn ($child) => $activePath->contains($child['id']));
@endphp

<div class="table__row-line table__row-line--catalog" style="--tree-depth: {{ $depth }}"
		data-catalog-toggle
		data-catalog-id="{{ $node['id'] }}"
		@if ($inActivePath) aria-expanded="true" @else aria-expanded="false" @endif
		tabindex="0" role="button">

	<div class="table__cell">
		<span class="material__catalog-arrow" aria-hidden="true"></span>
		{{ $node['name'] }}
	</div>
	<div class="table__cell"></div>
	<div class="table__cell"></div>
	<div class="table__cell"></div>
	<div class="table__cell"></div>

	@if ($canMaterials)
		<div class="table__cell materials__actions-icons">
			<button type="button" data-action="catalog-view" data-catalog-id="{{ $node['id'] }}"
					aria-label="Просмотр каталога" title="Просмотр каталога"
					data-catalog-row-button>
				<i class="icon icon-eye" aria-hidden="true"></i>
			</button>

			@if ($canMaterialsEdit)
				<button type="button" data-action="catalog-edit" data-catalog-id="{{ $node['id'] }}"
						aria-label="Редактировать каталог" title="Редактировать каталог"
						data-catalog-row-button>
					<i class="icon icon-edit" aria-hidden="true"></i>
				</button>
			@endif

			@if ($canMaterialsDelete)
				<button type="button" data-action="catalog-delete" data-catalog-id="{{ $node['id'] }}"
						aria-label="Удалить каталог" title="Удалить каталог"
						data-catalog-row-button>
					<i class="icon icon-trash" aria-hidden="true"></i>
				</button>
			@endif
		</div>
	@endif
</div>

{{-- Материалы каталога и вложенные ветки: прячутся вместе с веткой --}}
<div class="table__branch" data-catalog-branch="{{ $node['id'] }}" @if (! $inActivePath) hidden @endif>
	@foreach ($node['materials'] as $material)
		@include('warehouse._material-row', ['material' => $material, 'depth' => $depth + 1])
	@endforeach

	@foreach ($node['children'] as $child)
		@include('warehouse._catalog-branch', [
				'node' => $child,
				'depth' => $depth + 1,
				'ancestors' => '',
		])
	@endforeach
</div>
