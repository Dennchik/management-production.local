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

	<div class="operation-view__section">
		<div class="operation-view__label">Входные компоненты</div>

		@php
			$inputs = $operation->components->where('direction', 'input');
		@endphp

		@if ($inputs->isEmpty())
			<div class="operation-view__empty">
				Входные компоненты не заданы.
			</div>
		@else
			<div class="operation-view__components">
				@foreach ($inputs as $component)
					<div class="operation-view__component">
						<div class="operation-view__component-main">
                     <span class="operation-view__component-name">
                        {{ $component->material->name }}
                     </span>

							@if ($component->quantity !== null)
								<span class="operation-view__component-quantity">
                           {{ rtrim(rtrim(number_format($component->quantity, 3, '.', ''), '0'), '.') }}
									{{ $component->unit }}
                        </span>
							@endif
						</div>

						<div class="operation-view__component-meta">
							{{ $component->is_required ? 'Обязательный' : 'Необязательный' }}
						</div>

						@if ($component->comment)
							<div class="operation-view__component-comment">
								{{ $component->comment }}
							</div>
						@endif
					</div>
				@endforeach
			</div>
		@endif
	</div>

	<div class="operation-view__section">
		<div class="operation-view__label">Выходные компоненты</div>

		@php
			$outputs = $operation->components->where('direction', 'output');
		@endphp

		@if ($outputs->isEmpty())
			<div class="operation-view__empty">
				Выходные компоненты не заданы.
			</div>
		@else
			<div class="operation-view__components">
				@foreach ($outputs as $component)
					<div class="operation-view__component">
						<div class="operation-view__component-main">
                     <span class="operation-view__component-name">
                        {{ $component->material->name }}
                     </span>

							@if ($component->quantity !== null)
								<span class="operation-view__component-quantity">
                           {{ rtrim(rtrim(number_format($component->quantity, 3, '.', ''), '0'), '.') }}
									{{ $component->unit }}
                        </span>
							@endif
						</div>

						<div class="operation-view__component-meta">
							{{ $component->is_required ? 'Обязательный' : 'Необязательный' }}
						</div>

						@if ($component->comment)
							<div class="operation-view__component-comment">
								{{ $component->comment }}
							</div>
						@endif
					</div>
				@endforeach
			</div>
		@endif
	</div>

	<div class="operation-view__actions">
		<a class="button" href="{{ route('production.operations.edit', $operation) }}">
			Редактировать
		</a>

		<button class="button" type="button" data-production-operation-delete="{{ $operation->id }}">
			Удалить
		</button>
	</div>
</div>