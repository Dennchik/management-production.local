document.addEventListener('DOMContentLoaded', () => {
	const form = document.querySelector('.receipt-order');
	if (!form) return;

	const materialSelect = form.querySelector('.material-select');
	if (!materialSelect) return;

	const hiddenInput = materialSelect.querySelector('#material_id');
	const button = materialSelect.querySelector('.select-button');
	const valueSpan = materialSelect.querySelector(
		'.material-select__select-value');
	const dropdown = materialSelect.querySelector(
		'.material-select__select-list');
	const searchInput = materialSelect.querySelector(
		'.material-select__select-search-input');
	const searchClear = materialSelect.querySelector(
		'.material-select__select-search-clear');
	const options = materialSelect.querySelectorAll(
		'.material-select__select-option');
	const emptyMsg = materialSelect.querySelector(
		'.material-select__select-empty');

	// Поля для подстановки
	const grammageInput = form.querySelector('#grammage');
	const thicknessInput = form.querySelector('#thickness');
	const formatInput = form.querySelector('#format');
	const identifierInput = form.querySelector('#identifier');

	function resetSearch() {
		searchInput.value = '';
		searchClear.hidden = true;
		options.forEach(opt => opt.style.display = '');
		emptyMsg.hidden = true;
	}

	function selectMaterial(option) {
		if (!option) {
			hiddenInput.value = '';
			valueSpan.textContent = 'Выберите материал';
			grammageInput.value = '';
			thicknessInput.value = '';
			formatInput.value = '';
			identifierInput.value = '';
			return;
		}

		hiddenInput.value = option.dataset.value;
		valueSpan.textContent = option.textContent.trim();

		grammageInput.value = option.dataset.grammage || '';
		thicknessInput.value = option.dataset.thickness || '';
		formatInput.value = option.dataset.format || '';
		identifierInput.value = option.dataset.identifier || '';

		options.forEach(el => el.setAttribute('aria-selected',
			el === option ? 'true' : 'false'));

		button.setAttribute('aria-expanded', 'false');
		resetSearch();
	}

	// Поиск с фильтрацией
	searchInput.addEventListener('input', (e) => {
		const query = e.target.value.trim().toLowerCase();
		searchClear.hidden = !query;

		if (!query) {
			options.forEach(opt => opt.style.display = '');
			emptyMsg.hidden = true;
			return;
		}

		const parts = query.match(/[a-zа-яё]+|\d+/gi) || [];
		let visible = 0;

		options.forEach(opt => {
			const text = opt.textContent.trim().toLowerCase();
			const match = parts.every(p => text.includes(p));
			opt.style.display = match ? '' : 'none';
			if (match) visible++;
		});

		emptyMsg.hidden = visible > 0;
	});

	searchClear.addEventListener('click', () => {
		resetSearch();
		searchInput.focus();
	});

	// Выбор опции
	options.forEach(opt => {
		opt.addEventListener('click', () => selectMaterial(opt));
	});

	// Восстановление после ошибки
	const selected = Array.from(options).find(
		o => o.dataset.value === hiddenInput.value);
	selectMaterial(selected);

	// === Рулоны ===
	// ... остальной код без изменений
});