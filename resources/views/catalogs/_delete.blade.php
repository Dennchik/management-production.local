<div class="operation-confirm">
	<div class="operation-confirm__header">
		<h2 class="operation-confirm__title">Удаление каталога</h2>
	</div>
	<div class="operation-confirm__text">
		Вы действительно хотите удалить каталог «{{ $catalog->name }}»?
	</div>

	<div class="operation-confirm__actions">
		<button type="button" class="button button--primary" data-catalog-delete-confirm
				data-catalog-id="{{ $catalog->id }}">
			<span>Удалить</span>
		</button>
	</div>
</div>
