@extends('layouts.app')

@section('title', $productionLine->name)

@section('content')
	<div class="main-content__content" data-production-lines-page>
		<div class="main-content__header">
			<h1 class="main-content__title">{{ $productionLine->name }}</h1>

			<div class="catalogs__header-actions">
				<a class="button" href="{{ route('production.lines.line.edit', [$operation, $productionLine]) }}">
					Редактировать
				</a>

				<button class="button button--danger" type="button" data-production-line-delete="{{ $productionLine->id }}">
					Удалить
				</button>
			</div>
		</div>

		@include('partials.message')

		<nav class="materials__breadcrumbs">
			<a href="{{ route('production.operations.index') }}">Технологические линии</a>
			<span> / </span>
			<a href="{{ route('production.lines.show', $operation) }}">{{ $operation->name }}</a>
			<span> / </span>
			<span class="materials__breadcrumbs-current">{{ $productionLine->name }}</span>
		</nav>

		<div class="operation-form__section">
			<div class="operation-form__section-header">
				<h2 class="operation-form__section-title">Материалы (вход)</h2>
			</div>

			<div class="table">
				<div class="table__row-line table__row--header">
					<div class="table__cell">№</div>
					<div class="table__cell">Материал</div>
					<div class="table__cell">Идентификатор</div>
					<div class="table__cell">Формат</div>
				</div>

				@foreach ($productionLine->inputMaterials as $material)
					<div class="table__row-line">
						<div class="table__cell">{{ $loop->iteration }}</div>
						<div class="table__cell">{{ $material->name }}</div>
						<div class="table__cell">{{ $material->identifierForFormat($material->pivot->format) ?? '—' }}</div>
						<div class="table__cell">{{ $material->pivot->format ?? '—' }}</div>
					</div>
				@endforeach
			</div>
		</div>

		<div class="operation-form__section">
			<div class="operation-form__section-header">
				<h2 class="operation-form__section-title">Материалы (выход)</h2>
			</div>

			<div class="table">
				<div class="table__row-line table__row--header">
					<div class="table__cell">№</div>
					<div class="table__cell">Материал</div>
					<div class="table__cell">Идентификатор</div>
					<div class="table__cell">Формат</div>
				</div>

				@foreach ($productionLine->outputMaterials as $material)
					<div class="table__row-line">
						<div class="table__cell">{{ $loop->iteration }}</div>
						<div class="table__cell">{{ $material->name }}</div>
						<div class="table__cell">{{ $material->identifierForFormat($material->pivot->format) ?? '—' }}</div>
						<div class="table__cell">{{ $material->pivot->format ?? '—' }}</div>
					</div>
				@endforeach
			</div>
		</div>
	</div>
@endsection
