@extends('layouts.app')

@section('title', 'Материалы')

@section('content')
	<div class="main-content__content materials" data-materials-page>
		<div class="main-content__header">
			<h1 class="main-content__title">Материалы</h1>

			<button class="button button--primary materials__create" type="button" data-material-create>
				<i class="icon icon-plus" aria-hidden="true"></i>
				<span>Создать</span>
			</button>
		</div>

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>Материал</th>
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
					@if ($materials->isEmpty())
						<tr class="materials__empty" data-materials-empty>
							<td colspan="9">Материалы не добавлены.</td>
						</tr>
					@else
						@foreach ($materials as $material)
							<tr data-material-id="{{ $material->id }}">
								<td>{{ $loop->iteration }}</td>
								<td>{{ $material->name }}</td>
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