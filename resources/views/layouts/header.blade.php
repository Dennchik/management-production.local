<header class="header">
	<div class="header__body container">
		<div class="header__inner">
			<button class="header__menu-toggle"
					type="button"
					aria-label="Открыть меню"
					aria-controls="sidebar"
					aria-expanded="false">
				Меню
			</button>
			<a class="header__logo" href="{{ route('dashboard') }}">
				<img src="/img/icons/production-industria.svg" alt="">
				<span>Производственный учет</span>
			</a>
			<div class="header__actions">
				<button class="header__theme-toggle"
						type="button"
						aria-label="Переключить тему"
						aria-pressed="false">
					<span class="header__theme-icon" aria-hidden="true"></span>
				</button>
				@auth
					<div class="header__user"> {{ auth()->user()->name }}
						<form method="POST" action="{{ route('logout') }}" style="display:inline; margin-left: .5rem;">
							@csrf
							<button type="submit" style="background:none;border:none;cursor:pointer;text-decoration:underline;color:inherit;">
								Выйти
							</button>
						</form>
					</div>
				@endauth
			</div>
		</div>
	</div>
</header>