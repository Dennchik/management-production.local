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
import { initSelects, prepareClonedSelects } from '../../assets/select.js';
import { initIdentifierMirror } from './materials.js';

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

	// Невыбранные строки не отправляются на сервер.
	form?.addEventListener('submit', () => {
		form.querySelectorAll('[data-production-line-material]').forEach((row) => {
			const valueInput = row.querySelector('.select__value');

			if (valueInput && valueInput.value === '') {
				valueInput.disabled = true;
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

	// Слушатели режима резки нужны на любой форме линий: в форме
	// задачи режим включается выбором линии, а не атрибутом страницы.
	if (form) {
		initCuttingListeners(page, form);
		setCuttingActive(form, page.hasAttribute('data-cutting-mode'));
	}

	initSelects(page);
}

/**
 * Включает или выключает режим резки на форме состава материалов.
 *
 * Резка: на входе одна строка, на выходе — несколько форматов
 * того же материала; кнопка «Добавить формат» открывает создание
 * материала, заполненного данными входа.
 *
 * @param {HTMLElement} form Форма с составом материалов.
 * @param {boolean} active Признак режима резки.
 */
export function setCuttingActive(form, active) {
	if (active) {
		form.dataset.cuttingActive = '1';
	} else {
		delete form.dataset.cuttingActive;
	}

	form.querySelectorAll('[data-production-line-add-material], [data-production-line-add-format]')
		.forEach((button) => {
			const section = button.closest('.operation-form__section');
			const isOutput = !!section?.querySelector('.select__value[name^="output_materials"]');

			button.hidden = isOutput ? !active : active;
		});

	if (active) {
		limitCuttingInputRows(form);
	}

	applyCuttingOutputFilter(form);
}

/**
 * Применяет фильтр форматов к выбору выхода — актуален в режиме резки.
 *
 * @param {HTMLElement} form Форма с составом материалов.
 */
export function applyCuttingOutputFilter(form) {
	const input = form.dataset.cuttingActive === '1' ? cuttingInputOption(form) : null;

	form.querySelectorAll('.select__value[name="output_materials[]"]').forEach((valueInput) => {
		valueInput
			.closest('.select')
			?.querySelectorAll('.select__item[data-value]')
			.forEach((option) => {
				const selected = option.dataset.value === valueInput.value;
				const visible = input === null
					|| selected
					|| (option.dataset.value !== input.dataset.value && isSameMaterialFamily(option, input));

				option.hidden = !visible;
			});
	});
}

/**
 * Оставляет на входе первую строку: в режиме резки материал один.
 *
 * @param {HTMLElement} form Форма с составом материалов.
 */
function limitCuttingInputRows(form) {
	const rows = Array.from(form.querySelectorAll('[data-production-line-material]'))
		.filter((row) => row.querySelector('.select__value[name="materials[]"]'));

	if (rows.length <= 1) {
		return;
	}

	const list = rows[0].closest('[data-production-line-materials]');

	rows.slice(1).forEach((row) => row.remove());

	if (list) {
		updateIndexes(list);
	}
}

/**
 * Привязывает события режима резки к странице формы.
 *
 * Слушатель страницы — после существующих обработчиков:
 * материал входа уже выбран или сброшен.
 *
 * @param {HTMLElement} page Контейнер страницы.
 * @param {HTMLElement} form Форма производственной линии.
 */
function initCuttingListeners(page, form) {
	page.addEventListener('click', (event) => {
		const option = event.target.closest('.select__item[data-value]');
		const row = option?.closest('[data-production-line-material]');

		if (row && row.querySelector('.select__value')?.name === 'materials[]') {
			applyCuttingOutputFilter(form);

			return;
		}

		if (event.target.closest('[data-production-line-remove-material]')) {
			applyCuttingOutputFilter(form);
		}
	});

	page.querySelectorAll('[data-production-line-add-format]').forEach((button) => {
		button.addEventListener('click', () => {
			void openAddFormatModal(form);
		});
	});

	document.addEventListener('material:saved', (event) => {
		if (form.dataset.cuttingActive !== '1') {
			return;
		}

		const material = event.detail?.material;

		if (material) {
			addOutputFormatOption(form, material);
		}
	});
}

/**
 * Возвращает выбранную опцию материала входа.
 *
 * @param {HTMLElement} form Форма производственной линии.
 * @returns {HTMLElement|null} Опция входа.
 */
function cuttingInputOption(form) {
	const valueInput = form.querySelector('.select__value[name="materials[]"]');

	if (!valueInput || valueInput.value === '') {
		return null;
	}

	return valueInput
		.closest('.select')
		?.querySelector(`.select__item[data-value="${valueInput.value}"]`) ?? null;
}

/**
 * Тот же материал: совпадают код, грамматура и толщина.
 *
 * @param {HTMLElement} option Проверяемая опция выхода.
 * @param {HTMLElement} input Опция входа.
 * @returns {boolean} Признак совпадения.
 */
function isSameMaterialFamily(option, input) {
	const number = (value) => {
		const parsed = parseFloat(value);

		return Number.isFinite(parsed) ? String(parsed) : '';
	};

	return option.dataset.code === input.dataset.code
		&& number(option.dataset.grammage) === number(input.dataset.grammage)
		&& number(option.dataset.thickness) === number(input.dataset.thickness);
}

/**
 * Открывает форму создания материала, заполненную данными материала
 * входа: заполняется только формат.
 *
 * @param {HTMLElement} form Форма производственной линии.
 */
async function openAddFormatModal(form) {
	const input = cuttingInputOption(form);

	if (!input) {
		return;
	}

	try {
		const response = await fetch(
			`/materials/create?prefill_from=${encodeURIComponent(input.dataset.value)}`,
			{
				headers: {
					'X-Requested-With': 'XMLHttpRequest',
					Accept: 'text/html',
				},
			}
		);

		if (!response.ok) {
			return;
		}

		window.operationModal.open(await response.text());

		const materialForm = document.querySelector('[data-material-form]');

		initSelects(materialForm);
		initIdentifierMirror(materialForm);
		materialForm?.querySelector('#material-format')?.focus();
	} catch (error) {
		return;
	}
}

/**
 * Добавляет созданный формат в выбор выхода и выбирает его:
 * занимает пустую строку, а при отсутствии — добавляет новую.
 *
 * @param {HTMLElement} form Форма производственной линии.
 * @param {Object} material Созданный материал.
 */
function addOutputFormatOption(form, material) {
	const valueInputs = Array.from(form.querySelectorAll('.select__value[name="output_materials[]"]'));

	if (valueInputs.length === 0) {
		return;
	}

	// Строка без выбранного материала занимает новый формат.
	let valueInput = valueInputs.find((input) => input.value === '');

	if (!valueInput) {
		const list = valueInputs[0].closest('[data-production-line-materials]');

		addMaterialRow(list);

		valueInput = Array.from(list.querySelectorAll('.select__value[name="output_materials[]"]'))
			.find((input) => input.value === '');

		if (!valueInput) {
			return;
		}
	}

	const select = valueInput.closest('.select');
	const row = valueInput.closest('[data-production-line-material]');

	if (!select || !row) {
		return;
	}

	let option = select.querySelector(`.select__item[data-value="${material.id}"]`);

	if (!option) {
		option = document.createElement('button');
		option.type = 'button';
		option.className = 'material-select__select-option select__item';
		option.setAttribute('role', 'option');
		option.dataset.value = String(material.id);
		option.dataset.name = material.name || '';
		option.dataset.identifier = material.identifier || '';
		option.dataset.code = material.code || '';
		option.dataset.grammage = material.grammage ?? '';
		option.dataset.thickness = material.thickness ?? '';
		option.dataset.format = material.format ?? '';

		const label = document.createElement('span');

		label.textContent = [
			material.name,
			material.grammage ? `${material.grammage} гр` : '',
			material.thickness ? `${material.thickness} мкм` : '',
			material.format ? String(material.format) : '',
		].filter(Boolean).join(' | ');
		option.appendChild(label);

		const dropdown = select.querySelector('.select__dropdown');

		dropdown?.insertBefore(option, dropdown.querySelector('.select__empty') ?? null);
	}

	const instance = (window._activeSelects || []).find(
		(selectInstance) => selectInstance.container === select
	);

	instance?.refresh();
	instance?.selectOption(option, false);

	fillMaterialCells(row, option);

	if (select.closest('[data-autofill-line-name]')) {
		autofillLineName(material.name || '');
	}

	applyCuttingOutputFilter(form);
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

	prepareClonedSelects(newRow);

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
