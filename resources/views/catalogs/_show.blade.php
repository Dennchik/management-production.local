<div class="material-show">
	<div class="material-show__header">
		<h2 class="material-show__title">Каталог</h2>
	</div>

	<div class="material-show__content">
		<div class="material-show__row">
			<span>Наименование:</span>
			<div class="material-show__info">{{ $catalog->name }}</div>
		</div>

		<div class="material-show__row">
			<span>Полный путь:</span>
			<div class="material-show__info">{{ $path }}</div>
		</div>

		<div class="material-show__row">
			<span>Материалов:</span>
			<div class="material-show__info">{{ $catalog->materials()->count() }}</div>
		</div>

		<div class="material-show__row">
			<span>Статус:</span>
			<div class="material-show__info">{{ $catalog->is_active ? 'Активен' : 'Неактивен' }}</div>
		</div>
	</div>
</div>
