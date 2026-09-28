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

					<div class="form-field form-field--checkbox">
						<label class="form-field__label">
							<input type="hidden" name="is_cutting" value="0">
							<input type="checkbox" name="is_cutting" value="1" @checked($operation->is_cutting)>
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
					@php
						$selectedOutputIds = old('output_operations', $operation->outputOperations->pluck('id')->all());
					@endphp

					@if ($operationsList->isEmpty())
						<p class="operation-form__section-description">Нет других активных технологических линий.</p>
					@else
						@foreach ($operationsList as $outputOperation)
							<div class="form-field form-field--checkbox">
								<label class="form-field__label">
									<input type="checkbox" name="output_operations[]" value="{{ $outputOperation->id }}"
											{{ in_array($outputOperation->id, $selectedOutputIds, false) ? 'checked' : '' }}>
									<span>{{ $outputOperation->name }}</span>
								</label>
							</div>
						@endforeach
					@endif
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
