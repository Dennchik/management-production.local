@extends('layouts.app')

@section('title', 'Новый заказ №' . $orderNumber)

@section('content')
	<form class="issue-order" method="POST" action="{{ route('orders.store') }}" data-order-form>
		<div class="issue-order__header">
			<h1 class="main-content__title">Новый заказ №{{ $orderNumber }}</h1>
		</div>
		@csrf

		@include('orders._form', [
				'submitLabel' => 'Создать заказ',
				'cancelUrl' => route('orders.index'),
		])
	</form>
@endsection
