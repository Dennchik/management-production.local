@extends('layouts.app')

@section('title', 'Разрешённые материалы — ' . $operation->name)

@section('content')
	<div class="main-content__content materials"
			data-allowed-catalogs-page
			data-allowed-catalogs-update="{{ route('production.operations.allowed-catalogs.update', $operation) }}">
		<div class="main-content__header">
			<h1 class="main-content__title">Разрешённые материалы — {{ $operation->name }}</h1>

			<a class="button button--secondary" href="{{ route('production.lines.show', $operation) }}">
				<span>Назад к линиям</span>
			</a>
		</div>

		<nav class="main-content__breadcrumbs">
			<a href="{{ route('production.operations.index') }}">Технологические линии</a>
			<span> / </span>
			<a href="{{ route('production.lines.show', $operation) }}">{{ $operation->name }}</a>
			<span> / </span>
			<a href="{{ route('production.operations.allowed-catalogs', $operation) }}">Разрешённые материалы</a>
			@foreach ($breadcrumbs as $breadcrumb)
				<span> / </span>
				@if ($loop->last)
					<span class="main-content__breadcrumbs-current">{{ $breadcrumb->name }}</span>
				@else
					<a href="{{ route('production.operations.allowed-catalogs', [$operation, 'catalog' => $breadcrumb->id]) }}">
						{{ $breadcrumb->name }}
					</a>
				@endif
			@endforeach
		</nav>

		<div class="materials__content">
			<div class="materials__table">
				<table>
					<thead>
					<tr>
						<th>№</th>
						<th>{{ $currentCatalog === null ? 'Каталог' : 'Название' }}</th>
						<th>Разрешено</th>
					</tr>
					</thead>

					<tbody class="materials__body" data-allowed-catalogs-body>
					@if ($hierarchyEnabled && $catalogs->isEmpty() && $materials->isEmpty())
						<tr class="materials__empty">
							<td colspan="3">Каталоги и материалы не добавлены.</td>
						</tr>
					@elseif (!$hierarchyEnabled && $materials->isEmpty())
						<tr class="materials__empty">
							<td colspan="3">Материалы не добавлены.</td>
						</tr>
					@else
						@foreach ($catalogs as $catalog)
							@php $state = $catalogStates[$catalog->id]; @endphp

							<tr class="materials__catalog-row">
								<td>{{ $loop->iteration }}</td>

								<td>
									<a class="materials__catalog-link"
											href="{{ route('production.operations.allowed-catalogs', [$operation, 'catalog' => $catalog->id]) }}">
										{{ $catalog->name }}
									</a>
								</td>

								<td>
									<input type="checkbox"
											data-allowed-catalog
											data-catalog-id="{{ $catalog->id }}"
											data-catalog-name="{{ $catalog->name }}"
											data-materials-count="{{ $state['total'] }}"
											data-state="{{ $state['state'] }}"
											{{ $state['state'] === 'all' ? 'checked' : '' }}>
								</td>
							</tr>
						@endforeach

						@foreach ($materials as $material)
							<tr>
								<td>{{ $loop->iteration }}</td>

								<td>{{ $material->name }}</td>

								<td>
									<input type="checkbox"
											data-allowed-material
											data-material-id="{{ $material->id }}"
											{{ $allowedMaterialIds->contains($material->id) ? 'checked' : '' }}>
								</td>
							</tr>
						@endforeach
					@endif
					</tbody>
				</table>
			</div>
		</div>
	</div>
@endsection
