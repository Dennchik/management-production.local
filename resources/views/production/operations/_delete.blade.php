<div class="operation-delete" data-production-operation-delete-form data-production-operation-id="{{ $operation->id }}">
	<div class="operation-delete__header">
		<h2 class="operation-delete__title">Удаление технологической линии</h2>
	</div>

	<div class="operation-delete__content">
		<p class="operation-delete__message">
			Вы действительно хотите удалить технологическую линию
			«{{ $operation->name }}»?
		</p>

		<p class="operation-delete__warning">
			Это действие нельзя отменить.
		</p>
	</div>

	<div class="operation-delete__actions">
		<button class="button button--secondary" type="button" data-operation-modal-close>
			Отмена
		</button>

		<button class="button button--danger" type="button" data-production-operation-confirm-delete>
			Удалить
		</button>
	</div>
</div>