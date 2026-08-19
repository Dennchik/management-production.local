@if (session('success'))
	<div class="message message--success" role="alert">
		<div class="message__content">
			{{ session('success') }}
		</div>

		<button
				class="message__close"
				type="button"
				aria-label="Закрыть сообщение">
			<i class="icon icon-close" aria-hidden="true"></i>
		</button>
	</div>
@endif

@if ($errors->any())
	<div class="message message--error" role="alert">
		<div class="message__content">
			<ul class="message__errors">
				@foreach ($errors->all() as $error)
					<li class="message__error">
						{{ $error }}
					</li>
				@endforeach
			</ul>
		</div>

		<button
				class="message__close"
				type="button"
				aria-label="Закрыть сообщение">
			<i class="icon icon-close" aria-hidden="true"></i>
		</button>
	</div>
@endif