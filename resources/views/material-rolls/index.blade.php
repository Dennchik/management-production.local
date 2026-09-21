@extends('layouts.app')

@section('title', 'Рулоны')

@section('content')

	<div class="main-content__content">
			{{-- Фильтр --}}
			@include('layouts.filters-actions', [
			  'filterAction' => route('material-rolls.index'),
			  'filterReset' => route('material-rolls.index'),
			  'filterType' => 'rolls',
			  'filterSearch' => $search,
			])
		<div class="main-content__header">
			<h1 class="main-content__title">Рулоны</h1>
		</div>

		<div class="material">
			<div class="material__rows material__rows--rolls">
				<div class="material__head">
					<div class="material__label">№ рулона</div>
					<div class="material__label">Материал</div>
					<div class="material__label">Идентификатор</div>
					<div class="material__label">Формат</div>
					<div class="material__label">Остаток</div>

				</div>
				@forelse ($rolls as $roll)
					<a class="material__row" href="{{ route('material-rolls.show', $roll) }}">
						<div class="material__value">{{ $roll->roll_number }}</div>
						<div class="material__value">{{ $roll->material->name }}</div>
						<div class="material__value">{{ $roll->identifier ?? '—' }}</div>
						<div class="material__value">{{ $roll->format ?? '—' }}</div>
						<div class="material__value">{{ number_format($roll->weight, 3, '.', '') }} кг</div>
					</a>
				@empty
			</div>
		</div>
		<div class="material__empty">Рулонов на складе нет</div>

		@endforelse
	</div>

@endsection