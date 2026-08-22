<div class="sidebar">
	<div class="sidebar__body">
		<nav class="sidebar__nav">
			<a class="sidebar__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
					href="{{ route('dashboard') }}">
				<i class="icon-home-com icon"></i>
				<span>Главная</span>
			</a>

			<div class="sidebar__section">
				<div class="sidebar__section-title">
					<i class="icon-crane icon"></i>
					<span>Склад</span>
				</div>

				<div class="sidebar__submenu">
					<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('material-receipts.*') ? 'is-active' : '' }}"
							href="{{ route('material-receipts.index') }}">
						<i class="icon-indent-increase icon"></i>
						<span>Приходный ордер</span>
					</a>

					<a class="sidebar__link sidebar__link--submenu"
							href="{{ route('material-issues.index') }}">
						<i class="icon-indent-decrease icon"></i>
						<span>Расходный ордер</span>
					</a>

					<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('warehouse.*') ? 'is-active' : '' }}"
							href="{{ route('warehouse.index') }}">
						<i class="icon-warehouse icon"></i>
						<span>Материалы</span>
					</a>

					<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('material-movements.*') ? 'is-active' : '' }}"
							href="{{ route('material-movements.index') }}">
						<i class="icon-recycle-arrows icon"></i>
						<span>Движение материалов</span>
					</a>
				</div>
			</div>

			<a class="sidebar__link" href="#">
				<i class="icon-factory icon"></i>
				<span>Ламинация</span>
			</a>

			<a class="sidebar__link" href="#">
				<i class="icon-calculator icon"></i>
				<span>Праймирование</span>
			</a>

			<a class="sidebar__link" href="#">
				<i class="icon-cut icon"></i>
				<span>Резка</span>
			</a>

			<a class="sidebar__link" href="#">
				<i class="icon-print icon"></i>
				<span>Печать</span>
			</a>

			<a class="sidebar__link" href="#">
				<i class="icon-catalog icon"></i>
				<span>Справочники</span>
			</a>

			<a class="sidebar__link" href="#">
				<i class="icon-archive icon"></i>
				<span>Отчёты</span>
			</a>

			<a class="sidebar__link" href="#">
				<i class="icon-users-group icon"></i>
				<span>Пользователи и права</span>
			</a>

			<a class="sidebar__link" href="#">
				<i class="icon-order-list icon"></i>
				<span>Журнал операций</span>
			</a>
		</nav>
	</div>
</div>