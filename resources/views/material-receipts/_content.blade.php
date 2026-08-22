<div class="material-receipt">

	<div class="material-receipt__header">

		<h2 class="main-content__title">
			Приходный ордер
		</h2>

	</div>

	<div class="material-receipt__content">

		{{-- Дата --}}
		<div class="material-receipt__row">
			<div class="material-receipt__label">Дата</div>

			<div class="material-receipt__value">
				{{ $receipt->created_at->format('d.m.Y H:i') }}
			</div>
		</div>

		{{-- Пользователь --}}
		<div class="material-receipt__row">
			<div class="material-receipt__label">Пользователь</div>

			<div class="material-receipt__value">
				{{ $receipt->user->name }}
			</div>
		</div>

		{{-- Рулоны --}}
		<div class="material-receipt__row">
			<div class="material-receipt__label">Рулоны</div>

			<div class="material-receipt__value">

				@foreach ($receipt->items as $item)

					<div class="material-receipt__roll">

						<div>
							<strong>{{ $item->material->name }}</strong>
						</div>

						<div>
							Идентификатор:
							{{ $item->material->identifier }}
						</div>

						<div>
							Номер рулона:
							{{ $item->roll->roll_number }}
						</div>

						<div>
							Вес:
							{{ number_format($item->weight, 3, '.', '') }} кг
						</div>

					</div>

				@endforeach

			</div>
		</div>

		{{-- Общий вес --}}
		<div class="material-receipt__row">
			<div class="material-receipt__label">Общий вес</div>

			<div class="material-receipt__value">
				{{ number_format($receipt->items->sum('weight'), 3, '.', '') }} кг
			</div>
		</div>

		{{-- Комментарий --}}
		@if ($receipt->comment)

			<div class="material-receipt__row">
				<div class="material-receipt__label">Комментарий</div>

				<div class="material-receipt__value">
					{{ $receipt->comment }}
				</div>
			</div>

		@endif

	</div>

</div>