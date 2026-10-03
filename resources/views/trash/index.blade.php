@extends('layouts.app')

@section('title', 'Корзина')

@section('content')

	<div class="main-content__content">
		<div class="main-content__header">
			<h1 class="main-content__title">Корзина</h1>
		</div>

		@php
			$empty = $catalogs->isEmpty() && $materials->isEmpty();
		@endphp

		@if ($empty)
			<div class="material">
				<div class="material__content">
					<section class="material__section">
						<p class="material__empty">Корзина пуста.</p>
					</section>
				</div>
			</div>
		@else
			<div class="material" data-trash-page>
				<div class="material__content">

					@if ($catalogs->isNotEmpty())
						<section class="material__section material__section--format">
							<h2 class="material__section-title">Каталоги</h2>

							<div class="material__column material__column--full">
								<div class="material__table-wrapper">
									<table class="material__table">
										<thead>
										<tr>
											<th>Название</th>
											<th>Материалов</th>
											<th>Удалён</th>
											<th></th>
										</tr>
										</thead>

										<tbody>
										@foreach ($catalogs as $catalog)
											<tr>
												<td>{{ $catalog->name }}</td>
												<td>{{ $catalog->materials_count }}</td>
												<td>{{ $catalog->deleted_at->format('d.m.Y H:i') }}</td>
												<td class="materials__actions-icons">
													<button type="button" data-trash-restore
															data-trash-type="catalogs" data-trash-id="{{ $catalog->id }}"
															aria-label="Восстановить" title="Восстановить">
														<i class="icon icon-indent-decrease" aria-hidden="true"></i>
													</button>

													<button type="button" data-trash-delete
															data-trash-type="catalogs" data-trash-id="{{ $catalog->id }}"
															aria-label="Удалить навсегда" title="Удалить навсегда">
														<i class="icon icon-trash" aria-hidden="true"></i>
													</button>
												</td>
											</tr>
										@endforeach
										</tbody>
									</table>
								</div>
							</div>
						</section>
					@endif

					@if ($materials->isNotEmpty())
						<section class="material__section material__section--format">
							<h2 class="material__section-title">Материалы</h2>

							<div class="material__column material__column--full">
								<div class="material__table-wrapper">
									<table class="material__table">
										<thead>
										<tr>
											<th>Название</th>
											<th>Каталог</th>
											<th>Рулонов</th>
											<th>Удалён</th>
											<th></th>
										</tr>
										</thead>

										<tbody>
										@foreach ($materials as $material)
											<tr>
												<td>{{ $material->name }}</td>
												<td>{{ $material->catalog?->name ?? '—' }}</td>
												<td>{{ $material->rolls_count }}</td>
												<td>{{ $material->deleted_at->format('d.m.Y H:i') }}</td>
												<td class="materials__actions-icons">
													<button type="button" data-trash-restore
															data-trash-type="materials" data-trash-id="{{ $material->id }}"
															aria-label="Восстановить" title="Восстановить">
														<i class="icon icon-indent-decrease" aria-hidden="true"></i>
													</button>

													<button type="button" data-trash-delete
															data-trash-type="materials" data-trash-id="{{ $material->id }}"
															aria-label="Удалить навсегда" title="Удалить навсегда">
														<i class="icon icon-trash" aria-hidden="true"></i>
													</button>
												</td>
											</tr>
										@endforeach
										</tbody>
									</table>
								</div>
							</div>
						</section>
					@endif
				</div>
			</div>
		@endif
	</div>

@endsection
