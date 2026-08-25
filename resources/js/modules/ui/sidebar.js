document.addEventListener('DOMContentLoaded', () => {
	const toggleBtn = document.querySelector('.header__menu-toggle');
	const sidebar = document.querySelector('.sidebar');

	if (!toggleBtn || !sidebar) return;

	const isDesktop = () => window.innerWidth >= 1024;

	// Начальное состояние
	if (!isDesktop()) {
		sidebar.style.display = 'none';
	}

	toggleBtn.addEventListener('click', () => {
		const isOpen = sidebar.style.display !== 'none';

		if (isOpen) {
			sidebar.style.display = 'none';
			toggleBtn.setAttribute('aria-expanded', 'false');
		} else {
			sidebar.style.display = 'block';
			toggleBtn.setAttribute('aria-expanded', 'true');
		}
	});

	// При изменении размера
	window.addEventListener('resize', () => {
		if (isDesktop()) {
			sidebar.style.display = 'block';
			toggleBtn.setAttribute('aria-expanded', 'true');
		} else {
			sidebar.style.display = 'none';
			toggleBtn.setAttribute('aria-expanded', 'false');
		}
	});
});