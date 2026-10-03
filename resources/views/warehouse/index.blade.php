@extends('layouts.app')

@section('title', 'Материалы')

@section('content')

	@php
		$user = auth()->user();

		/*
		 * Управление материалами и каталогами доступно по праву «Материалы»;
		 * без него страница остаётся складским просмотром остатков.
		 */
		$canMaterials = $user?->may('materials');
		$canMaterialsCreate = $user?->may('materials', 'create');
		$canMaterialsEdit = $user?->may('materials', 'edit');
		$canMaterialsDelete = $user?->may('materials', 'delete');
	@endphp

	<div class="main-content__content materials"
			data-materials-page
			data-hierarchy="1"
			data-current-catalog="{{ $currentCatalog?->id }}">

		{{-- Фильтр --}}
		@include('layouts.filters-actions')

		<div class="main-content__header">
			<h1 class="main-content__title">Материалы</h1>

			<div class="catalogs__header-actions">
				@if ($catalogTree->isNotEmpty())
					<button class="button button--secondary" type="button" data-catalogs-expand>
						<span>Раскрыть всё</span>
					</button>

					<button class="button button--secondary" type="button" data-catalogs-collapse>
						<span>Скрыть всё</span>
					</button>
				@endif

				@if ($canMaterialsCreate)
					<button class="button button--primary materials__create" type="button" data-material-create>
						<i class="icon icon-plus" aria-hidden="true"></i>
						<span>Создать</span>
					</button>
				@endif
			</div>
		</div>

		{{-- Таблица склада на компоненте .table: каталоги раскрываются строками --}}
		<div class="table table--warehouse">
			<div class="table__row-line table__row--header">
				<div class="table__cell">Каталог / Материал</div>
				<div class="table__cell">Идентификатор</div>
				<div class="table__cell">Формат</div>
				<div class="table__cell">Рулонов</div>
				<div class="table__cell">Остаток, кг</div>

				@if ($canMaterials)
					<div class="table__cell materials__table-edit">
						<i class="icon-settings-cogs icon"></i>
					</div>
				@endif
			</div>

			<div class="table__body" data-materials-body>
				@if ($flatList)
					{{-- Фильтры пробивают иерархию: материалы всех каталогов подряд --}}
					@foreach ($materials as $material)
						@include('warehouse._material-row', ['material' => $material, 'depth' => 0])
					@endforeach

					@if ($materials->isEmpty())
						<div class="table__row-line">
							<div class="table__cell" style="grid-column: 1 / -1;">Склад пуст</div>
						</div>
					@endif
				@else
					{{-- Материалы без каталога — в корне таблицы --}}
					@foreach ($rootMaterials as $material)
						@include('warehouse._material-row', ['material' => $material, 'depth' => 0])
					@endforeach

					@foreach ($catalogTree as $node)
						@include('warehouse._catalog-branch', [
								'node' => $node,
								'depth' => 0,
								'ancestors' => '',
						])
					@endforeach

					@if ($catalogTree->isEmpty() && $rootMaterials->isEmpty())
						<div class="table__row-line">
							<div class="table__cell" style="grid-column: 1 / -1;">Каталоги и материалы не добавлены.</div>
						</div>
					@endif
				@endif
			</div>
		</div>
	</div>

@endsection
