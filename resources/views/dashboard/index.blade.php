@extends('layouts.app')

@section('title', 'Главная')

@section('content')

	<h1 class="main-content__title">Главная</h1>

	<section class="main-content__section">
		<h2 class="main-content__section-title">Ключевые показатели склада</h2>

		<div class="main-content__stats">
			<div class="main-content__stat">
				<span class="main-content__stat-label">Материалы</span>
				<strong class="main-content__stat-value">
					{{ $materialsCount }}
				</strong>
			</div>

			<div class="main-content__stat">
				<span class="main-content__stat-label">Рулоны</span>
				<strong class="main-content__stat-value">
					{{ $rollsCount }}
				</strong>
			</div>

			<div class="main-content__stat">
				<span class="main-content__stat-label">Остаток</span>
				<strong class="main-content__stat-value">
					{{ number_format($totalWeight, 3, '.', '') }} кг
				</strong>
			</div>
		</div>
	</section>

	{{-- Быстрые действия --}}
	<section class="main-content__section">

		<div class="quick-action">

			<h2 class="main-content__section-title">
				Быстрые действия
			</h2>

			<div class="quick-action__content">

				<a
						class="quick-action__link button"
						href="{{ route('material-receipts.create') }}">
					<span>Оприходовать сырьё</span>
				</a>

				<a
						class="quick-action__link button"
						href="{{ route('material-issues.create') }}">
					<span>Оформить расход</span>
				</a>

			</div>

		</div>

	</section>

	{{-- Последние операции --}}
	<section class="main-content__section">

		<h2 class="main-content__section-title">
			Последние операции
		</h2>

		@if ($recentOperations->isEmpty())

			<p class="main-content__empty">
				Операций пока нет.
			</p>

		@else

			<div class="main-content__operations">

				@foreach ($recentOperations as $operation)

					<article class="main-content__operation">

						<div class="main-content__operation-header">

							<strong class="main-content__operation-title">
								{{ $operation['operation'] }}
							</strong>

							<time class="main-content__operation-date">
								{{ $operation['date']->format('d.m.Y H:i') }}
							</time>

						</div>

						<div class="main-content__operation-info">

							@if ($operation['type'] === 'receipt')

								{{-- Приход --}}
								<span class="main-content__operation-material">
                   @foreach ($operation['receipt']->items as $item)

										{{ $item->material->name }}@if (!$loop->last)
											,
										@endif

									@endforeach
                 </span>

								<span class="main-content__operation-roll">
                   Рулонов: {{ $operation['receipt']->items->count() }}
                 </span>

								<strong class="main-content__operation-weight">
									+{{ number_format(
                     $operation['receipt']->items->sum('weight'),
                     3,
                     '.',
                     ''
                   ) }} кг
								</strong>

								<span class="main-content__operation-user">
                   {{ $operation['receipt']->user->name }}
                 </span>

							@else

								{{-- Расход --}}
								<span class="main-content__operation-material">
                   {{ $operation['issue']->material->name }}
                 </span>

								<span class="main-content__operation-roll">
                   Рулон №{{ $operation['issue']->roll->roll_number }}
                 </span>

								<strong class="main-content__operation-weight">
									−{{ number_format(
                     $operation['issue']->weight,
                     3,
                     '.',
                     ''
                   ) }} кг
								</strong>

								<span class="main-content__operation-user">
                   {{ $operation['issue']->user->name }}
                 </span>

							@endif

						</div>

					</article>

				@endforeach

			</div>

		@endif

	</section>

@endsection