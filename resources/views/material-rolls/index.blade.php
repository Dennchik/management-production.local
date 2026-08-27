@extends('layouts.app')

@section('title', 'Рулоны')

@section('content')

	<div class="material-rolls">

		<div class="material-rolls__content">

			{{-- Фильтр --}}
			<div class="material-rolls__filter">
				@include('layouts.filters-actions', [
				  'filterAction' => route('material-rolls.index'),
				  'filterReset' => route('material-rolls.index'),
				  'filterType' => 'rolls',
				  'filterSearch' => $search,
				])
			</div>

			<h1 class="main-content__title">Рулоны</h1>

			@forelse ($rolls as $roll)

				<a class="material-rolls__item"
						href="{{ route('material-rolls.show', $roll) }}">

					<div class="material-rolls__row">
						<div class="material-rolls__label">
							Номер рулона
						</div>

						<div class="material-rolls__value">
							{{ $roll->roll_number }}
						</div>
					</div>

					<div class="material-rolls__row">
						<div class="material-rolls__label">
							Материал
						</div>

						<div class="material-rolls__value">
							{{ $roll->material->name }}
						</div>
					</div>

					<div class="material-rolls__row">
						<div class="material-rolls__label">
							Идентификатор
						</div>

						<div class="material-rolls__value">
							{{ $roll->material->identifier }}
						</div>
					</div>

					<div class="material-rolls__row">
						<div class="material-rolls__label">
							Остаток
						</div>

						<div class="material-rolls__value">
							{{ number_format($roll->weight, 3, '.', '') }} кг
						</div>
					</div>
				</a>

			@empty

				<div class="material-rolls__empty">
					Рулонов на складе нет.
				</div>

			@endforelse

		</div>

	</div>

@endsection