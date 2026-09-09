@extends('layouts.app')

@section('title', 'Создание производственной операции')

@section('content')
	<div class="main-content__content" data-production-operation-form>
		<div class="main-content__header">
			<h1 class="main-content__title">Создание производственной операции</h1>
		</div>

		<form class="operation-form"
				method="POST"
				action="{{ route('production.operations.store') }}"
				data-production-operation-create-form>
			@csrf

			<div class="operation-form__section">
				<div class="operation-form__section-header">
					<h2 class="operation-form__section-title">Основная информация</h2>
				</div>

				<div class="operation-form__fields">
					<div class="form-field">
						<label class="form-field__label" for="name">Название операции</label>
						<input class="form-field__input" id="name" name="name" type="text" value="{{ old('name') }}" required>
					</div>

					<div class="form-field">
						<label class="form-field__label" for="code">Код операции</label>
						<input class="form-field__input" id="code" name="code" type="text" value="{{ old('code') }}" required>
					</div>

					<div class="form-field">
						<label class="form-field__label" for="description">Описание</label>
						<textarea class="form-field__textarea" id="description" name="description"
								rows="4">{{ old('description') }}</textarea>
					</div>

					<div class="form-field form-field--checkbox">
						<label class="form-field__label">
							<input type="hidden" name="is_active" value="0">
							<input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
							<span>Операция активна</span>
						</label>
					</div>
				</div>
			</div>

			<div class="operation-form__section">
				<div class="operation-form__section-header">
					<div>
						<h2 class="operation-form__section-title">Входные материалы</h2>
						<p class="operation-form__section-description">
							Материалы, которые используются при выполнении операции.
						</p>
					</div>

					<button class="button button--secondary" type="button" data-production-operation-add-input>
						Добавить вход
					</button>
				</div>

				<div class="operation-form__components" data-production-operation-inputs>
					<div class="operation-component" data-production-operation-input data-component-index="0">
						<div class="form-field">
							<label class="form-field__label">Материал</label>

							<div data-select>
								<div class="select material-select operation-form">
									<input class="select__value"
											id="input_material_id_0"
											name="inputs[0][material_id]"
											type="hidden"
											value="">

									<button class="material-select__select-button select__button select-button"
											id="input_material_select_0"
											type="button"
											aria-haspopup="listbox"
											aria-expanded="false">
										<span class="material-select__select-value select__button-text">Выберите материал</span>
										<span class="material-select__select-arrow" aria-hidden="true"></span>
									</button>

									<div class="select__dropdown material-select__select-list _collapse" role="listbox">
										<div class="material-select__select-search">
											<input class="material-select__select-search-input select__search"
													id="input_material_search_0"
													type="search"
													placeholder="Поиск материала..."
													autocomplete="off">

											<button class="material-select__select-search-clear select__search-clear" type="button"
													aria-label="Очистить поиск" hidden>
												<i class="icon icon-close" aria-hidden="true"></i>
											</button>
										</div>

										@foreach ($materials as $material)
											<button class="material-select__select-option select__item" type="button" role="option"
													data-value="{{ $material->id }}" aria-selected="false">
												<span>
													{{ preg_replace('/\s*гр\.?\s*$/ui', '', $material->name) }}
													@if ($material->grammage)
														| {{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }}
														гр
													@endif
													@if ($material->thickness)
														| {{ $material->thickness }} мкм
													@endif
													@if ($material->format)
														| {{ $material->format }}
													@endif
												</span>
											</button>
										@endforeach

										<div class="material-select__select-empty select__empty" hidden>
											Ничего не найдено
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="form-field">
							<label class="form-field__label" for="input-quantity-0">Количество</label>
							<input class="form-field__input" id="input-quantity-0" name="inputs[0][quantity]" type="number"
									min="0" step="0.001">
						</div>

						<div class="form-field">
							<label class="form-field__label" for="input-unit-0">Единица</label>
							<input class="form-field__input"
									id="input-unit-0"
									name="inputs[0][unit]"
									type="text"
									value="kg"
									required>
						</div>

						<div class="form-field form-field--checkbox">
							<label class="form-field__label">
								<input type="hidden" name="inputs[0][is_required]" value="0">
								<input type="checkbox" name="inputs[0][is_required]" value="1" checked>
								<span>Обязательный</span>
							</label>
						</div>

						<div class="form-field">
							<label class="form-field__label" for="input-comment-0">Комментарий</label>
							<input class="form-field__input" id="input-comment-0" name="inputs[0][comment]" type="text">
						</div>

						<button class="button button--danger" type="button" data-production-operation-remove-input>
							Удалить
						</button>
					</div>
				</div>
			</div>

			<div class="operation-form__section">
				<div class="operation-form__section-header">
					<div>
						<h2 class="operation-form__section-title">Выходные материалы</h2>
						<p class="operation-form__section-description">
							Материалы или полуфабрикаты, которые получаются в результате операции.
						</p>
					</div>

					<button class="button button--secondary" type="button" data-production-operation-add-output>
						Добавить выход
					</button>
				</div>

				<div class="operation-form__components" data-production-operation-outputs>
					<div class="operation-component" data-production-operation-output data-component-index="0">
						<div class="form-field">
							<label class="form-field__label">Материал</label>

							<div data-select>
								<div class="select material-select operation-form">
									<input class="select__value"
											id="output_material_id_0"
											name="outputs[0][material_id]"
											type="hidden"
											value="">

									<button class="material-select__select-button select__button select-button"
											id="output_material_select_0"
											type="button"
											aria-haspopup="listbox"
											aria-expanded="false">
										<span class="material-select__select-value select__button-text">Выберите материал</span>
										<span class="material-select__select-arrow" aria-hidden="true"></span>
									</button>

									<div class="select__dropdown material-select__select-list _collapse" role="listbox">
										<div class="material-select__select-search">
											<input class="material-select__select-search-input select__search"
													id="output_material_search_0"
													type="search"
													placeholder="Поиск материала..."
													autocomplete="off">

											<button class="material-select__select-search-clear select__search-clear" type="button"
													aria-label="Очистить поиск" hidden>
												<i class="icon icon-close" aria-hidden="true"></i>
											</button>
										</div>

										@foreach ($materials as $material)
											<button class="material-select__select-option select__item" type="button" role="option"
													data-value="{{ $material->id }}" aria-selected="false">
												<span>
													{{ preg_replace('/\s*гр\.?\s*$/ui', '', $material->name) }}
													@if ($material->grammage)
														| {{ rtrim(rtrim(number_format($material->grammage, 2, '.', ''), '0'), '.') }}
														гр
													@endif
													@if ($material->thickness)
														| {{ $material->thickness }} мкм
													@endif
													@if ($material->format)
														| {{ $material->format }}
													@endif
												</span>
											</button>
										@endforeach

										<div class="material-select__select-empty select__empty" hidden>
											Ничего не найдено
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="form-field">
							<label class="form-field__label" for="output-quantity-0">Количество</label>
							<input class="form-field__input"
									id="output-quantity-0"
									name="outputs[0][quantity]"
									type="number"
									min="0"
									step="0.001">
						</div>

						<div class="form-field">
							<label class="form-field__label" for="output-unit-0">Единица</label>
							<input class="form-field__input"
									id="output-unit-0"
									name="outputs[0][unit]"
									type="text"
									value="kg"
									required>
						</div>

						<div class="form-field form-field--checkbox">
							<label class="form-field__label">
								<input type="hidden" name="outputs[0][is_required]" value="0">
								<input type="checkbox" name="outputs[0][is_required]" value="1">
								<span>Обязательный</span>
							</label>
						</div>

						<div class="form-field">
							<label class="form-field__label" for="output-comment-0">Комментарий</label>
							<input class="form-field__input" id="output-comment-0" name="outputs[0][comment]" type="text">
						</div>

						<button class="button button--danger" type="button" data-production-operation-remove-output>
							Удалить
						</button>
					</div>
				</div>
			</div>

			<div class="operation-form__actions">
				<a class="button button--secondary" href="{{ route('production.operations.index') }}">
					Отмена
				</a>

				<button class="button" type="submit">
					Сохранить операцию
				</button>
			</div>
		</form>
	</div>
@endsection