<div class="material-show">
	<div class="material-show__header">
		<h2 class="material-show__title">Каталог</h2>
	</div>

	<div class="material-show__content">
		<div class="material-show__row">
			<span>Наименование:</span>
			<span>{{ $catalog->name }}</span>
		</div>

		<div class="material-show__row">
			<span>Полный путь:</span>
			<span>{{ $path }}</span>
		</div>

		<div class="material-show__row">
			<span>Материалов:</span>
			<span>{{ $catalog->materials()->count() }}</span>
		</div>

		<div class="material-show__row">
			<span>Сортировка:</span>
			<span>{{ $catalog->sort_order }}</span>
		</div>

		<div class="material-show__row">
			<span>Статус:</span>
			<span>{{ $catalog->is_active ? 'Активен' : 'Неактивен' }}</span>
		</div>
	</div>
</div>
