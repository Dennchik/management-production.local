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

		<form class="issue-order" method="POST" data-users-form
				action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}"
				style="max-width: 720px;">
			@csrf

			<div class="issue-order__body">
				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="login">Логин</label>
						<input class="issue-order__input" id="login" name="login" type="text"
								value="{{ old('login', $user->login) }}" required>
					</fieldset>
				</div>

				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="full_name">ФИО</label>
						<input class="issue-order__input" id="full_name" name="full_name" type="text"
								value="{{ old('full_name', $user->full_name) }}" required>
					</fieldset>
				</div>

				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="display_name">Имя для отображения в системе</label>
						<input class="issue-order__input" id="display_name" name="display_name" type="text"
								value="{{ old('display_name', $user->display_name) }}"
								placeholder="Иначе — ФИО или логин">
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

						<div class="select material-select">
							<input class="select__value" id="role_id" name="role_id" type="hidden"
									value="{{ old('role_id', $user->role_id) }}">

							<button class="material-select__select-button select__button select-button" type="button"
									aria-haspopup="listbox" aria-expanded="false">
								<span class="material-select__select-value select__button-text">
									{{ $roles->firstWhere('id', (int) old('role_id', $user->role_id))?->name ?? '— Нет роли —' }}
								</span>
								<span class="material-select__select-arrow" aria-hidden="true"></span>
							</button>

							<div class="select__dropdown material-select__select-list _collapse" role="listbox">
								<button class="material-select__select-option select__item" type="button" role="option"
										data-value="">— Нет роли —</button>

								@foreach ($roles as $role)
									<button class="material-select__select-option select__item" type="button" role="option"
											data-value="{{ $role->id }}">{{ $role->name }}</button>
								@endforeach
							</div>
						</div>
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
