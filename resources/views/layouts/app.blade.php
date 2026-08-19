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
		<main class="main-content">
			<div class="main-content__body container">
				<div class="main-content__column">
					@include('layouts.sidebar')
				</div>
				<div class="main-content__column">
					@yield('content')
				</div>
			</div>
		</main>


	</div>
</div>

</body>
</html>