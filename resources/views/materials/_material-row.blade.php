@php
	/*
	 * Отдельная строка на каждый формат;
	 * без рулонов — одна строка «—».
	 */
	$formatGroups = $material->rolls
		->groupBy(fn ($roll) => $roll->format ?? '')
		->sortBy(fn ($group, $format) => (int) $format);
@endphp

@foreach ($formatGroups->isEmpty() ? collect([null]) : $formatGroups as $group)
	@php
		$groupRolls = $group ?? collect();
		$groupFormats = $groupRolls->pluck('format')->filter()->unique()->values();
		$groupIdentifiers = $groupRolls->pluck('identifier')->filter()->unique()->sort(SORT_STRING)->values();
	@endphp

	<tr data-material-id="{{ $material->id }}">
		<td>{{ $number }}</td>
		<td>{{ $material->name }}</td>
		<td>{{ $material->code }}</td>
		<td>{{ $groupIdentifiers->implode(', ') ?: '—' }}</td>
		<td>{{ $material->grammage }}</td>
		<td>{{ $material->thickness }}</td>
		<td>{{ $groupFormats->implode(', ') ?: '—' }}</td>
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
