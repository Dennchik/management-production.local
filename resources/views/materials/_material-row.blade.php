<tr data-material-id="{{ $material->id }}">
	<td>{{ $number }}</td>
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