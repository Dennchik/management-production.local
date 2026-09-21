@extends('layouts.app')

@section('title', 'Редактирование задачи №' . $task->id)

@section('content')
	<div class="main-content__content" data-production-lines-page>
		<div class="main-content__header">
			<h1 class="main-content__title">Редактирование задачи №{{ $task->id }}</h1>
		</div>
		<form class="production-task production-task--edit" method="POST" action="{{ route('tasks.update', $task) }}"
				data-tasks-form data-production-lines-form>
			@csrf
			@method('PUT')

			@include('partials.message')

			@include('tasks._form', [
					'task' => $task,
					'submitLabel' => 'Сохранить изменения',
					'cancelUrl' => route('tasks.show', $task),
			])
		</form>
	</div>
@endsection
