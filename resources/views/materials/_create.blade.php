<div class="material-show material-form" data-material-form>
	<div class="material-show__header">
		<h2 class="material-show__title">{{ isset($prefill) ? 'Новый формат материала' : 'Создание материала' }}</h2>
	</div>

	<div class="material-show__content">
		<div class="material-show__row">
			<span>Шаблон названия:</span>
			<label>
				<input type="text" name="name-template" id="name-template" autocomplete="off" disabled
						value="{{ $nameTemplate }}">
			</label>
		</div>

		<div class="material-show__row">
			<span>Название:</span>
			<label>
				<input type="text"
						name="material-name"
						id="material-name"
						autocomplete="off"
						list="names-list"
						value="{{ $prefill->name ?? '' }}">
				<datalist id="names-list">
					{{-- Список наименований будет заполнен позже по справочнику пользователя --}}
				</datalist>
			</label>
		</div>

		<div class="material-show__row">
			<span>Код:</span>
			<label>
				<input type="text" name="code" autocomplete="off" list="codes-list" value="{{ $prefill->code ?? '' }}">
				<datalist id="codes-list">
					{{-- Список кодов будет заполнен позже по справочнику пользователя --}}
				</datalist>
			</label>
		</div>

		<div class="material-show__row">
			<span>Идентификатор:</span>
			<label>
				<input type="text" name="identifier" id="identifier" readonly>
			</label>
		</div>

		<div class="material-show__row">
			<span>Грамматура:</span>
			<label>
				<input type="number"
						name="grammage"
						min="0"
						step="0.01"
						list="grammages-list"
						value="{{ $prefill->grammage ?? '' }}">
				<datalist id="grammages-list">
					{{-- Список грамматур будет заполнен позже по справочнику пользователя --}}
				</datalist>
			</label>
		</div>

		<div class="material-show__row">
			<span>Толщина:</span>
			<label>
				<input type="number" name="thickness" min="0" step="0.01" value="{{ $prefill->thickness ?? '' }}">
			</label>
		</div>

		<div class="material-show__row">
			<span>Формат:</span>
			<label>
				<input type="text" name="format" id="material-format" autocomplete="off" inputmode="numeric">
			</label>
		</div>

		<div class="material-show__row">
			<span>Каталог:</span>
			<label>
				<div class="select material-select">
					<input class="select__value" name="catalog_id" type="hidden" value="{{ $selectedCatalogId ?? '' }}">

					<button class="material-select__select-button select__button select-button" type="button"
							aria-haspopup="listbox" aria-expanded="false">
						<span class="material-select__select-value select__button-text">
							{{ $catalogOptions[$selectedCatalogId ?? ''] ?? '— Нет —' }}
						</span>
						<i class="material-select__select-arrow" aria-hidden="true"></i>
					</button>

					<div class="select__dropdown material-select__select-list _collapse" role="listbox">
						<button class="material-select__select-option select__item" type="button" role="option"
								data-value="">— Нет —
						</button>

						@foreach ($catalogOptions as $catalogId => $catalogLabel)
							<button class="material-select__select-option select__item" type="button" role="option"
									data-value="{{ $catalogId }}">{{ $catalogLabel }}</button>
						@endforeach
					</div>
				</div>
			</label>
		</div>

		<div class="material-show__row">
			<span>Назначение материала:</span>

			<div class="material-form__operations">
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

			<label class="material-show__status">
				<input type="checkbox" name="is_active" checked>Активен
			</label>
		</div>
	</div>

	<div class="material-show__actions">
		<button class="button button--primary" type="button" data-action="save">
			<span>Сохранить</span>
		</button>
	</div>
</div>