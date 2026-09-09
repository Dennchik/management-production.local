<div class="material-show material-form" data-material-form>
	<div class="material-show__header">
		<h2 class="material-show__title">Создание материала</h2>
	</div>

	<div class="material-show__content">
		<div class="material-show__row">
			<span>Наименование:</span>
			<label>
				<input type="text" name="material-name" autocomplete="off">
			</label>
		</div>

		<div class="material-show__row">
			<span>Код:</span>
			<label>
				<input type="text" name="code" autocomplete="off">
			</label>
		</div>

		<div class="material-show__row">
			<span>Идентификатор:</span>
			<input type="text" name="identifier" readonly>
		</div>

		<div class="material-show__row">
			<span>Грамматура:</span>
			<label>
				<input type="number" name="grammage" min="0" step="0.01">
			</label>
		</div>

		<div class="material-show__row">
			<span>Толщина:</span>
			<label>
				<input type="number" name="thickness" min="0" step="0.01">
			</label>
		</div>

		<div class="material-show__row">
			<span>Формат:</span>
			<label>
				<input type="text" name="format" autocomplete="off">
			</label>
		</div>

		<div class="material-show__row">
			<span>Разрешённые операции:</span>

			<div class="material-form__operations">
				<label>
					<input type="checkbox" name="lamination_allowed">
					Ламинация
				</label>

				<label>
					<input type="checkbox" name="priming_allowed">
					Праймирование
				</label>

				<label>
					<input type="checkbox" name="cutting_allowed">
					Резка
				</label>

				<label>
					<input type="checkbox" name="printing_allowed">
					Печать
				</label>
			</div>
		</div>

		<div class="material-show__row">
			<span>Статус:</span>

			<label>
				<input type="checkbox" name="is_active" checked>
				Активен
			</label>
		</div>
	</div>

	<div class="material-show__actions">
		<button class="button button--primary" type="button" data-action="save">
			Сохранить
		</button>
	</div>
</div>