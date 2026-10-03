@extends('layouts.app')

@section('title', 'Пользователи')

@section('content')
	<div class="main-content__content materials">
		<div class="main-content__header">
			<h1 class="main-content__title">Пользователи</h1>

			<div class="catalogs__header-actions">
				<a class="button button--secondary" href="{{ route('roles.index') }}">
					<span>Роли и права</span>
				</a>

				<a class="button button--primary materials__create" href="{{ route('users.create') }}">
					<i class="icon icon-plus" aria-hidden="true"></i>
					<span>Создать</span>
				</a>
			</div>
		</div>

		@include('partials.message')

			<div class="materials__content">
				<div class="materials__table">
					<table>
						<thead>
						<tr>
							<th>№</th>
							<th>Логин</th>
							<th>ФИО</th>
							<th>Имя в системе</th>
							<th>Роль</th>
							<th></th>
						</tr>
						</thead>
						<tbody>
						@foreach ($users as $user)
							<tr>
								<td>{{ $user->id }}</td>
								<td>{{ $user->login }}</td>
								<td>{{ $user->full_name ?? '—' }}</td>
								<td>{{ $user->name }}</td>
								<td>{{ $user->role?->name ?? '—' }}</td>
								<td class="materials__actions-icons">
									<a href="{{ route('users.edit', $user) }}" title="Редактировать">
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
