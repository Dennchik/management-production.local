<div class="material-receipt">

	<div class="material-receipt__header">
		<h2 class="main-content__title">Приходный ордер</h2>
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
				<table>
					<thead>
						<tr>
							<th>Материал</th>
							<th>Формат</th>
							<th>Идентификатор</th>
							<th>Номер рулона</th>
							<th>Вес, кг</th>
						</tr>
					</thead>
					<tbody>
						@foreach ($receipt->items as $item)
							<tr>
								<td>{{ $item->material->name }}</td>
								<td>{{ $item->roll->format ?? '—' }}</td>
								<td>{{ $item->roll->identifier ?? '—' }}</td>
								<td>{{ $item->roll->roll_number }}</td>
								<td>{{ number_format($item->weight, 3, '.', '') }}</td>
							</tr>
						@endforeach
					</tbody>
				</table>
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
				<div class="material-receipt__value">{{ $receipt->comment }}</div>
			</div>

		@endif
	</div>
</div>