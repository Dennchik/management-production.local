@extends('layouts.app')

@section('title', 'Расходный ордер')

@section('content')

	@include('material-issues._content', [
		'issue' => $issue,
	])

@endsection