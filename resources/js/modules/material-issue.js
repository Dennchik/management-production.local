import ItcCollapse from '../assets/its-collapse.js';

const form = document.querySelector('.issue-order');

if (form) {
	const materialSelect = form.querySelector('.material-select');

	const resetButton = form?.querySelector(
		'.issue-order__button--reset'
	);

	const rollInput = materialSelect.querySelector('#roll_id');

	const selectButton = materialSelect.querySelector(
		'.material-select__select-button'
	);

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

	const materialInput = form.querySelector('#material');
	const identifierInput = form.querySelector('#identifier');
	const rollNumberInput = form.querySelector('#roll_number');
	const remainingWeightInput = form.querySelector(
		'#remaining_weight'
	);
	const weightInput = form.querySelector('#weight');

	const collapse = new ItcCollapse(selectList);

	const resetSearch = () => {
		searchInput.value = '';
		searchClear.hidden = true;

		options.forEach((option) => {
			option.style.display = '';
		});

		emptyMessage.hidden = true;
	};

	const updateRollData = (option) => {
		if (!option) {
			rollInput.value = '';

			selectValue.textContent = 'Выберите рулон';

			materialInput.value = '';
			identifierInput.value = '';
			rollNumberInput.value = '';
			remainingWeightInput.value = '';

			weightInput.removeAttribute('max');

			return;
		}

		const weight = option.dataset.weight || '';

		rollInput.value = option.dataset.value || '';

		selectValue.textContent = option.textContent.trim();

		materialInput.value =
			option.dataset.material || '';

		identifierInput.value =
			option.dataset.identifier || '';

		rollNumberInput.value =
			option.dataset.roll || '';

		remainingWeightInput.value = weight;

		weightInput.max = weight;

		options.forEach((item) => {
			item.setAttribute(
				'aria-selected',
				item === option ? 'true' : 'false'
			);
		});

		collapse.hide();

		selectButton.setAttribute(
			'aria-expanded',
			'false'
		);

		resetSearch();
	};

	// .material-select__select-button
	selectButton.addEventListener('click', () => {
		collapse.toggle();

		selectButton.setAttribute(
			'aria-expanded',
			String(
				selectList.classList.contains('_show')
			)
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

		const parts = query.match(
			/[a-zа-яё]+|\d+/gi
		) || [];

		let visibleCount = 0;

		options.forEach((option) => {
			const text = option.textContent.trim().toLowerCase();

			const isVisible = parts.every(
				(part) => text.includes(part)
			);

			option.style.display = isVisible
				? ''
				: 'none';

			if (isVisible) {
				visibleCount++;
			}
		});

		emptyMessage.hidden =
			visibleCount > 0;
	});

	// .material-select__select-search-clear
	searchClear.addEventListener('click', () => {
		resetSearch();
		searchInput.focus();
	});

	// .material-select__select-option
	options.forEach((option) => {
		option.addEventListener('click', () => {
			updateRollData(option);
		});
	});

	// Восстановление выбранного рулона
	// после ошибки валидации
	const selectedOption = Array.from(options).find(
		(option) =>
			option.dataset.value === rollInput.value
	);

	updateRollData(selectedOption);

	// .issue-order__button--reset
	resetButton?.addEventListener('click', () => {
		rollInput.value = '';

		selectValue.textContent =
			'Выберите рулон';

		options.forEach((option) => {
			option.setAttribute(
				'aria-selected',
				'false'
			);
		});

		materialInput.value = '';
		identifierInput.value = '';
		rollNumberInput.value = '';
		remainingWeightInput.value = '';

		weightInput.value = '';
		weightInput.removeAttribute('max');

		resetSearch();

		if (selectList.classList.contains('_show')) {
			collapse.hide();
		}

		selectButton.setAttribute(
			'aria-expanded',
			'false'
		);
	});
}