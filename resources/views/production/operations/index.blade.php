@extends('layouts.app')

@section('content')
	<div class="main-content__content" data-production-operations-page>
		<div class="main-content__header">
			<h1 class="main-content__title">Производственные операции</h1>

			<a class="button" href="{{ route('production.operations.create') }}">
				Добавить операцию
			</a>
		</div>

		<div class="operations">
			<div class="operations__content" data-production-operations-content>
				@if ($operations->isEmpty())
					<div class="empty-state" data-production-operations-empty>
						<p>Производственные операции пока не созданы.</p>
					</div>
				@else
					<div class="table">
						<div class="table__row table__row--header">
							<div class="table__cell">№</div>
							<div class="table__cell">Название</div>
							<div class="table__cell">Код</div>
							<div class="table__cell">Компоненты</div>
							<div class="table__cell">Статус</div>
						</div>

						@foreach ($operations as $operation)
							<a class="table__row"
									href="{{ route('production.operations.show', $operation) }}"
									data-production-operation-open data-production-operation-id="{{ $operation->id }}">

								<div class="table__cell">
									{{ $loop->iteration }}
								</div>

								<div class="table__cell">
									{{ $operation->name }}
								</div>

								<div class="table__cell">
									{{ $operation->code }}
								</div>

								<div class="table__cell">
									{{ $operation->components_count }}
								</div>

								<div class="table__cell">
									{{ $operation->is_active ? 'Активна' : 'Неактивна' }}
								</div>
							</a>
						@endforeach
					</div>
				@endif
			</div>
		</div>
	</div>
@endsection