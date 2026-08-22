import ItcCollapse from '../assets/its-collapse.js';

const filter = document.querySelector(
	'.filters-actions'
);

if (filter) {
	/*
	 * Выпадающий список материала.
	 */
	const materialSelect = filter.querySelector(
		'.material-select'
	);

	if (materialSelect) {
		const materialInput = materialSelect.querySelector(
			'#material-movements-material'
		);

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
		 * Выбор материала.
		 */
		const updateMaterial = (option) => {
			if (!option) {
				materialInput.value = '';
				selectValue.textContent = 'Все материалы';

				options.forEach((item) => {
					item.setAttribute(
						'aria-selected',
						'false'
					);
				});

				resetSearch();

				return;
			}

			materialInput.value = option.dataset.value || '';

			const text = option.querySelector('span')?.textContent.trim();

			selectValue.textContent = text || 'Все материалы';

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

		/*
		 * Открытие / закрытие списка.
		 */
		selectButton.addEventListener('click', () => {
			collapse.toggle();

			selectButton.setAttribute(
				'aria-expanded',
				String(
					selectList.classList.contains('_show')
				)
			);

			if (
				selectList.classList.contains('_show')
			) {
				searchInput.focus();
			}
		});

		/*
		 * Поиск материала внутри списка.
		 */
		searchInput.addEventListener(
			'input',
			(event) => {
				const query = event.target.value.trim().toLowerCase();

				searchClear.hidden = !query;

				if (!query) {
					options.forEach((option) => {
						option.style.display = '';
					});

					emptyMessage.hidden = true;

					return;
				}

				const parts =
					query.match(/[a-zа-яё]+|\d+/gi) || [];

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
			}
		);

		/*
		 * Очистить поиск.
		 */
		searchClear.addEventListener(
			'click',
			() => {
				resetSearch();
				searchInput.focus();
			}
		);

		/*
		 * Выбор материала.
		 */
		options.forEach((option) => {
			option.addEventListener(
				'click',
				() => {
					updateMaterial(option);
				}
			);
		});

		/*
		 * Восстановление выбранного материала
		 * после применения GET-фильтра.
		 */
		const selectedOption =
			Array.from(options).find(
				(option) =>
					option.dataset.value ===
					materialInput.value
			);

		if (selectedOption) {
			const text = selectedOption.querySelector('span')?.textContent.trim();

			selectValue.textContent =
				text || 'Все материалы';
		} else {
			materialInput.value = '';
			selectValue.textContent =
				'Все материалы';
		}
	}

	/*
	 * Остальные выпадающие списки фильтра.
	 *
	 * Используем существующую разметку:
	 * .select
	 * .select__button
	 * .select__value
	 * .select__dropdown
	 * .select__item
	 */
	const selects = filter.querySelectorAll(
		'[data-select]'
	);

	selects.forEach((select) => {
		/*
		 * Не обрабатываем material-select здесь,
		 * потому что он имеет собственную логику.
		 */
		if (
			select.querySelector(
				'.material-select__select-list'
			)
		) {
			return;
		}

		const selectElement =
			select.querySelector('.select');

		if (!selectElement) {
			return;
		}

		const button =
			selectElement.querySelector(
				'.select__button'
			);

		const value =
			selectElement.querySelector(
				'.select__value'
			);

		const dropdown =
			selectElement.querySelector(
				'.select__dropdown'
			);

		const items =
			selectElement.querySelectorAll(
				'.select__item'
			);

		if (
			!button ||
			!value ||
			!dropdown
		) {
			return;
		}

		const collapse =
			new ItcCollapse(dropdown);

		/*
		 * Открытие / закрытие.
		 */
		button.addEventListener(
			'click',
			() => {
				collapse.toggle();
			}
		);

		/*
		 * Выбор значения.
		 */
		items.forEach((item) => {
			item.addEventListener(
				'click',
				() => {
					value.value =
						item.textContent.trim();

					const selectedValue =
						item.dataset.value ?? '';

					value.setAttribute(
						'value',
						selectedValue
					);

					items.forEach((option) => {
						option.classList.toggle(
							'_selected',
							option === item
						);
					});

					collapse.hide();
				}
			);
		});
	});
}