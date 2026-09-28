@extends('layouts.app')

@section('title', 'Создание производственной линии — ' . $operation->name)

@section('content')
	<div class="main-content__content" data-production-lines-page @if ($isCutting) data-cutting-mode="1" @endif>
		<div class="main-content__header">
			<h1 class="main-content__title">Создание производственной линии</h1>
		</div>

		<nav class="materials__breadcrumbs">
			<a href="{{ route('production.operations.index') }}">Технологические линии</a>
			<span> / </span>
			<a href="{{ route('production.lines.show', $operation) }}">{{ $operation->name }}</a>
			<span> / </span>
			<span class="materials__breadcrumbs-current">Создание</span>
		</nav>

		<form method="POST" action="{{ route('production.lines.store', $operation) }}"
				data-production-lines-form>
			@csrf

			<div class="operation-form__section">
				<div class="operation-form__section-header">
					<h2 class="operation-form__section-title">Основная информация</h2>
				</div>

				<div class="operation-form__fields">
					<div class="form-field">
						<label class="form-field__label" for="production-line-name">Название линии</label>
						<input class="form-field__input" id="production-line-name" name="name" type="text"
								value="{{ old('name') }}" required>
					</div>
				</div>
			</div>

			@include('production.operations._line-materials-table', [
					'title' => 'Материалы (вход)',
					'description' => $isCutting
							? 'Один материал: на вход линии резки поступает единственный материал.'
							: 'Материалы с разрешённой операцией «' . $operation->name . '».',
					'inputName' => 'materials',
					'materials' => $materials,
					'emptyText' => 'Нет материалов с разрешённой операцией «' . $operation->name . '».',
					'allowAdd' => !$isCutting,
			])

			<div data-autofill-line-name>
				@include('production.operations._line-materials-table', [
						'title' => 'Материалы (выход)',
						'description' => $isCutting
								? 'Тот же материал в других форматах: выбор по материалу входа.'
								: 'Материалы, назначенные выбранным выходным линиям технологической линии «' . $operation->name . '».',
						'inputName' => 'output_materials',
						'materials' => $outputMaterials,
						'emptyText' => 'Нет назначенных материалов у выбранных выходных линий.',
						'allowAdd' => $isCutting,
						'allowAddFormat' => $isCutting,
				])
			</div>

			<div class="operation-form__actions">
				<a class="button button--secondary" href="{{ route('production.lines.show', $operation) }}">
					Отмена
				</a>

				<button class="button" type="submit">
					Сохранить линию
				</button>
			</div>
		</form>
	</div>
@endsection
