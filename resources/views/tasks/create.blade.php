@extends('layouts.app')

@section('title', 'Новая производственная задача')

@section('content')
	<form class="issue-order" method="POST" action="{{ route('tasks.store') }}" data-order-form>
		<div class="issue-order__header">
			<h1 class="main-content__title">Новая производственная задача</h1>
		</div>
		@csrf

		@include('tasks._form', [
				'submitLabel' => 'Создать задачу',
				'cancelUrl' => route('tasks.index'),
		])
	</form>
@endsection
