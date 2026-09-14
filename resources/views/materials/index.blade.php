@extends('layouts.app')

@section('title', 'Материалы')

@section('content')
	<div class="main-content__content materials"
			data-materials-page
			data-hierarchy="{{ $hierarchyEnabled ? '1' : '0' }}"
			data-current-catalog="{{ $currentCatalog?->id }}">
		<div class="main-content__header">
			<h1 class="main-content__title">Материалы</h1>

			<div class="catalogs__header-actions">
				@if ($hierarchyEnabled)
					<a class="button button--secondary" href="{{ route('catalogs.index') }}">
						<span>Каталоги</span>
					</a>
				@endif

				<button class="button button--primary materials__create" type="button" data-material-create>
					<i class="icon icon-plus" aria-hidden="true"></i>
					<span>Создать</span>
				</button>
			</div>
		</div>

		@if ($hierarchyEnabled && $breadcrumbs->isNotEmpty())
			<nav class="materials__breadcrumbs">
				<a href="{{ route('materials.index') }}">Материалы</a>
				@foreach ($breadcrumbs as $breadcrumb)
					<span> / </span>
					@if ($loop->last)
						<span class="materials__breadcrumbs-current">{{ $breadcrumb->name }}</span>
					@else
						<a href="{{ route('materials.index', ['catalog' => $breadcrumb->id]) }}">{{ $breadcrumb->name }}</a>
					@endif
				@endforeach
			</nav>
		@endif

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>Материал</th>
						<th>Тип</th>
						<th>Код</th>
						<th>Идентификатор</th>
						<th>Грамматура</th>
						<th>Толщина</th>
						<th>Формат</th>
						<th>Статус</th>

						<th class="materials__table-edit">
							<i class="icon-settings-cogs icon"></i>
						</th>
					</tr>
					</thead>

					<tbody class="materials__body" data-materials-body>
					@if ($hierarchyEnabled && $catalogs->isEmpty() && $materials->isEmpty())
						<tr class="materials__empty">
							<td colspan="10">Каталоги и материалы не добавлены.</td>
						</tr>
					@elseif (!$hierarchyEnabled && $materials->isEmpty())
						<tr class="materials__empty" data-materials-empty>
							<td colspan="10">Материалы не добавлены.</td>
						</tr>
					@else
						@foreach ($catalogs as $catalog)
							<tr class="materials__catalog-row">
								<td>{{ $loop->iteration }}</td>
								<td colspan="8">
									<a class="materials__catalog-link" href="{{ route('materials.index', ['catalog' => $catalog->id]) }}">
										{{ $catalog->name }}
									</a>
								</td>
								<td class="materials__actions-icons">
									<button type="button" data-action="catalog-view" data-catalog-id="{{ $catalog->id }}"
											aria-label="Просмотр каталога" title="Просмотр каталога">
										<i class="icon icon-eye" aria-hidden="true"></i>
									</button>
								</td>
							</tr>
						@endforeach

						@foreach ($materials as $material)
							<tr data-material-id="{{ $material->id }}">
								<td>{{ $loop->iteration }}</td>
								<td>{{ $material->name }}</td>
								<td>{{ \App\Models\Material::TYPES[$material->material_type] ?? '—' }}</td>
								<td>{{ $material->code }}</td>
								<td>{{ $material->identifier }}</td>
								<td>{{ $material->grammage }}</td>
								<td>{{ $material->thickness }}</td>
								<td>{{ $material->format }}</td>

								<td>{{ $material->is_active ? 'Активен' : 'Неактивен' }}</td>

								<td class="materials__actions-icons">
									<button type="button" data-action="view" aria-label="Просмотр" title="Просмотр">
										<i class="icon icon-eye" aria-hidden="true"></i>
									</button>

									<button type="button" data-action="edit" aria-label="Редактировать" title="Редактировать">
										<i class="icon icon-edit" aria-hidden="true"></i>
									</button>

									<button type="button" data-action="delete" aria-label="Удалить" title="Удалить">
										<i class="icon icon-trash" aria-hidden="true"></i>
									</button>
								</td>
							</tr>
						@endforeach
					@endif
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
