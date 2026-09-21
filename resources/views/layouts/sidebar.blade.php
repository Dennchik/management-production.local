<div class="sidebar">
	<div class="sidebar__body">
		<nav class="sidebar__nav">
			@php $user = auth()->user(); @endphp

			<a class="sidebar__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}"
					href="{{ route('dashboard') }}">
				<i class="icon-home-com icon"></i>
				<span>Главная</span>
			</a>

			@if ($user?->may('warehouse'))
				<div class="sidebar__section">
					<div class="sidebar__section-title">
						<i class="icon-crane icon"></i>
						<span>Склад</span>
					</div>

					<div class="sidebar__submenu">
						@if ($user->may('warehouse', 'create'))
							<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('material-receipts.*') ? 'is-active' : '' }}"
									href="{{ route('material-receipts.index') }}">
								<i class="icon-indent-increase icon"></i>
								<span>Приходный ордер</span>
							</a>

							<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('material-issues.*') ? 'is-active' : '' }}"
									href="{{ route('material-issues.index') }}">
								<i class="icon-indent-decrease icon"></i>
								<span>Расходный ордер</span>
							</a>
						@endif

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

						@if ($user->may('rolls'))
							<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('material-rolls.*') ? 'is-active' : '' }}"
									href="{{ route('material-rolls.index') }}">
								<i class="icon-roll icon"></i>
								<span>Рулоны</span>
							</a>
						@endif
					</div>
				</div>
			@endif

			@if ($user?->may('tasks'))
				<div class="sidebar__section">
					<div class="sidebar__section-title">
						<i class="icon-factory icon"></i>
						<span>Задачи</span>
					</div>

					<div class="sidebar__submenu">
						<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('tasks.*') ? 'is-active' : '' }}"
								href="{{ route('tasks.index') }}">
							<i class="icon-indent-increase icon"></i>
							<span>Производственные задачи</span>
						</a>
					</div>
				</div>
			@endif

			@if ($user?->may('operations'))
				<div class="sidebar__section" data-production-operations>
					<a class="sidebar__section-title {{ request()->routeIs('production.operations.*') ? 'is-active' : '' }}"
							href="{{ route('production.operations.index') }}">
						<i class="icon-factory icon"></i>
						<span>Производственные линии (шаблоны)</span>
					</a>

					<div class="sidebar__submenu" data-production-operations-list>
						@foreach ($productionOperations as $operation)
							<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('production.lines.show') && request()->route('productionOperation')?->id === $operation->id ? 'is-active' : '' }}"
									href="{{ route('production.lines.show', $operation) }}"
									data-production-operation-id="{{ $operation->id }}">
								<i class="icon-recycle-arrows icon"></i>
								<span>{{ $operation->name }}</span>
							</a>
						@endforeach
					</div>
				</div>
			@endif

			@if ($user?->may('materials') || $user?->may('operations'))
				<div class="sidebar__section">
					<div class="sidebar__section-title">
						<i class="icon-catalog icon"></i>
						<span>Справочники</span>
					</div>

					<div class="sidebar__submenu">
						@if ($user->may('materials'))
							<a class="sidebar__link sidebar__link--submenu {{ request()->routeIs('materials.*') ? 'is-active' : '' }}"
									href="{{ route('materials.index') }}">
								<i class="icon-warehouse icon"></i>
								<span>Материалы</span>
							</a>
						@endif

						<a class="sidebar__link sidebar__link--submenu" href="#">
							<i class="icon-recycle-arrows icon"></i>
							<span>Технологические маршруты</span>
						</a>
					</div>
				</div>
			@endif

			@if ($user?->may('reports'))
				<a class="sidebar__link" href="#">
					<i class="icon-archive icon"></i>
					<span>Отчёты</span>
				</a>
			@endif

			@if ($user?->may('users'))
				<a class="sidebar__link {{ request()->routeIs('users.*') || request()->routeIs('roles.*') ? 'is-active' : '' }}"
						href="{{ route('users.index') }}">
					<i class="icon-users-group icon"></i>
					<span>Пользователи и права</span>
				</a>
			@endif

			<a class="sidebar__link" href="#">
				<i class="icon-order-list icon"></i>
				<span>Журнал операций</span>
			</a>
		</nav>
	</div>
</div>
