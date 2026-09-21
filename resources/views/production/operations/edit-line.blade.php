@extends('layouts.app')

@section('title', 'Редактирование производственной линии — ' . $productionLine->name)

@section('content')
	<div class="main-content__content" data-production-lines-page>
		<div class="main-content__header">
			<h1 class="main-content__title">Редактирование производственной линии</h1>
		</div>

		<nav class="materials__breadcrumbs">
			<a href="{{ route('production.operations.index') }}">Технологические линии</a>
			<span> / </span>
			<a href="{{ route('production.lines.show', $operation) }}">{{ $operation->name }}</a>
			<span> / </span>
			<a href="{{ route('production.lines.line.show', [$operation, $productionLine]) }}">{{ $productionLine->name }}</a>
			<span> / </span>
			<span class="materials__breadcrumbs-current">Редактирование</span>
		</nav>

		<form method="POST" action="{{ route('production.lines.line.update', [$operation, $productionLine]) }}"
				data-production-lines-form>
			@csrf
			@method('PUT')

			<div class="operation-form__section">
				<div class="operation-form__section-header">
					<h2 class="operation-form__section-title">Основная информация</h2>
				</div>

				<div class="operation-form__fields">
					<div class="form-field">
						<label class="form-field__label" for="production-line-name">Название линии</label>
						<input class="form-field__input" id="production-line-name" name="name" type="text"
								value="{{ $productionLine->name }}" required>
					</div>
				</div>
			</div>

			@include('production.operations._line-materials-table', [
					'title' => 'Материалы (вход)',
					'description' => 'Материалы с разрешённой операцией «' . $operation->name . '».',
					'inputName' => 'materials',
					'materials' => $materials,
					'rows' => $productionLine->inputMaterials,
					'emptyText' => 'Нет материалов с разрешённой операцией «' . $operation->name . '».',
			])

			@include('production.operations._line-materials-table', [
					'title' => 'Материалы (выход)',
					'description' => 'Материалы со справочника с типом «Продукция».',
					'inputName' => 'output_materials',
					'materials' => $outputMaterials,
					'rows' => $productionLine->outputMaterials,
					'emptyText' => 'В справочнике нет активных материалов с типом «Продукция».',
					'allowAdd' => false,
			])

			<div class="operation-form__actions">
				<a class="button button--secondary" href="{{ route('production.lines.line.show', [$operation, $productionLine]) }}">
					Отмена
				</a>

				<button class="button" type="submit">
					Сохранить линию
				</button>
			</div>
		</form>
	</div>
@endsection
