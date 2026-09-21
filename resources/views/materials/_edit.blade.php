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
			<span>Каталог:</span>
			<label>
				<div class="select material-select">
					<input class="select__value" name="catalog_id" type="hidden" value="{{ $material->catalog_id ?? '' }}">

					<button class="material-select__select-button select__button select-button" type="button"
							aria-haspopup="listbox" aria-expanded="false">
						<span class="material-select__select-value select__button-text">
							{{ $catalogOptions[$material->catalog_id] ?? '— Нет —' }}
						</span>
						<span class="material-select__select-arrow" aria-hidden="true"></span>
					</button>

					<div class="select__dropdown material-select__select-list _collapse" role="listbox">
						<button class="material-select__select-option select__item" type="button" role="option"
								data-value="">— Нет —</button>

						@foreach ($catalogOptions as $catalogId => $catalogLabel)
							<button class="material-select__select-option select__item" type="button" role="option"
									data-value="{{ $catalogId }}">{{ $catalogLabel }}</button>
						@endforeach
					</div>
				</div>
			</label>
		</div>

		<div class="material-show__row">
			<span>Тип материала:</span>
			<label>
				<div class="select material-select">
					<input class="select__value" name="material_type" type="hidden" value="{{ $material->material_type }}">

					<button class="material-select__select-button select__button select-button" type="button"
							aria-haspopup="listbox" aria-expanded="false">
						<span class="material-select__select-value select__button-text">
							{{ \App\Models\Material::TYPES[$material->material_type] ?? '' }}
						</span>
						<span class="material-select__select-arrow" aria-hidden="true"></span>
					</button>

					<div class="select__dropdown material-select__select-list _collapse" role="listbox">
						@foreach (\App\Models\Material::TYPES as $typeValue => $typeLabel)
							<button class="material-select__select-option select__item" type="button" role="option"
									data-value="{{ $typeValue }}">{{ $typeLabel }}</button>
						@endforeach
					</div>
				</div>
			</label>
		</div>

		<div class="material-show__row">
			<span>Разрешённые операции:</span>

			<div class="material-show__operations">
				@if ($productionLines->isEmpty())
					<span>Нет активных технологических линий.</span>
				@else
					@foreach ($productionLines as $line)
						<label>
							<input type="checkbox" name="allowed_operations[]" value="{{ $line->id }}"
									{{ in_array($line->id, $selectedOperationIds ?? [], false) ? 'checked' : '' }}>
							{{ $line->name }}
						</label>
					@endforeach
				@endif
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