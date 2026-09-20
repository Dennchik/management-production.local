@extends('layouts.app')

@section('title', 'Новая производственная задача')

@section('content')
	<div class="main-content__content" data-production-lines-page>
		<form class="issue-order" method="POST" action="{{ route('tasks.store') }}"
				data-tasks-form data-production-lines-form>
			<div class="issue-order__header">
				<h1 class="main-content__title">Новая производственная задача</h1>
			</div>
			@csrf

			@include('partials.message')

			@include('tasks._form', [
					'submitLabel' => 'Создать задачу',
					'cancelUrl' => route('tasks.index'),
			])
		</form>
	</div>
@endsection
