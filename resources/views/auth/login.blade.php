@extends('layouts.app')

@section('title', 'Вход')

@section('content')
	<div class="main-content__content">
		<div class="main-content__header">
			<h1 class="main-content__title">Вход в систему</h1>
		</div>

		<form class="issue-order" method="POST" action="{{ route('login.attempt') }}" style="max-width: 480px;">
			@csrf

			<div class="issue-order__body">
				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="name">Имя пользователя</label>
						<input class="issue-order__input" id="name" name="name" type="text"
								value="{{ old('name') }}" required autofocus>

						@error('name')
							<p style="color: #b00; margin: 0.25rem 0 0;">{{ $message }}</p>
						@enderror
					</fieldset>
				</div>

				<div class="issue-order__line">
					<fieldset class="issue-order__field" style="width: 100%;">
						<label class="issue-order__label" for="password">Пароль</label>
						<input class="issue-order__input" id="password" name="password" type="password" required>
					</fieldset>
				</div>

				<div class="issue-order__actions">
					<button class="main-content__button button" type="submit">
						<span>Войти</span>
					</button>
				</div>
			</div>
		</form>
	</div>
@endsection
