import { initSelects } from '../../assets/select.js';

document.addEventListener('DOMContentLoaded', () => {
	const filter = document.querySelector('.filters-actions');
	if (!filter) return;

	// Инициализируем select-ы внутри фильтра
	initSelects();
});