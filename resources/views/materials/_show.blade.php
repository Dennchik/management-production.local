<div class="material-show">
	<div class="material-show__header">
		<h2 class="material-show__title">Материал</h2>
	</div>

	<div class="material-show__content">
		<div class="material-show__row">
			<span>Наименование:</span>
			<strong>{{ $material->name }}</strong>
		</div>

		<div class="material-show__row">
			<span>Код:</span>
			<strong>{{ $material->code }}</strong>
		</div>

		<div class="material-show__row">
			<span>Идентификатор:</span>
			<strong>{{ $material->identifier }}</strong>
		</div>

		<div class="material-show__row">
			<span>Грамматура:</span>
			<strong>{{ $material->grammage ?? '—' }}</strong>
		</div>

		<div class="material-show__row">
			<span>Толщина:</span>
			<strong>{{ $material->thickness ?? '—' }}</strong>
		</div>

		<div class="material-show__row">
			<span>Формат:</span>
			<strong>{{ $material->format ?? '—' }}</strong>
		</div>

		<div class="material-show__row">
			<span>Статус:</span>
			<strong>{{ $material->is_active ? 'Активен' : 'Неактивен' }}</strong>
		</div>
	</div>
</div>