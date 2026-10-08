<div class="operation-confirm" data-production-line-delete-form data-production-line-id="{{ $productionLine->id }}">
	<div class="operation-confirm__header">
		<h2 class="operation-confirm__title">Удаление производственной линии</h2>
	</div>
	<div class="operation-confirm__text">
		Вы действительно хотите удалить производственную линию
		«{{ $productionLine->name }}»?
	</div>

	<div class="operation-confirm__actions">
		<button type="button" class="button button--secondary" data-operation-modal-close>
			<span>Отмена</span>
		</button>

		<button type="button" class="button button--primary" data-production-line-delete-confirm
				data-production-line-id="{{ $productionLine->id }}">
			<span>Удалить</span>
		</button>
	</div>
</div>
