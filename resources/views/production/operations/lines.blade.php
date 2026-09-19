@extends('layouts.app')

@section('title', 'Производственные линии — ' . $operation->name)

@section('content')
	<div class="main-content__content">
		<div class="main-content__header">
			<h1 class="main-content__title">{{ $operation->name }}</h1>

			<a class="button button--primary" href="{{ route('production.lines.create', $operation) }}">
				Добавить
			</a>
		</div>

		@include('partials.message')

		<nav class="materials__breadcrumbs">
			<a href="{{ route('production.operations.index') }}">Технологические линии</a>
			<span> / </span>
			<span class="materials__breadcrumbs-current">{{ $operation->name }}</span>
		</nav>

		<div class="operations">
			<div class="operations__content">
				<div class="table">
					<div class="table__row table__row--header">
						<div class="table__cell">№</div>
						<div class="table__cell">Название</div>
						<div class="table__cell"></div>
					</div>

					@foreach ($productionLines as $productionLine)
						<a class="table__row-line" href="{{ route('production.lines.line.show', [$operation, $productionLine]) }}">
							<div class="table__cell">{{ $loop->iteration }}</div>
							<div class="table__cell">{{ $productionLine->name }}</div>
							<div class="table__cell production-line-materials__actions">
								<button class="button button--danger" type="button"
										data-production-line-delete="{{ $productionLine->id }}">
									Удалить
								</button>
							</div>
						</a>
					@endforeach
				</div>
			</div>
		</div>
	</div>
@endsection
