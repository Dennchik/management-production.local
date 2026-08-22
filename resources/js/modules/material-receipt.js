import ItcCollapse from '../assets/its-collapse.js';

const form = document.querySelector('.receipt-order');

if (form) {
	const materialSelect = form.querySelector('.material-select');
	const resetButton = form.querySelector(
		'.receipt-order__button--reset'
	);

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

	const grammageInput = form.querySelector('#grammage');
	const thicknessInput = form.querySelector('#thickness');
	const formatInput = form.querySelector('#format');
	const identifierInput = form.querySelector('#identifier');

	/*
	 * Рулоны
	 */
	const rollsList = form.querySelector('[data-receipt-rolls]');
	const addRollButton = form.querySelector(
		'[data-receipt-roll-add]'
	);

	const collapse = new ItcCollapse(selectList);

	/*
	 * Сброс поиска материала.
	 */
	const resetSearch = () => {
		searchInput.value = '';
		searchClear.hidden = true;

		options.forEach((option) => {
			option.style.display = '';
		});

		emptyMessage.hidden = true;
	};

	/*
	 * Обновление данных выбранного материала.
	 */
	const updateMaterialData = (option) => {
		if (!option) {
			materialInput.value = '';

			selectValue.textContent = 'Выберите материал';

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

	/*
	 * Получить строки рулонов.
	 */
	const getRolls = () => {
		return Array.from(
			rollsList.querySelectorAll('[data-receipt-roll]')
		);
	};

	/*
	 * Обновить индексы рулонов.
	 *
	 * Например:
	 *
	 * rolls[0][roll_number]
	 * rolls[0][weight]
	 *
	 * rolls[1][roll_number]
	 * rolls[1][weight]
	 */
	const updateRollIndexes = () => {
		const rolls = getRolls();

		rolls.forEach((roll, index) => {
			const rollNumberInput = roll.querySelector(
				'[data-receipt-roll-number]'
			);

			const weightInput = roll.querySelector(
				'[data-receipt-roll-weight]'
			);

			if (rollNumberInput) {
				rollNumberInput.name = `rolls[${index}][roll_number]`;
				rollNumberInput.id = `roll_number_${index}`;

				const label = roll.querySelector(
					'[data-receipt-roll-number-label]'
				);

				if (label) {
					label.htmlFor = `roll_number_${index}`;
				}
			}

			if (weightInput) {
				weightInput.name = `rolls[${index}][weight]`;
				weightInput.id = `weight_${index}`;

				const label = roll.querySelector(
					'[data-receipt-roll-weight-label]'
				);

				if (label) {
					label.htmlFor = `weight_${index}`;
				}
			}
		});
	};

	/*
	 * Обновить доступность кнопок удаления.
	 *
	 * Последний оставшийся рулон удалить нельзя.
	 */
	const updateRemoveButtons = () => {
		const rolls = getRolls();

		rolls.forEach((roll) => {
			const removeButton = roll.querySelector(
				'[data-receipt-roll-remove]'
			);

			if (removeButton) {
				removeButton.disabled = rolls.length === 1;
			}
		});
	};

	/*
	 * Создать строку рулона.
	 */
	const createRoll = () => {
		const roll = document.createElement('div');

		roll.className = 'receipt-order__roll';
		roll.setAttribute('data-receipt-roll', '');

		roll.innerHTML = `
       <fieldset class="receipt-order__field">

         <label
             class="receipt-order__label"
             data-receipt-roll-number-label>
           Номер рулона
         </label>

         <input
             class="receipt-order__input"
             data-receipt-roll-number
             type="text">

       </fieldset>

       <fieldset class="receipt-order__field">

         <label
             class="receipt-order__label"
             data-receipt-roll-weight-label>
           Вес, кг
         </label>

         <input
             class="receipt-order__input"
             data-receipt-roll-weight
             type="number"
             step="0.001"
             min="0">

       </fieldset>

       <button
           class="receipt-order__roll-remove button"
           type="button"
           data-receipt-roll-remove
           aria-label="Удалить рулон">
         <span aria-hidden="true">Удалить</span>
       </button>
     `;

		return roll;
	};

	/*
	 * Добавить рулон.
	 */
	const addRoll = () => {
		const roll = createRoll();

		rollsList.appendChild(roll);

		updateRollIndexes();
		updateRemoveButtons();

		const input = roll.querySelector(
			'[data-receipt-roll-number]'
		);

		input?.focus();
	};

	/*
	 * Удаление рулона.
	 *
	 * Используем делегирование, поэтому обработчики
	 * не нужно назначать каждому новому рулону отдельно.
	 */
	rollsList.addEventListener('click', (event) => {
		const removeButton = event.target.closest(
			'[data-receipt-roll-remove]'
		);

		if (!removeButton) {
			return;
		}

		const rolls = getRolls();

		if (rolls.length === 1) {
			return;
		}

		const roll = removeButton.closest('[data-receipt-roll]');

		roll?.remove();

		updateRollIndexes();
		updateRemoveButtons();
	});

	/*
	 * .select-button
	 */
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

	/*
	 * Поиск материала.
	 */
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

			const isVisible = parts.every((part) =>
				text.includes(part)
			);

			option.style.display = isVisible ? '' : 'none';

			if (isVisible) {
				visibleCount++;
			}
		});

		emptyMessage.hidden = visibleCount > 0;
	});

	/*
	 * Очистить поиск материала.
	 */
	searchClear.addEventListener('click', () => {
		resetSearch();
		searchInput.focus();
	});

	/*
	 * Выбор материала.
	 */
	options.forEach((option) => {
		option.addEventListener('click', () => {
			updateMaterialData(option);
		});
	});

	/*
	 * Восстановление выбранного материала
	 * после ошибки валидации.
	 */
	const selectedOption = Array.from(options).find(
		(option) => option.dataset.value === materialInput.value
	);

	updateMaterialData(selectedOption);

	/*
	 * Добавить рулон.
	 */
	addRollButton?.addEventListener('click', addRoll);

	/*
	 * Очистить форму.
	 */
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

		/*
		 * Оставляем только один рулон.
		 */
		const rolls = getRolls();

		rolls.slice(1).forEach((roll) => {
			roll.remove();
		});

		const firstRoll = getRolls()[0];

		if (firstRoll) {
			const rollNumberInput = firstRoll.querySelector(
				'[data-receipt-roll-number]'
			);

			const weightInput = firstRoll.querySelector(
				'[data-receipt-roll-weight]'
			);

			if (rollNumberInput) {
				rollNumberInput.value = '';
			}

			if (weightInput) {
				weightInput.value = '';
			}
		}

		updateRollIndexes();
		updateRemoveButtons();
	});

	/*
	 * Начальная инициализация.
	 */
	updateRollIndexes();
	updateRemoveButtons();
}