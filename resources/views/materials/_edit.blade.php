<div class="material-show material-form" data-material-form data-material-id="{{ $material->id }}">
	<div class="material-show__header">
		<h2 class="material-show__title">Редактирование материала</h2>
	</div>

	<div class="material-show__content">
		<div class="material-show__row">
			<span>Наименование:</span>
			<label>
				<input type="text" name="material-name" value="{{ $material->name }}" autocomplete="off">
			</label>
		</div>

		<div class="material-show__row">
			<span>Код:</span>
			<label>
				<input type="text" name="code" value="{{ $material->code }}" autocomplete="off">
			</label>
		</div>

		<div class="material-show__row">
			<span>Идентификатор:</span>
			<input type="text" name="identifier" value="{{ $material->identifier }}" readonly>
		</div>

		<div class="material-show__row">
			<span>Грамматура:</span>
			<label>
				<input type="number" name="grammage" value="{{ $material->grammage }}" min="0" step="0.01">
			</label>
		</div>

		<div class="material-show__row">
			<span>Толщина:</span>
			<label>
				<input type="number" name="thickness" value="{{ $material->thickness }}" min="0" step="0.01">
			</label>
		</div>

		<div class="material-show__row">
			<span>Формат:</span>
			<label>
				<input type="text" name="format" value="{{ $material->format }}" autocomplete="off">
			</label>
		</div>

		<div class="material-show__row">
			<span>Каталог:</span>
			<label>
				<select class="catalogs__parent-select" name="catalog_id">
					<option value="">— Нет —</option>
					@foreach ($catalogOptions as $catalogId => $catalogLabel)
						<option value="{{ $catalogId }}" {{ $material->catalog_id === $catalogId ? 'selected' : '' }}>
							{{ $catalogLabel }}
						</option>
					@endforeach
				</select>
			</label>
		</div>

		<div class="material-show__row">
			<span>Тип материала:</span>
			<label>
				<select class="catalogs__parent-select" name="material_type">
					@foreach (\App\Models\Material::TYPES as $typeValue => $typeLabel)
						<option value="{{ $typeValue }}" {{ $material->material_type === $typeValue ? 'selected' : '' }}>{{ $typeLabel }}</option>
					@endforeach
				</select>
			</label>
		</div>

		{{-- Разрешённые операции: скрыто до запуска функционала --}}
		<div class="material-show__row" hidden>
			<span>Разрешённые операции:</span>

			<div class="material-form__operations">
				<label>
					<input type="checkbox" name="lamination_allowed" {{ $material->lamination_allowed ? 'checked' : '' }}>
					Ламинация
				</label>

				<label>
					<input type="checkbox" name="priming_allowed" {{ $material->priming_allowed ? 'checked' : '' }}>
					Праймирование
				</label>

				<label>
					<input type="checkbox" name="cutting_allowed" {{ $material->cutting_allowed ? 'checked' : '' }}>
					Резка
				</label>

				<label>
					<input type="checkbox" name="printing_allowed" {{ $material->printing_allowed ? 'checked' : '' }}>
					Печать
				</label>
			</div>
		</div>

		<div class="material-show__row">
			<span>Статус:</span>

			<label>
				<input type="checkbox" name="is_active" {{ $material->is_active ? 'checked' : '' }}>
				Активен
			</label>
		</div>
	</div>

	<div class="material-show__actions">
		<button class="button button--primary" type="button" data-action="update">Сохранить</button>
	</div>
</div>