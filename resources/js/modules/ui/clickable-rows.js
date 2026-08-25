document.addEventListener('DOMContentLoaded', () => {
	const handler = (e) => {
		const row = e.target.closest('[data-row-link]');
		if (!row) return;

		const url = row.dataset.rowLink;
		if (!url) return;

		if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;

		if (e.type === 'keydown') {
			e.preventDefault();
		}

		window.location.href = url;
	};

	document.addEventListener('click', handler);
	document.addEventListener('keydown', handler);
});