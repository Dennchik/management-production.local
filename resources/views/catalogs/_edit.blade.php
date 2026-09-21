<div class="material-show material-form" data-catalog-form data-catalog-id="{{ $catalog->id }}">
	<div class="material-show__header">
		<h2 class="material-show__title">Редактирование каталога</h2>
	</div>

	<div class="material-show__content">
		<div class="material-show__row">
			<span>Наименование:</span>
			<label>
				<input type="text" name="catalog-name" value="{{ $catalog->name }}" autocomplete="off" required>
			</label>
		</div>

		<div class="material-show__row">
			<span>Родительский каталог:</span>
			<label>
				<div class="select material-select">
					<input class="select__value" name="parent_id" type="hidden" value="{{ $catalog->parent_id ?? '' }}">

					<button class="material-select__select-button select__button select-button" type="button"
							aria-haspopup="listbox" aria-expanded="false">
						<span class="material-select__select-value select__button-text">
							{{ $parents->firstWhere('id', $catalog->parent_id)?->name ?? '— Нет —' }}
						</span>
						<span class="material-select__select-arrow" aria-hidden="true"></span>
					</button>

					<div class="select__dropdown material-select__select-list _collapse" role="listbox">
						<button class="material-select__select-option select__item" type="button" role="option"
								data-value="">— Нет —</button>

						@foreach ($parents as $parent)
							<button class="material-select__select-option select__item" type="button" role="option"
									data-value="{{ $parent->id }}">{{ $parent->name }}</button>
						@endforeach
					</div>
				</div>
			</label>
		</div>

		<div class="material-show__row">
			<span>Сортировка:</span>
			<label>
				<input type="number" name="sort_order" min="0" step="1" value="{{ $catalog->sort_order }}">
			</label>
		</div>

		<div class="material-show__row">
			<span>Статус:</span>

			<label>
				<input type="checkbox" name="is_active" {{ $catalog->is_active ? 'checked' : '' }}>
				Активен
			</label>
		</div>
	</div>

	<div class="material-show__actions">
		<button class="button button--primary" type="button" data-action="update">Сохранить</button>
	</div>
</div>
