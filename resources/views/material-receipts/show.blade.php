@extends('layouts.app')

@section('title', 'Приходный ордер')

@section('content')

	@include('material-receipts._content', [
		 'receipt' => $receipt,
	])

@endsection