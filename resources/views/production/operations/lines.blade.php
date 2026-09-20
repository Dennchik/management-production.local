@extends('layouts.app')

@section('title', 'Производственные линии — ' . $operation->name)

@section('content')
	<div class="main-content__content">
		<div class="main-content__header">
			<h1 class="main-content__title">{{ $operation->name }}</h1>

			<div class="catalogs__header-actions">
				<a class="button button--primary" href="{{ route('production.lines.create', $operation) }}">
					Добавить
				</a>

				<a class="button button--secondary"
						href="{{ route('production.operations.allowed-catalogs', $operation) }}">
					Разрешённые материалы
				</a>
			</div>
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
					{{-- Заголовок и строки — один и тот же класс сетки, чтобы колонки совпадали. --}}
					<div class="table__row-line table__row--header">
						<div class="table__cell">№</div>
						<div class="table__cell">Название</div>
						<div class="table__cell">Материалы (вход)</div>
						<div class="table__cell">Материалы (выход)</div>
						<div class="table__cell materials__table-edit">
							<i class="icon-settings-cogs icon"></i>
						</div>
					</div>

					@foreach ($productionLines as $productionLine)
						<a class="table__row-line" href="{{ route('production.lines.line.show', [$operation, $productionLine]) }}">
							<div class="table__cell">{{ $loop->iteration }}</div>
							<div class="table__cell">{{ $productionLine->name }}</div>

							<div class="table__cell table__cell--stack">
								@forelse ($productionLine->inputMaterials as $material)
									<div>{{ $material->name }}@if ($material->pivot->format) — {{ $material->pivot->format }} @endif</div>
								@empty
									—
								@endforelse
							</div>

							<div class="table__cell table__cell--stack">
								@forelse ($productionLine->outputMaterials as $material)
									<div>{{ $material->name }}@if ($material->pivot->format) — {{ $material->pivot->format }} @endif</div>
								@empty
									—
								@endforelse
							</div>

							<div class="table__cell production-line-materials__actions">
								<button type="button" class="production-line-materials__remove"
										data-production-line-delete="{{ $productionLine->id }}"
										aria-label="Удалить линию" title="Удалить линию">
									<i class="icon icon-trash" aria-hidden="true"></i>
								</button>
							</div>
						</a>
					@endforeach
				</div>
			</div>
		</div>
	</div>
@endsection
