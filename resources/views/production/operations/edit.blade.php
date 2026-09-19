@extends('layouts.app')

@section('title', 'Редактирование технологической линии')

@section('content')
	<div class="main-content__content" data-production-operation-form>
		<div class="main-content__header">
			<h1 class="main-content__title">Редактирование технологической линии</h1>
		</div>

		<form class="operation-form" method="POST"
				action="{{ route('production.operations.update', $operation) }}"
				data-production-operation-update-form>
			@csrf
			@method('PUT')

			<div class="operation-form__section">
				<div class="operation-form__section-header">
					<h2 class="operation-form__section-title">Основная информация</h2>
				</div>

				<div class="operation-form__fields">
					<div class="form-field">
						<label class="form-field__label" for="edit-name">Название линии</label>
						<input class="form-field__input" id="edit-name" name="name" type="text"
								value="{{ $operation->name }}" required>
					</div>

					<div class="form-field">
						<label class="form-field__label" for="edit-code">Код линии</label>
						<input class="form-field__input" id="edit-code" name="code" type="text"
								value="{{ $operation->code }}" required>
					</div>

					<div class="form-field">
						<label class="form-field__label" for="edit-description">Описание</label>
						<textarea class="form-field__textarea" id="edit-description" name="description"
								rows="4">{{ $operation->description }}</textarea>
					</div>

					<div class="form-field form-field--checkbox">
						<label class="form-field__label">
							<input type="hidden" name="is_active" value="0">
							<input type="checkbox" name="is_active" value="1" @checked($operation->is_active)>
							<span>Линия активна</span>
						</label>
					</div>
				</div>
			</div>

			<div class="operation-form__actions">
				<a class="button button--secondary" href="{{ route('production.operations.index') }}">
					Отмена
				</a>

				<button class="button" type="submit">
					Сохранить изменения
				</button>
			</div>
		</form>
	</div>
@endsection
