/**
 * Инициализация страницы создания производственной линии.
 *
 * Отвечает за:
 * - добавление блока материала;
 * - удаление блока материала;
 * - подстановку характеристик выбранного материала из справочника;
 * - пересчёт порядковых номеров блоков.
 */
import { initSelects } from '../../assets/select.js';

export function initProductionLinesModule() {
	const page = document.querySelector('[data-production-lines-page]');

	// Обработчики удаления нужны на любой странице с производственными линиями.
	document.addEventListener('click', (event) => {
		const deleteButton = event.target.closest('[data-production-line-delete]');

		if (deleteButton) {
			// Кнопка может лежать внутри строки-ссылки — переход не нужен.
			event.preventDefault();

			const lineId = deleteButton.dataset.productionLineDelete;

			if (lineId) {
				window.operationModal.load(
					`/production/lines/${lineId}/delete`,
					'Не удалось загрузить окно удаления производственной линии.'
				);
			}

			return;
		}

		const confirmButton = event.target.closest(
			'[data-production-line-delete-confirm]'
		);

		if (!confirmButton) {
			return;
		}

		void confirmDeleteLine(confirmButton);
	});

	if (!page) {
		return;
	}

	const form = page.querySelector('[data-production-lines-form]');

	// Невыбранные строки не отправляются на сервер.
	form?.addEventListener('submit', () => {
		form.querySelectorAll('.select__value').forEach((input) => {
			if (input.value === '') {
				input.disabled = true;
			}
		});
	});

	// Кнопка «Добавить материал» работает в пределах своей секции.
	page.querySelectorAll('[data-production-line-add-material]').forEach((addButton) => {
		const list = addButton
				.closest('.operation-form__section')
				?.querySelector('[data-production-line-materials]');

		addButton.addEventListener('click', () => {
			addMaterialRow(list);
		});
	});

	page.addEventListener('click', (event) => {
		const removeButton = event.target.closest('[data-production-line-remove-material]');

		if (removeButton) {
			const list = removeButton.closest('[data-production-line-materials]');
			const row = removeButton.closest('[data-production-line-material]');

			if (!list) {
				return;
			}

			if (list.querySelectorAll('[data-production-line-material]').length > 1) {
				row?.remove();
			} else {
				resetRow(row);
			}

			updateIndexes(list);

			return;
		}

		const option = event.target.closest('.select__item[data-value]');

		if (!option) {
			return;
		}

		const row = option.closest('[data-production-line-material]');

		if (row) {
			fillMaterialCells(row, option);
		}
	});

	initSelects(page);
}

/**
 * Окончательно удаляет производственную линию.
 *
 * @param {HTMLElement} button Кнопка подтверждения.
 */
async function confirmDeleteLine(button) {
	const lineId = button.dataset.productionLineId;

	if (!lineId) {
		return;
	}

	button.disabled = true;

	try {
		const csrfToken = document.querySelector(
			'meta[name="csrf-token"]'
		)?.content;

		const response = await fetch(`/production/lines/${lineId}`, {
			method: 'DELETE',
			headers: {
				Accept: 'application/json',
				'X-CSRF-TOKEN': csrfToken,
			},
		});

		const result = await response.json().catch(() => ({}));

		if (!response.ok || !result.success) {
			button.disabled = false;
			return;
		}

		if (result.redirect) {
			window.location.href = result.redirect;
			return;
		}

		window.location.reload();
	} catch (error) {
		button.disabled = false;
	}
}

/**
 * Добавляет новый блок материала по образцу первого.
 *
 * @param {HTMLElement|null} list Контейнер блоков материалов.
 */
function addMaterialRow(list) {
	if (!list) {
		return;
	}

	const template = list.querySelector('[data-production-line-material]');

	if (!template) {
		return;
	}

	const newRow = template.cloneNode(true);

	resetRow(newRow);

	list.appendChild(newRow);

	initSelects(newRow);

	updateIndexes(list);
}

/**
 * Сбрасывает блок материала в исходное состояние.
 *
 * @param {HTMLElement|null} row Блок материала.
 */
function resetRow(row) {
	if (!row) {
		return;
	}

	const valueInput = row.querySelector('.select__value');

	if (valueInput) {
		valueInput.value = '';
	}

	const buttonText = row.querySelector('.select__button-text');

	if (buttonText) {
		buttonText.textContent = 'Выберите материал';
	}

	setCell(row, 'code', '');
	setCell(row, 'identifier', '');
	setCell(row, 'grammage', '');
	setCell(row, 'thickness', '');
	setCell(row, 'format', '');
}

/**
 * Заполняет ячейки характеристик из данных справочника материалов.
 *
 * @param {HTMLElement} row Блок материала.
 * @param {HTMLElement} option Выбранный вариант материала.
 */
function fillMaterialCells(row, option) {
	setCell(row, 'code', option.dataset.code || '—');
	setCell(row, 'identifier', option.dataset.identifier || '—');
	setCell(
			row,
			'grammage',
			option.dataset.grammage ? `${trimZeros(option.dataset.grammage)} гр` : '—'
	);
	setCell(
			row,
			'thickness',
			option.dataset.thickness ? `${option.dataset.thickness} мкм` : '—'
	);
	setCell(row, 'format', option.dataset.format || '—');
}

/**
 * Записывает значение в ячейку характеристики.
 *
 * @param {HTMLElement} row Блок материала.
 * @param {string} name Название характеристики.
 * @param {string} value Значение.
 */
function setCell(row, name, value) {
	const cell = row.querySelector(`[data-cell-${name}]`);

	if (cell) {
		cell.textContent = value;
	}
}

/**
 * Пересчитывает порядковые номера блоков.
 *
 * @param {HTMLElement} list Контейнер блоков материалов.
 */
function updateIndexes(list) {
	list.querySelectorAll('[data-line-index]').forEach((cell, index) => {
		cell.textContent = String(index + 1);
	});
}

/**
 * Убирает лишние нули из числового значения.
 *
 * @param {string} value Числовое значение.
 * @returns {string} Результат.
 */
function trimZeros(value) {
	return value.replace(/\.?0+$/, '');
}
