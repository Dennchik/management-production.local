const messages = document.querySelectorAll('.message');

messages.forEach((message) => {
	const closeButton = message.querySelector('.message__close');

	const hideMessage = () => {
		message.classList.add('_hide');

		message.addEventListener(
			'animationend',
			() => {
				message.remove();
			},
			{once: true}
		);
	};

	closeButton?.addEventListener('click', hideMessage);

	setTimeout(hideMessage, 5000);
});