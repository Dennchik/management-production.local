<div class="material-receipt">

	<div class="material-receipt__header">
		<h2 class="main-content__title">Ордер корректировки</h2>
	</div>

	<div class="material-receipt__content">

		{{-- Дата --}}
		<div class="material-receipt__row">
			<div class="material-receipt__label">Дата</div>

			<div class="material-receipt__value">
				{{ $adjustment->created_at->format('d.m.Y H:i') }}
			</div>
		</div>

		{{-- Материал --}}
		<div class="material-receipt__row">
			<div class="material-receipt__label">Материал</div>

			<div class="material-receipt__value">
				{{ $adjustment->material->name }}
			</div>
		</div>

		{{-- Рулоны --}}
		<div class="material-receipt__row">
			<div class="material-receipt__label">Рулон</div>

			<div class="material-receipt__value">
				<table>
					<thead>
						<tr>
							<th>Номер рулона</th>
							<th>Текущий вес, кг</th>
							<th>Корректировка, кг</th>
							<th>Конечный вес, кг</th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td>{{ $adjustment->roll->roll_number }}</td>
							<td>{{ rtrim(rtrim(number_format((float) $adjustment->weight_before, 3, '.', ''), '0'), '.') }}</td>
							<td class="{{ $adjustment->adjustment < 0 ? 'text-red-soft' : '' }}">
								{{ ($adjustment->adjustment > 0 ? '+' : '') . rtrim(rtrim(number_format((float) $adjustment->adjustment, 3, '.', ''), '0'), '.') }}
							</td>
							<td>{{ rtrim(rtrim(number_format((float) $adjustment->weight_after, 3, '.', ''), '0'), '.') }}</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		{{-- Пользователь --}}
		<div class="material-receipt__row">
			<div class="material-receipt__label">Пользователь</div>

			<div class="material-receipt__value">
				{{ $adjustment->user->name ?? '—' }}
			</div>
		</div>

		{{-- Комментарий --}}
		@if ($adjustment->comment)

			<div class="material-receipt__row">
				<div class="material-receipt__label">Комментарий</div>
				<div class="material-receipt__value">{{ $adjustment->comment }}</div>
			</div>

		@endif
	</div>
</div>
