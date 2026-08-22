<!DOCTYPE html>
<html lang="ru">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<title>@yield('title', 'Management Production')</title>

	@vite([
		'resources/scss/app.scss',
		'resources/js/app.js',
	])
</head>
<body>

<div class="wrapper">
	<div class="page">

		<div class="page__header">
			@include('layouts.header')
		</div>
		<div class="page__main-content">

			<main class="main-content container">
				<div class="main-content__body">
					<div class="main-content__column">
						@include('layouts.sidebar')
					</div>
					<div class="main-content__column">
						@yield('content')
					</div>
				</div>
			</main>

		</div>

		{{-- Одна общая модалка для просмотра операций --}}
		<div class="operation-modal" data-operation-modal aria-hidden="true">
			<div class="operation-modal__overlay" data-operation-modal-close></div>

			<div class="operation-modal__content" role="dialog" aria-modal="true">

				<button
						class="operation-modal__close button"
						type="button"
						aria-label="Закрыть"
						data-operation-modal-close>
					<span aria-hidden="true">Закрыть</span>
				</button>

				<div class="data-operation-modal-content"
						data-operation-modal-content></div>

			</div>
		</div>

	</div>
</div>

</body>
</html>