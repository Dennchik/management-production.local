document.addEventListener('DOMContentLoaded', () => {
	const messages = document.querySelectorAll('.message');

	messages.forEach((msg) => {
		const closeBtn = msg.querySelector('.message__close');

		function hide() {
			msg.classList.add('_hide');
			msg.addEventListener('animationend', () => msg.remove(), {once: true});
		}

		closeBtn?.addEventListener('click', hide);
		setTimeout(hide, 5000);
	});
});