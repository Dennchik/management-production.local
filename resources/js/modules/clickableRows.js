document.addEventListener('DOMContentLoaded', () => {
	document.addEventListener('click', (event) => {
		const row = event.target.closest(
			'[data-row-link]'
		);

		if (!row) {
			return;
		}

		const url = row.dataset.rowLink;

		if (!url) {
			return;
		}

		window.location.href = url;
	});

	document.addEventListener('keydown', (event) => {
		if (
			event.key !== 'Enter' &&
			event.key !== ' '
		) {
			return;
		}

		const row = event.target.closest(
			'[data-row-link]'
		);

		if (!row) {
			return;
		}

		event.preventDefault();

		const url = row.dataset.rowLink;

		if (!url) {
			return;
		}

		window.location.href = url;
	});
});