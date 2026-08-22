<div class="material-issue">

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
			<div class="material-issue__label">Материал</div>

			<div class="material-issue__value">
				{{ $issue->material->name }}
			</div>
		</div>

		<div class="material-issue__row">
			<div class="material-issue__label">Идентификатор</div>

			<div class="material-issue__value">
				{{ $issue->material->identifier }}
			</div>
		</div>

		<div class="material-issue__row">
			<div class="material-issue__label">Номер рулона</div>

			<div class="material-issue__value">
				{{ $issue->roll->roll_number }}
			</div>
		</div>

		<div class="material-issue__row">
			<div class="material-issue__label">Вес расхода</div>

			<div class="material-issue__value">
				{{ number_format($issue->weight, 3, '.', '') }} кг
			</div>
		</div>

		<div class="material-issue__row">
			<div class="material-issue__label">Пользователь</div>

			<div class="material-issue__value">
				{{ $issue->user->name }}
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

</div>