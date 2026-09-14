@extends('layouts.app')

@section('title', $user->exists ? 'Редактирование пользователя' : 'Новый пользователь')

@section('content')
	<div class="main-content__content">
		<div class="main-content__header">
			<h1 class="main-content__title">
				{{ $user->exists ? 'Редактирование пользователя' : 'Новый пользователь' }}
			</h1>
		</div>

		@include('partials.message')

		<form class="issue-order" method="POST"
				action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}"
				style="max-width: 560px;">
			@csrf

			<div class="issue-order__body">
				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="name">Имя пользователя</label>
						<input class="issue-order__input" id="name" name="name" type="text"
								value="{{ old('name', $user->name) }}" required>
					</fieldset>
				</div>

				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="password">
							Пароль {{ $user->exists ? '(оставьте пустым, чтобы не менять)' : '' }}
						</label>
						<input class="issue-order__input" id="password" name="password" type="password">
					</fieldset>
				</div>

				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="role_id">Роль</label>
						<select class="catalogs__parent-select" id="role_id" name="role_id">
							<option value="">— Нет роли —</option>
							@foreach ($roles as $role)
								<option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
									{{ $role->name }}
								</option>
							@endforeach
						</select>
					</fieldset>
				</div>

				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="machine_id">Станок</label>
						<select class="catalogs__parent-select" id="machine_id" name="machine_id">
							<option value="">— Без станка —</option>
							@foreach ($machines as $machine)
								<option value="{{ $machine->id }}" {{ old('machine_id', $user->machine_id) == $machine->id ? 'selected' : '' }}>
									{{ $machine->name }}
								</option>
							@endforeach
						</select>
					</fieldset>
				</div>

				<div class="issue-order__actions">
					<button class="main-content__button button" type="submit">
						<span>Сохранить</span>
					</button>

					<a class="button button--secondary" href="{{ route('users.index') }}">
						<span>Отмена</span>
					</a>
				</div>
			</div>
		</form>
	</div>
@endsection
