@extends('layouts.app')

@section('title', 'Каталоги')

@section('content')
	<div class="main-content__content materials catalogs" data-catalogs-page>
		<div class="main-content__header">
			<h1 class="main-content__title">Каталоги</h1>

			<div class="catalogs__header-actions">
				<label class="catalogs__hierarchy-toggle">
					<input type="checkbox" name="hierarchy_enabled" {{ $hierarchyEnabled ? 'checked' : '' }}>
					<span>Иерархия каталогов</span>
				</label>

				<button class="button button--primary materials__create" type="button" data-catalog-create>
					<i class="icon icon-plus" aria-hidden="true"></i>
					<span>Создать</span>
				</button>
			</div>
		</div>

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>Каталог</th>
						<th>Материалов</th>

						<th class="materials__table-edit">
							<i class="icon-settings-cogs icon"></i>
						</th>
					</tr>
					</thead>

					<tbody class="materials__body" data-catalogs-body>
					@if ($catalogs->isEmpty())
						<tr class="materials__empty">
							<td colspan="4">Каталоги не добавлены.</td>
						</tr>
					@else
						@php
						$rendered = collect();

						$appendBranch = function ($parentId, $depth) use (&$appendBranch, &$rows, &$rendered, $catalogs) {
								foreach ($catalogs->where('parent_id', $parentId)->sortBy([['sort_order', 'asc'], ['name', 'asc']]) as $catalog) {
									if ($rendered->contains($catalog->id)) {
										continue;
									}

									$rendered->push($catalog->id);
									$rows->push(['catalog' => $catalog, 'depth' => $depth]);
									$appendBranch($catalog->id, $depth + 1);
								}
							};

							$rows = collect();
							$appendBranch(null, 0);

							// Каталоги, потерявшие родителя, показываем в корне.
							foreach ($catalogs as $catalog) {
								if (!$rendered->contains($catalog->id)) {
									$rendered->push($catalog->id);
									$rows->push(['catalog' => $catalog, 'depth' => 0]);
									$appendBranch($catalog->id, 1);
								}
							}
						@endphp

						@foreach ($rows as $row)
							@php $catalog = $row['catalog']; $depth = $row['depth']; @endphp
							<tr data-catalog-id="{{ $catalog->id }}">
								<td>{{ $loop->iteration }}</td>
								<td class="catalogs__name-cell">
									<a class="catalogs__name catalogs__name-link" style="--catalog-depth: {{ $depth }}"
											href="{{ route('materials.index', ['catalog' => $catalog->id]) }}">
										{{ $catalog->name }}
									</a>
								</td>
								<td>{{ $catalog->materials_count ?? $catalog->materials()->count() }}</td>

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
