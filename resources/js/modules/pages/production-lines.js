/**
 * Инициализация страницы создания производственной линии.
 *
 * Отвечает за:
 * - добавление блока материала;
 * - удаление блока материала;
 * - подстановку характеристик выбранного материала из справочника;
 * - пересчёт порядковых номеров блоков;
 * - настройку разрешённых операций шаблона по каталогам.
 */
import { initSelects } from '../../assets/select.js';

// Подтверждаемое изменение статуса каталога.
let pendingToggle = null;

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

		if (confirmButton) {
			void confirmDeleteLine(confirmButton);

			return;
		}

		const allowedApplyButton = event.target.closest('[data-allowed-catalogs-confirm-apply]');

		if (allowedApplyButton) {
			void confirmAllowedToggle(allowedApplyButton);
		}
	});

	document.addEventListener('change', (event) => {
		const materialCheckbox = event.target.closest('[data-allowed-material]');

		if (materialCheckbox) {
			// Изменение отдельного материала применяется сразу, без каскада.
			void applyAllowedToggle(
					{
						materialId: materialCheckbox.dataset.materialId,
						allowed: materialCheckbox.checked,
					},
					materialCheckbox
			);

			return;
		}

		const checkbox = event.target.closest('[data-allowed-catalog]');

		if (!checkbox) {
			return;
		}

		// Галочка возвращается к серверному состоянию до подтверждения изменения.
		const allowed = checkbox.checked;

		checkbox.checked = !allowed;

		pendingToggle = {
			checkbox,
			catalogId: checkbox.dataset.catalogId,
			allowed,
		};

		openAllowedConfirmModal(
				checkbox.dataset.catalogName || '',
				checkbox.dataset.materialsCount || '0',
				allowed
		);
	});

	// Частично выбранные каталоги отображаются «наполовину» отмеченными.
	syncAllowedCatalogsStates();

	if (!page) {
		return;
	}

	const form = page.querySelector('[data-production-lines-form]');

	// Невыбранные строки не отправляются на сервер
	// (материал и формат отключаются парой, чтобы индексы совпали).
	form?.addEventListener('submit', () => {
		form.querySelectorAll('[data-production-line-material]').forEach((row) => {
			const valueInput = row.querySelector('.select__value');

			if (valueInput && valueInput.value === '') {
				valueInput.disabled = true;

				const formatInput = row.querySelector('.select__format-value');

				if (formatInput) {
					formatInput.disabled = true;
				}
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

		// Название линии подставляется из выбранного выходного материала.
		if (option.closest('[data-autofill-line-name]')) {
			autofillLineName(option.dataset.name || '');
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
 * Подставляет название выходного материала в название линии.
 *
 * @param {string} name Название материала.
 */
function autofillLineName(name) {
	if (!name) {
		return;
	}

	const nameInput = document.querySelector('[data-production-lines-form] input[name="name"]');

	if (nameInput) {
		nameInput.value = name;
	}
}

/**
 * Выставляет «наполовину» отмеченные каталоги (частично разрешённые).
 */
function syncAllowedCatalogsStates() {
	document.querySelectorAll('[data-allowed-catalog]').forEach((checkbox) => {
		checkbox.indeterminate = checkbox.dataset.state === 'partial';
	});
}

/**
 * Перезагружает таблицу каталогов страницы без перезагрузки окна.
 *
 * @returns {Promise<boolean>} Признак успешного обновления.
 */
async function refreshAllowedCatalogsTable() {
	const body = document.querySelector('[data-allowed-catalogs-body]');

	if (!body) {
		return false;
	}

	const response = await fetch(window.location.href, {
		headers: {
			'X-Requested-With': 'XMLHttpRequest',
			Accept: 'text/html',
		},
	});

	if (!response.ok) {
		return false;
	}

	const parsedDocument = new DOMParser().parseFromString(
			await response.text(),
			'text/html'
	);

	const newBody = parsedDocument.querySelector('[data-allowed-catalogs-body]');

	if (!newBody) {
		return false;
	}

	body.replaceChildren(...Array.from(newBody.childNodes));

	syncAllowedCatalogsStates();

	return true;
}

/**
 * Открывает универсальное окно подтверждения каскадного изменения каталога.
 *
 * @param {string} name Название каталога.
 * @param {string} count Количество материалов поддерева.
 * @param {boolean} allowed Целевое состояние разрешения.
 */
function openAllowedConfirmModal(name, count, allowed) {
	const action = allowed ? 'разрешена' : 'запрещена';

	const wrapper = document.createElement('div');

	wrapper.className = 'operation-confirm';
	wrapper.innerHTML = `
		<div class="operation-confirm__header">
			<h2 class="operation-confirm__title">Подтверждение изменения</h2>
		</div>
		<div class="operation-confirm__text"></div>
		<div class="operation-confirm__actions">
			<button type="button" class="button button--secondary" data-operation-modal-close>
				<span>Отмена</span>
			</button>
			<button type="button" class="button button--primary" data-allowed-catalogs-confirm-apply>
				<span>Применить</span>
			</button>
		</div>`;

	wrapper.querySelector('.operation-confirm__text').textContent =
		`Изменение будет применено ко всем вложенным подкаталогам и материалам каталога «${name}» (материалов: ${count}). Операция будет ${action} для всех. Продолжить?`;

	window.operationModal?.open(wrapper.outerHTML);
}

/**
 * Применяет подтверждённое в окне изменение статуса каталога.
 *
 * @param {HTMLElement} button Кнопка «Применить».
 */
async function confirmAllowedToggle(button) {
	if (!pendingToggle) {
		window.operationModal?.close();

		return;
	}

	const {checkbox, catalogId, allowed} = pendingToggle;

	pendingToggle = null;
	button.disabled = true;

	await applyAllowedToggle({catalogId, allowed}, checkbox);

	window.operationModal?.close();
}

/**
 * Применяет изменение статуса разрешения каталога или материала.
 *
 * @param {{catalogId?: string, materialId?: string, allowed: boolean}} payload Данные изменения.
 * @param {HTMLInputElement} checkbox Переключённый чекбокс.
 */
async function applyAllowedToggle(payload, checkbox) {
	const updateUrl = document.querySelector('[data-allowed-catalogs-page]')?.dataset.allowedCatalogsUpdate;

	if (!updateUrl) {
		return;
	}

	const body = {allowed: payload.allowed};

	if (payload.catalogId) {
		body.catalog_id = payload.catalogId;
	} else {
		body.material_id = payload.materialId;
	}

	try {
		const csrfToken = document.querySelector(
			'meta[name="csrf-token"]'
		)?.content;

		const response = await fetch(updateUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				Accept: 'application/json',
				'X-CSRF-TOKEN': csrfToken,
			},
			body: JSON.stringify(body),
		});

		const result = await response.json().catch(() => ({}));

		if (!response.ok || !result.success) {
			checkbox.checked = !payload.allowed;

			return;
		}

		// Таблица перезагружается, чтобы обновить статусы всех каталогов.
		const refreshed = await refreshAllowedCatalogsTable();

		if (!refreshed) {
			window.location.reload();
		}
	} catch (error) {
		checkbox.checked = !payload.allowed;
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

	const formatInput = row.querySelector('.select__format-value');

	if (formatInput) {
		formatInput.value = '';
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
	const formatInput = row.querySelector('.select__format-value');

	if (formatInput) {
		formatInput.value = option.dataset.format || '';
	}

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
