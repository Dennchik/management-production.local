@extends('layouts.app')

@section('title', 'Роли и права')

@section('content')
	<div class="main-content__content materials">
		<div class="main-content__header">
			<h1 class="main-content__title">Роли и права</h1>

			<a class="button button--secondary" href="{{ route('users.index') }}">
				<span>Пользователи</span>
			</a>
		</div>

		@include('partials.message')

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>Роль</th>
						<th>Прав</th>
						<th></th>
					</tr>
					</thead>
					<tbody>
					@foreach ($roles as $role)
						<tr>
							<td>{{ $role->name }}</td>
							<td>{{ $role->permissions->count() }}</td>
							<td class="materials__actions-icons">
								<a href="{{ route('roles.edit', $role) }}" title="Настроить права">
									<i class="icon icon-edit" aria-hidden="true"></i>
								</a>
							</td>
						</tr>
					@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
