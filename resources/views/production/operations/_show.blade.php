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

	@if ($operation->is_cutting)
		<div class="operation-view__section">
			<div class="operation-view__label">Режим резки</div>
			<div class="operation-view__description">
				На входе один материал, на выходе — тот же материал другого формата.
			</div>
		</div>
	@endif

	<div class="operation-view__section">
		<div class="operation-view__label">Выходные товары</div>
		<div class="operation-view__description">
			@if ($operation->outputOperations->isEmpty())
				—
			@else
				{{ $operation->outputOperations->pluck('name')->implode(', ') }}
			@endif
		</div>
	</div>

	<div class="operation-view__actions">
		<a class="button" href="{{ route('production.operations.edit', $operation) }}">
			<span>Редактировать</span>
		</a>

		<button class="button" type="button" data-production-operation-delete="{{ $operation->id }}">
			<span>Удалить</span>
		</button>
	</div>
</div>