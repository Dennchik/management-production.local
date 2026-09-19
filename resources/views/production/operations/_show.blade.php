<div class="operation-view" data-production-operation-show data-production-operation-id="{{ $operation->id }}">
	<div class="operation-view__header">
		<div>
			<h2 class="operation-view__title">{{ $operation->name }}</h2>

			<div class="operation-view__code">
				Код: {{ $operation->code }}
			</div>
		</div>

		<div class="operation-view__status">
			{{ $operation->is_active ? 'Активна' : 'Неактивна' }}
		</div>
	</div>

	@if ($operation->description)
		<div class="operation-view__section">
			<div class="operation-view__label">Описание</div>
			<div class="operation-view__description">{{ $operation->description }}</div>
		</div>
	@endif

	<div class="operation-view__actions">
		<a class="button" href="{{ route('production.operations.edit', $operation) }}">
			Редактировать
		</a>

		<button class="button" type="button" data-production-operation-delete="{{ $operation->id }}">
			Удалить
		</button>
	</div>
</div>