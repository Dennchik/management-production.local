import ItcCollapse from '../assets/its-collapse.js';

const materialSelect = document.querySelector('.material-select');

if (materialSelect) {
	const form = materialSelect.closest('form');
	const resetButton = form?.querySelector('.receipt-order__button--reset');

	const materialInput = materialSelect.querySelector('#material_id');
	const selectButton = materialSelect.querySelector('.select-button');
	const selectValue = materialSelect.querySelector(
		'.material-select__select-value'
	);
	const selectList = materialSelect.querySelector(
		'.material-select__select-list'
	);
	const searchInput = materialSelect.querySelector(
		'.material-select__select-search-input'
	);
	const searchClear = materialSelect.querySelector(
		'.material-select__select-search-clear'
	);
	const options = materialSelect.querySelectorAll(
		'.material-select__select-option'
	);
	const emptyMessage = materialSelect.querySelector(
		'.material-select__select-empty'
	);

	const grammageInput = document.querySelector('#grammage');
	const thicknessInput = document.querySelector('#thickness');
	const formatInput = document.querySelector('#format');
	const identifierInput = document.querySelector('#identifier');

	const collapse = new ItcCollapse(selectList);

	const resetSearch = () => {
		searchInput.value = '';
		searchClear.hidden = true;

		options.forEach((option) => {
			option.style.display = '';
		});

		emptyMessage.hidden = true;
	};

	const updateMaterialData = (option) => {
		if (!option) {
			grammageInput.value = '';
			thicknessInput.value = '';
			formatInput.value = '';
			identifierInput.value = '';

			return;
		}

		materialInput.value = option.dataset.value || '';

		selectValue.textContent = option.textContent.trim();

		grammageInput.value = option.dataset.grammage || '';
		thicknessInput.value = option.dataset.thickness || '';
		formatInput.value = option.dataset.format || '';
		identifierInput.value = option.dataset.identifier || '';

		options.forEach((item) => {
			item.setAttribute(
				'aria-selected',
				item === option ? 'true' : 'false'
			);
		});

		collapse.hide();

		selectButton.setAttribute('aria-expanded', 'false');

		resetSearch();
	};

	// .select-button
	selectButton.addEventListener('click', () => {
		collapse.toggle();

		selectButton.setAttribute(
			'aria-expanded',
			String(selectList.classList.contains('_show'))
		);

		if (selectList.classList.contains('_show')) {
			searchInput.focus();
		}
	});

	// .material-select__select-search-input
	searchInput.addEventListener('input', (event) => {
		const query = event.target.value.trim().toLowerCase();

		searchClear.hidden = !query;

		if (!query) {
			options.forEach((option) => {
				option.style.display = '';
			});

			emptyMessage.hidden = true;

			return;
		}

		const parts = query.match(/[a-zа-яё]+|\d+/gi) || [];

		let visibleCount = 0;

		options.forEach((option) => {
			const text = option.textContent.trim().toLowerCase();

			const isVisible = parts.every((part) => text.includes(part));

			option.style.display = isVisible ? '' : 'none';

			if (isVisible) {
				visibleCount++;
			}
		});

		emptyMessage.hidden = visibleCount > 0;
	});

	// .material-select__select-search-clear
	searchClear.addEventListener('click', () => {
		resetSearch();
		searchInput.focus();
	});

	// .material-select__select-option
	options.forEach((option) => {
		option.addEventListener('click', () => {
			updateMaterialData(option);
		});
	});

	// Восстановление выбранного материала после ошибки валидации
	const selectedOption = Array.from(options).find(
		(option) => option.dataset.value === materialInput.value
	);

	updateMaterialData(selectedOption);

	// .receipt-order__button--reset
	resetButton?.addEventListener('click', () => {
		materialInput.value = '';

		selectValue.textContent = 'Выберите материал';

		options.forEach((option) => {
			option.setAttribute('aria-selected', 'false');
		});

		grammageInput.value = '';
		thicknessInput.value = '';
		formatInput.value = '';
		identifierInput.value = '';

		resetSearch();

		if (selectList.classList.contains('_show')) {
			collapse.hide();
		}

		selectButton.setAttribute('aria-expanded', 'false');
	});
}