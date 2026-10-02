@extends('layouts.app')

@section('title', 'Создание технологической линии')

@section('content')
	<div class="main-content__content" data-production-operation-form>
		<div class="main-content__header">
			<h1 class="main-content__title">Создание технологической линии</h1>
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
						<label class="form-field__label" for="name">Название линии</label>
						<input class="form-field__input" id="name" name="name" type="text" value="{{ old('name') }}" required>
					</div>

					<div class="form-field">
						<label class="form-field__label" for="code">Код линии</label>
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
							<span>Линия активна</span>
						</label>
					</div>

					<div class="form-field form-field--checkbox">
						<label class="form-field__label">
							<input type="hidden" name="is_cutting" value="0">
							<input type="checkbox" name="is_cutting" value="1" @checked(old('is_cutting', false))>
							<span>Режим резки</span>
						</label>
						<p class="operation-form__section-description">
							На входе один материал, на выходе — тот же материал другого формата.
						</p>
					</div>
				</div>
			</div>

			<div class="operation-form__section">
				<div class="operation-form__section-header">
					<div>
						<h2 class="operation-form__section-title">Выходные товары</h2>
						<p class="operation-form__section-description">
							Технологические линии, на которые поступает продукция этой линии.
							Их назначенные материалы доступны на выходе шаблонов производства.
						</p>
					</div>
				</div>

				<div class="operation-form__fields">
					@if ($operationsList->isEmpty())
						<p class="operation-form__section-description">Нет других активных технологических линий.</p>
					@else
						@foreach ($operationsList as $outputOperation)
							<div class="form-field form-field--checkbox">
								<label class="form-field__label">
									<input type="checkbox" name="output_operations[]" value="{{ $outputOperation->id }}"
											{{ in_array($outputOperation->id, old('output_operations', []), false) ? 'checked' : '' }}>
									<span>{{ $outputOperation->name }}</span>
								</label>
							</div>
						@endforeach
					@endif
				</div>
			</div>


			<div class="operation-form__actions">
				<a class="button button--secondary" href="{{ route('production.operations.index') }}">
					<span>Отмена</span>
				</a>

				<button class="button" type="submit">
					<span>Сохранить линию</span>
				</button>
			</div>
		</form>
	</div>
@endsection