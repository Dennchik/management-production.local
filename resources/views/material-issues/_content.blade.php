<div class="material-issue">
	<div class="material-issue__body">
		<div class="material-issue__header">

			<h2 class="main-content__title">
				Расходный ордер
			</h2>

		</div>

		<div class="material-issue__content">
			<div class="material-issue__row">
				<div class="material-issue__label">Дата</div>

				<div class="material-issue__value">
					{{ $issue->created_at->format('d.m.Y H:i') }}
				</div>
			</div>

			<div class="material-issue__row">
				<div class="material-issue__label">Пользователь</div>

				<div class="material-issue__value">
					{{ $issue->user->name }}
				</div>
			</div>

			<div class="material-issue__row">
				<div class="material-issue__label">Позиций</div>

				<div class="material-issue__value">
					{{ $rows->count() }}
				</div>
			</div>

			<div class="material-issue__row">
				<div class="material-issue__label">Общий вес</div>

				<div class="material-issue__value">
					{{ number_format($rows->sum('weight'), 3, '.', '') }} кг
				</div>
			</div>

			@if ($issue->comment)

				<div class="material-issue__row">
					<div class="material-issue__label">Комментарий</div>

					<div class="material-issue__value">
						{{ $issue->comment }}
					</div>
				</div>

			@endif
		</div>

		{{-- Позиции ордера --}}
		<div class="material__table-wrapper">
			<table class="material__table">
				<thead>
				<tr>
					<th>Материал</th>
					<th>Формат</th>
					<th>Номер рулона</th>
					<th>Вес расхода, кг</th>
				</tr>
				</thead>
				<tbody>

				@foreach ($rows as $row)
					<tr>
						<td> {{ $row->material->name }} </td>
						<td> {{ $row->material->format ?? '—' }} </td>
						<td> {{ $row->roll->roll_number }} </td>
						<td> {{ number_format($row->weight, 3, '.', '') }} </td>
					</tr>
				@endforeach
				</tbody>
			</table>
		</div>
	</div>
</div>
