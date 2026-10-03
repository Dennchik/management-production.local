@extends('layouts.app')

@section('title', 'Права роли «' . $role->name . '»')

@section('content')
	<div class="main-content__content materials">
		<div class="main-content__header">
			<h1 class="main-content__title">Права роли «{{ $role->name }}»</h1>

			<a class="button button--secondary" href="{{ route('roles.index') }}">
				<span>К списку ролей</span>
			</a>
		</div>

		@include('partials.message')

		<form method="POST" action="{{ route('roles.update', $role) }}">
			@csrf

			<div class="materials__content">
				<div class="materials__table">
					<table>
						<thead>
						<tr>
							<th style="text-align: left;">Объект</th>
							@foreach ($actions as $actionValue => $actionLabel)
								<th>{{ $actionLabel }}</th>
							@endforeach
						</tr>
						</thead>
						<tbody>
						@foreach ($objects as $objectValue => $objectLabel)
							<tr>
								<td>{{ $objectLabel }}</td>
								@foreach ($actions as $actionValue => $actionLabel)
									<td style="text-align: center;">
										<label>
											<input type="checkbox"
													name="permissions[{{ $objectValue }}][]"
													value="{{ $actionValue }}"
													{{ $role->hasPermission($objectValue, $actionValue) ? 'checked' : '' }}>
										</label>
									</td>
								@endforeach
							</tr>
						@endforeach
						</tbody>
					</table>
				</div>
			</div>

			<div class="issue-order__actions" style="margin-top: 1rem;">
				<button class="main-content__button button" type="submit">
					<span>Сохранить права</span>
				</button>
			</div>
		</form>
	</div>
@endsection
