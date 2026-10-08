<div class="operation-confirm">
	<div class="operation-confirm__header">
		<h2 class="operation-confirm__title">Удаление материала</h2>
	</div>
	<div class="operation-confirm__text">
		Вы действительно хотите удалить материал «{{ $material->name }}»?
	</div>

	@if (($rollsCount ?? 0) > 0)
		<div class="operation-confirm__text">
			Рулоны материала без остатков ({{ $rollsCount }}) будут удалены в корзину вместе с материалом.
		</div>
	@endif

	<div class="operation-confirm__actions">
		<button type="button" class="button button--primary" data-material-delete-confirm
				data-material-id="{{ $material->id }}">
			<span>Удалить</span>
		</button>
	</div>
</div>
