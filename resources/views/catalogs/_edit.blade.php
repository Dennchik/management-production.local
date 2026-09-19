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
				<select class="catalogs__parent-select" name="parent_id">
					<option value="">— Нет —</option>
					@foreach ($parents as $parent)
						<option value="{{ $parent->id }}" {{ $catalog->parent_id === $parent->id ? 'selected' : '' }}>
							{{ $parent->name }}
						</option>
					@endforeach
				</select>
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
