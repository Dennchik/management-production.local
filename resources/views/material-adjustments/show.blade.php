@extends('layouts.app')

@section('title', 'Ордер корректировки')

@section('content')

	@include('material-adjustments._content', [
		'adjustment' => $adjustment,
	])

@endsection
