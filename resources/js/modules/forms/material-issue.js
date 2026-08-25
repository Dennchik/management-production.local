document.addEventListener('DOMContentLoaded', () => {
	const form = document.querySelector('.issue-order');

	if (!form) {
		return;
	}

	// =========================================================
	// Материал
	// =========================================================

	const materialSelect = form.querySelector(
		'.material-select'
	);

	if (!materialSelect) {
		console.error('material-select не найден');
		return;
	}

	const materialHidden = materialSelect.querySelector(
		'#material_id'
	);

	const materialOptions = materialSelect.querySelectorAll(
		'.material-select__select-option'
	);

	const materialNameInput = form.querySelector(
		'#material_name'
	);

	const materialIdentifierInput = form.querySelector(
		'#material_identifier'
	);


	// =========================================================
	// Рулон
	// =========================================================

	const selects = form.querySelectorAll(
		'.material-select'
	);

	const rollSelect = selects[1];

	if (!rollSelect) {
		console.error('roll-select не найден');
		return;
	}

	const rollHidden = rollSelect.querySelector(
		'#roll_id'
	);

	const rollValueSpan = rollSelect.querySelector(
		'.material-select__select-value'
	);

	const rollList = rollSelect.querySelector(
		'#rolls-list'
	);

	const rollSearchInput = rollSelect.querySelector(
		'.select__search'
	);

	const rollSearchClear = rollSelect.querySelector(
		'.select__search-clear'
	);

	const rollEmptyMsg = rollSelect.querySelector(
		'.select__empty'
	);


	// =========================================================
	// Поля
	// =========================================================

	const remainingWeightInput = form.querySelector(
		'#remaining_weight'
	);

	const weightInput = form.querySelector(
		'#weight'
	);


	// =========================================================
	// Сброс данных рулона
	// =========================================================

	function resetRoll() {
		if (rollHidden) {
			rollHidden.value = '';
		}

		if (rollValueSpan) {
			rollValueSpan.textContent = 'Выберите рулон';
		}

		if (remainingWeightInput) {
			remainingWeightInput.value = '';
		}

		if (weightInput) {
			weightInput.removeAttribute('max');
		}
	}


	// =========================================================
	// Загрузка рулонов
	// =========================================================

	async function fetchRolls(materialId) {
		if (!materialId) {
			updateRollList([]);
			return;
		}

		try {
			const response = await fetch(
				`/api/rolls?material_id=${encodeURIComponent(materialId)}`,
				{
					headers: {
						Accept: 'application/json',
					},
				}
			);

			if (!response.ok) {
				throw new Error(`HTTP ${response.status}`);
			}

			const data = await response.json();

			updateRollList(data);

		} catch (error) {
			console.error(
				'Ошибка загрузки рулонов:',
				error
			);

			updateRollList([]);
		}
	}


	// =========================================================
	// Обновление списка рулонов
	// =========================================================

	function updateRollList(rolls) {
		if (!rollList) {
			return;
		}

		/*
		 * Удаляем старые рулоны.
		 */
		rollList.innerHTML = '';

		/*
		 * Сбрасываем выбранный рулон.
		 */
		resetRoll();

		/*
		 * Рулонов нет.
		 */
		if (!rolls || rolls.length === 0) {
			if (rollEmptyMsg) {
				rollEmptyMsg.hidden = false;
			}

			if (rollValueSpan) {
				rollValueSpan.textContent =
					'Нет доступных рулонов';
			}

			return;
		}

		if (rollEmptyMsg) {
			rollEmptyMsg.hidden = true;
		}

		/*
		 * Создаём пункты.
		 *
		 * Никаких собственных обработчиков click здесь нет.
		 * За выбор отвечает универсальный select.js.
		 */
		rolls.forEach((roll) => {
			const button = document.createElement('button');

			button.className =
				'material-select__select-option select__item';

			button.type = 'button';
			button.role = 'option';

			button.dataset.value = roll.id;
			button.dataset.roll = roll.roll_number;
			button.dataset.weight = roll.weight;

			button.setAttribute(
				'aria-selected',
				'false'
			);

			button.innerHTML = `
         <span>
           ${roll.roll_number} | ${roll.weight} кг
         </span>
       `;

			rollList.appendChild(button);
		});

		if (rollValueSpan) {
			rollValueSpan.textContent =
				'Выберите рулон';
		}
	}


	// =========================================================
	// Выбор материала
	// =========================================================

	materialSelect.addEventListener(
		'select-change',
		(event) => {
			const materialId =
				event.detail?.value;

			const option =
				event.detail?.item;

			if (!materialId || !option) {
				return;
			}

			/*
			 * Название материала.
			 */
			if (materialNameInput) {
				materialNameInput.value =
					option.dataset.name || '';
			}

			/*
			 * Идентификатор материала.
			 */
			if (materialIdentifierInput) {
				materialIdentifierInput.value =
					option.dataset.identifier || '';
			}

			/*
			 * При смене материала
			 * старый рулон сбрасываем.
			 */
			resetRoll();

			/*
			 * Загружаем рулоны выбранного материала.
			 */
			fetchRolls(materialId);
		}
	);


	// =========================================================
	// Выбор рулона
	// =========================================================

	rollSelect.addEventListener(
		'select-change',
		(event) => {
			const rollId =
				event.detail?.value;

			const option =
				event.detail?.item;

			if (!rollId || !option) {
				resetRoll();
				return;
			}

			/*
			 * Остаток выбранного рулона.
			 */
			if (remainingWeightInput) {
				remainingWeightInput.value =
					option.dataset.weight || '';
			}

			/*
			 * Вес расхода не может
			 * превышать остаток.
			 */
			if (weightInput) {
				weightInput.max =
					option.dataset.weight || '';
			}
		}
	);


	// =========================================================
	// Поиск по рулонам
	// =========================================================

	if (rollSearchInput && rollList) {

		rollSearchInput.addEventListener(
			'input',
			(event) => {
				const query = event.target.value.trim().toLowerCase();

				if (rollSearchClear) {
					rollSearchClear.hidden = !query;
				}

				const items =
					rollList.querySelectorAll(
						'.select__item'
					);

				if (!query) {
					items.forEach((item) => {
						item.style.display = '';
					});

					if (rollEmptyMsg) {
						rollEmptyMsg.hidden = true;
					}

					return;
				}

				let visible = 0;

				items.forEach((item) => {
					const match =
						item.textContent.trim().toLowerCase().includes(query);

					item.style.display =
						match ? '' : 'none';

					if (match) {
						visible++;
					}
				});

				if (rollEmptyMsg) {
					rollEmptyMsg.hidden =
						visible > 0;
				}
			}
		);


		rollSearchClear?.addEventListener(
			'click',
			() => {
				rollSearchInput.value = '';

				rollSearchInput.dispatchEvent(
					new Event('input')
				);

				rollSearchInput.focus();
			}
		);
	}


	// =========================================================
	// Очистка формы
	// =========================================================

	const resetButton = form.querySelector(
		'.issue-order__button--reset'
	);

	if (resetButton) {

		resetButton.addEventListener(
			'click',
			() => {

				/*
				 * Материал.
				 */
				if (materialHidden) {
					materialHidden.value = '';
				}

				if (materialNameInput) {
					materialNameInput.value = '';
				}

				if (materialIdentifierInput) {
					materialIdentifierInput.value = '';
				}

				materialOptions.forEach(
					(option) => {
						option.classList.remove(
							'_selected'
						);

						option.setAttribute(
							'aria-selected',
							'false'
						);
					}
				);


				/*
				 * Рулон.
				 */
				resetRoll();

				if (rollList) {
					rollList.innerHTML = '';
				}

				if (rollValueSpan) {
					rollValueSpan.textContent =
						'Сначала выберите материал';
				}

				if (rollEmptyMsg) {
					rollEmptyMsg.hidden = true;
				}


				/*
				 * Поиск рулона.
				 */
				if (rollSearchInput) {
					rollSearchInput.value = '';
				}

				if (rollSearchClear) {
					rollSearchClear.hidden = true;
				}


				/*
				 * Вес расхода.
				 */
				if (weightInput) {
					weightInput.value = '';
					weightInput.removeAttribute('max');
				}
			}
		);
	}


	// =========================================================
	// Начальное состояние
	// =========================================================

	/*
	 * Новый расход открывается без
	 * автоматически выбранного материала.
	 */
	if (materialNameInput) {
		materialNameInput.value = '';
	}

	if (materialIdentifierInput) {
		materialIdentifierInput.value = '';
	}

	resetRoll();
});