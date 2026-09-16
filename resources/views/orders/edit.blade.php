@extends('layouts.app')

@section('title', 'Редактирование заказа №' . $order->id)

@section('content')
	<form class="issue-order" method="POST" action="{{ route('orders.update', $order) }}" data-order-form>
		<div class="issue-order__header">
			<h1 class="main-content__title">Редактирование заказа №{{ $order->id }}</h1>
		</div>
		@csrf
		@method('PUT')

		@include('partials.message')

		@include('orders._form', [
				'order' => $order,
				'submitLabel' => 'Сохранить изменения',
				'cancelUrl' => route('orders.show', $order),
		])
	</form>
@endsection
