@extends('layouts.app')

@section('title', 'Мой профиль')

@section('content')
	<div class="main-content__content">
		<div class="main-content__header">
			<h1 class="main-content__title">Мой профиль</h1>
		</div>

		@include('partials.message')

		<div class="material">
			<div class="material__content">

				{{-- Карточка пользователя --}}

				<section class="material__section">
					<div class="material__column-roll">
						<h2 class="material__section-title">Карточка пользователя</h2>

						<div class="material__rows show-roll">
							<div class="material__row">
								<div class="material__label">Логин</div>
								<div class="material__value">{{ $user->login }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">ФИО</div>
								<div class="material__value">{{ $user->full_name ?? '—' }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Имя в системе</div>
								<div class="material__value">{{ $user->name }}</div>
							</div>

							<div class="material__row">
								<div class="material__label">Роль</div>
								<div class="material__value">{{ $user->role?->name ?? '—' }}</div>
							</div>
						</div>
					</div>
				</section>

				{{-- Смена пароля --}}

				<section class="material__section">
					<div class="material__column-roll">
						<h2 class="material__section-title">Смена пароля</h2>

						<form class="issue-order" method="POST" action="{{ route('profile.password') }}"
								style="max-width: 560px;">
							@csrf

							<div class="issue-order__body">
								<div class="issue-order__line">
									<fieldset class="issue-order__field" style="width: 100%;">
										<label class="issue-order__label" for="current_password">Текущий пароль</label>
										<input class="issue-order__input" id="current_password" name="current_password"
												type="password" required>
									</fieldset>
								</div>

								<div class="issue-order__line">
									<fieldset class="issue-order__field" style="width: 100%;">
										<label class="issue-order__label" for="password">Новый пароль</label>
										<input class="issue-order__input" id="password" name="password"
												type="password" required minlength="3">
									</fieldset>
								</div>

								<div class="issue-order__line">
									<fieldset class="issue-order__field" style="width: 100%;">
										<label class="issue-order__label" for="password_confirmation">Повторите новый пароль</label>
										<input class="issue-order__input" id="password_confirmation" name="password_confirmation"
												type="password" required minlength="3">
									</fieldset>
								</div>

								<div class="issue-order__actions">
									<button class="main-content__button button" type="submit">
										<span>Изменить пароль</span>
									</button>
								</div>
							</div>
						</form>
					</div>
				</section>
			</div>
		</div>
	</div>
@endsection
