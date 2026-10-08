<div class="material-show">
	<div class="material-show__header">
		<h2 class="material-show__title">Материал</h2>
	</div>

	<div class="material-show__content">
		<div class="material-show__row">
			<span>Наименование:</span>
			<div class="material-show__info">{{ $material->name }}</div>
		</div>

		<div class="material-show__row">
			<span>Код:</span>
			<div class="material-show__info">{{ $material->code }}</div>
		</div>

		<div class="material-show__row">
			<span>Идентификатор:</span>
			<div class="material-show__info">{{ $material->identifier ?? '—' }}</div>
		</div>

		<div class="material-show__row">
			<span>Грамматура:</span>
			<div class="material-show__info">{{ $material->grammage ?? '—' }}</div>
		</div>

		<div class="material-show__row">
			<span>Толщина:</span>
			<div class="material-show__info">{{ $material->thickness ?? '—' }}</div>
		</div>

		<div class="material-show__row">
			<span>Формат:</span>
			<div class="material-show__info">{{ $material->format ?? '—' }}</div>
		</div>

		<div class="material-show__row">
			<span>Назначение материала:</span>
			<div>
				{{ $material->allowedOperations->pluck('name')->implode(', ') ?: '—' }}
			</div class="material-show__info">
		</div>

		<div class="material-show__row">
			<span>Статус:</span>
			<div class="material-show__info">{{ $material->is_active ? 'Активен' : 'Неактивен' }}</div>
		</div>
	</div>
</div>