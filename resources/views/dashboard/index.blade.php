@extends('layouts.app')

@section('title', 'Главная')

@section('content')

	<h1 class="main-content__title">Главная</h1>

	{{-- Информационные блоки --}}
	<section class="main-content__section">
		<h2 class="main-content__section-title">Состояние производства</h2>

		<div class="main-content__stats main-content__stats--grid">

			{{-- 1. Сырье на складе --}}
			<a href="{{ route('warehouse.index') }}"
					class="main-content__stat main-content__stat--link">
				<span class="main-content__stat-label">Сырье на складе</span>
				<strong class="main-content__stat-value">{{ number_format($rawMaterialsWeight, 0, '.', ' ') }}
					кг</strong>
				<span class="main-content__stat-sub">{{ $rawMaterialsRolls }} рулонов</span>
			</a>

			{{-- 2. ПФ не праймированный --}}
			<a href="{{ route('warehouse.index', ['codes' => ['30', '40']]) }}"
					class="main-content__stat main-content__stat--link">
				<span class="main-content__stat-label">ПФ не праймированный</span>
				<strong class="main-content__stat-value">
					{{ number_format($unprimedPfWeight, 0, '.', ' ') }} кг
				</strong>
				<span class="main-content__stat-sub">
				  {{ $unprimedPfRolls }} рулонов
				</span>
			</a>

			{{-- 3. ПФ праймированный --}}
			<a href="{{ route('warehouse.index', ['codes' => ['31', '41']]) }}"
					class="main-content__stat main-content__stat--link">
				<span class="main-content__stat-label">ПФ праймированный</span>
				<strong class="main-content__stat-value">
					{{ number_format($primedPfWeight, 0, '.', ' ') }} кг
				</strong>
				<span class="main-content__stat-sub">
				  {{ $primedPfRolls }} рулонов
				</span>
			</a>

			{{-- 4. ПФ на резку --}}
			<a href="{{ route('warehouse.index', ['codes' => ['30', '31', '40', '41']]) }}"
					class="main-content__stat main-content__stat--link">
				<span class="main-content__stat-label">ПФ на резку</span>
				<strong class="main-content__stat-value">
					{{ number_format($cuttingPfWeight, 0, '.', ' ') }} кг
				</strong>
				<span class="main-content__stat-sub">
				  {{ $cuttingPfRolls }} рулонов
				</span>
			</a>

			{{-- 5. ПФ на печать --}}
			<a
					href="{{ route('warehouse.index', ['codes' => ['30', '31', '40', '41']]) }}"
					class="main-content__stat main-content__stat--link">
				<span class="main-content__stat-label">ПФ на печать</span>
				<strong class="main-content__stat-value">
					{{ number_format($printingPfWeight, 0, '.', ' ') }} кг
				</strong>
				<span class="main-content__stat-sub">
				  {{ $printingPfRolls }} рулонов
				</span>
			</a>

			{{-- 6. Материалы с низким остатком --}}
			<a href="{{ route('warehouse.index', ['stock' => 'low']) }}"
					class="main-content__stat main-content__stat--link">
				<span class="main-content__stat-label">Низкий остаток</span>
				<strong class="main-content__stat-value">{{ $lowStockMaterials->count() }}</strong>☼
				<span class="main-content__stat-sub">материалов</span> </a>
		</div>
	</section>

	{{-- Быстрые действия --}}
	<section class="main-content__section">
		<div class="quick-action">
			<h2 class="main-content__section-title">
				Быстрые действия
			</h2>

			<div class="quick-action__content">
				<a class="quick-action__link button"
						href="{{ route('material-receipts.create') }}">
					<span>Оприходовать сырьё</span>
				</a>

				<a class="quick-action__link button"
						href="{{ route('material-issues.create') }}">
					<span>Оформить расход</span>
				</a>

				<a class="quick-action__link button"
						href="{{ route('warehouse.index') }}">
					<span>Смотреть склад</span>
				</a>
			</div>
		</div>
	</section>

	{{-- Последние операции --}}
	<section class="main-content__section">
		<div class="section-header">
			<h2 class="main-content__section-title">
				Последние операции
			</h2>
			<a href="{{ route('material-movements.index') }}"
					class="main-content__link-more button">
				<span>Все операции</span>
				<i class="icon-angles-right-solid icon"></i>
			</a>
		</div>

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
									@endforeach </span>
								<span class="main-content__operation-roll">
									Рулонов: {{ $operation['receipt']->items->count() }}
								</span>

								<strong class="main-content__operation-weight">
									+{{ number_format( $operation['receipt']->items->sum('weight'), 3, '.', '' ) }} кг
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
								$operation['issue']->weight, 3, '.', '' ) }} кг
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