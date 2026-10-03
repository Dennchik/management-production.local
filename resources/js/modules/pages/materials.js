/**
 * Инициализация страницы справочника материалов.
 *
 * Отвечает за:
 * - открытие формы создания материала;
 * - копирование материала и каталога;
 * - просмотр материала;
 * - открытие формы редактирования;
 * - удаление материала;
 * - сохранение нового материала;
 * - обновление существующего материала;
 * - просмотр, редактирование и удаление каталога;
 * - запоминание раскрытых ветвей каталогов в хранилище браузера;
 * - обновление таблицы без перезагрузки страницы.
 */
import { initSelects } from '../../assets/select.js';

/** Ключ sessionStorage с раскрытыми ветвями каталогов. */
const EXPANDED_CATALOGS_KEY = 'warehouse-expanded-catalogs';

export function initMaterialsModule() {
   // Сохранение и подтверждение удаления материала работают из модалки
   // на любой странице: модалка открывается и вне справочника
   // (создание формата из формы шаблона линии резки).
   document.addEventListener('click', (event) => {
      const formActionButton = event.target.closest(
         '[data-material-form] [data-action]'
      );

      if (formActionButton) {
         const action = formActionButton.dataset.action;

         if (action === 'save') {
            void saveMaterial(formActionButton);
            return;
         }

         if (action === 'update') {
            void updateMaterial(formActionButton);
            return;
         }
      }

      const confirmButton = event.target.closest(
         '[data-material-delete-confirm]'
      );

      if (!confirmButton) {
         return;
      }

      void confirmDeleteMaterial(confirmButton);
   });

   const materialsPage = document.querySelector('[data-materials-page]');

   if (!materialsPage) {
      return;
   }

   const createButton = materialsPage.querySelector('[data-material-create]');

   // Кнопки в строках работают и без кнопки «Создать»:
   // право на создание у роли может отсутствовать.
   createButton?.addEventListener('click', () => {
      void createEntry();
   });

   // Делегирование на контейнере страницы: переживает
   // обновление списка каталогов и материалов.
   materialsPage.addEventListener('click', (event) => {
      // «Раскрыть всё» / «Скрыть всё» — массовое управление ветками.
      const expandAllButton = event.target.closest('[data-catalogs-expand]');
      const collapseAllButton = event.target.closest('[data-catalogs-collapse]');

      if (expandAllButton || collapseAllButton) {
         const expandAll = !!expandAllButton;

         materialsPage
            .querySelectorAll('[data-catalog-toggle]')
            .forEach((row) => {
               row.setAttribute('aria-expanded', expandAll ? 'true' : 'false');
            });

         materialsPage
            .querySelectorAll('.table__branch[data-catalog-branch]')
            .forEach((branch) => {
               branch.hidden = !expandAll;
            });

         markLastWarehouseRow(materialsPage);
         persistExpandedCatalogs();
         return;
      }

      // Кнопки каталога не должны раскрывать/сворачивать ветку.
      const catalogRowButton = event.target.closest('[data-catalog-row-button]');

      if (catalogRowButton) {
         handleCatalogRowAction(event, catalogRowButton);
         return;
      }

      // Строка-каталог: раскрывает/сворачивает свою ветку.
      const catalogToggle = event.target.closest('[data-catalog-toggle]');

      if (catalogToggle) {
         toggleCatalogBranch(catalogToggle);
         return;
      }

      const actionButton = event.target.closest('[data-action]');

      if (!actionButton) {
         return;
      }

      const action = actionButton.dataset.action;

      if (action === 'view') {
         viewMaterial(actionButton);
         return;
      }

      if (action === 'edit') {
         void editMaterial(actionButton);
         return;
      }

      if (action === 'copy') {
         void copyMaterial(actionButton);
         return;
      }

      if (action === 'delete') {
         deleteMaterial(actionButton);
      }
   });

   // Ветка из адреса раскрыта сервером через hidden/aria-expanded,
   // вручную раскрытые ветви восстанавливаются из хранилища браузера.
   restoreExpandedCatalogs();
}

/**
 * Возвращает идентификатор текущего каталога страницы материалов.
 *
 * @returns {string|null} Идентификатор каталога.
 */
function getCurrentCatalogId() {
   return (
      document.querySelector('[data-materials-page]')?.dataset.currentCatalog ||
      null
   );
}

/**
 * Обрабатывает кнопки каталога в строках таблицы склада:
 * просмотр, редактирование, удаление (как на странице каталогов).
 *
 * @param {Event} event Событие клика.
 * @param {HTMLElement} button Кнопка каталога.
 */
function handleCatalogRowAction(event, button) {
   event.stopPropagation();

   const catalogId = button.dataset.catalogId;

   if (!catalogId) {
      return;
   }

   const action = button.dataset.action;

   if (action === 'catalog-view') {
      window.operationModal.load(
         `/catalogs/${catalogId}`,
         'Не удалось загрузить каталог.'
      );
      return;
   }

   if (action === 'catalog-edit') {
      void editCatalogRow(catalogId);
      return;
   }

   if (action === 'catalog-copy') {
      void copyCatalog(catalogId);
      return;
   }

   if (action === 'catalog-delete') {
      window.operationModal.load(
         `/catalogs/${catalogId}/delete`,
         'Не удалось загрузить окно удаления каталога.'
      );
   }
}

/**
 * Открывает форму редактирования каталога из строки склада.
 *
 * @param {string} catalogId Идентификатор каталога.
 */
async function editCatalogRow(catalogId) {
   try {
      const response = await fetch(`/catalogs/${catalogId}/edit`, {
         headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
         },
      });

      if (!response.ok) {
         return;
      }

      window.operationModal.open(await response.text());

      const form = document.querySelector('[data-catalog-form]');

      initSelects(form);

      form?.querySelector('input[name="catalog-name"]')?.focus();
   } catch (error) {
      return;
   }
}

/**
 * Раскрывает или сворачивает ветку каталога в таблице склада.
 * Вложенные ветки лежат внутри родительской, поэтому прячутся
 * вместе с ней — пересчёт видимости не нужен.
 *
 * @param {HTMLElement} row Строка-заголовок каталога.
 */
function toggleCatalogBranch(row) {
   const branch = row.parentElement.querySelector(
      `[data-catalog-branch="${row.dataset.catalogId}"]`
   );

   if (!branch) {
      return;
   }

   const expanded = row.getAttribute('aria-expanded') !== 'false';

   row.setAttribute('aria-expanded', expanded ? 'false' : 'true');
   branch.hidden = expanded;

   markLastWarehouseRow();
   persistExpandedCatalogs();
}

/**
 * Сохраняет раскрытые ветви каталогов в хранилище браузера:
 * позиция дерева переживает обновления таблицы, перезагрузки
 * и возврат с карточки материала — без обращений к серверу.
 */
function persistExpandedCatalogs() {
   const expandedIds = Array.from(
      document.querySelectorAll(
         '[data-materials-page] [data-catalog-toggle][aria-expanded="true"]'
      )
   )
      .map((row) => row.dataset.catalogId)
      .filter(Boolean);

   try {
      sessionStorage.setItem(
         EXPANDED_CATALOGS_KEY,
         JSON.stringify(expandedIds)
      );
   } catch {
      // Хранилище недоступно — позиция дерева просто не запомнится.
   }
}

/**
 * Раскрывает ветви, запомненные в хранилище браузера: сервер
 * отдаёт дерево свёрнутым, кроме ветки из адреса страницы.
 * Несуществующие каталоги (удалённые) пропускаются.
 */
function restoreExpandedCatalogs() {
   const materialsPage = document.querySelector('[data-materials-page]');

   if (!materialsPage) {
      return;
   }

   const wantedIds = new Set(readExpandedCatalogs());

   materialsPage
      .querySelectorAll('[data-catalog-toggle]')
      .forEach((row) => {
         if (!wantedIds.has(row.dataset.catalogId)) {
            return;
         }

         const branch = row.parentElement.querySelector(
            `[data-catalog-branch="${row.dataset.catalogId}"]`
         );

         if (!branch) {
            return;
         }

         row.setAttribute('aria-expanded', 'true');
         branch.hidden = false;
      });

   markLastWarehouseRow(materialsPage);
}

/**
 * Читает раскрытые ветви каталогов из хранилища браузера.
 *
 * @returns {string[]} Идентификаторы каталогов.
 */
function readExpandedCatalogs() {
   try {
      const raw = sessionStorage.getItem(EXPANDED_CATALOGS_KEY);
      const parsed = raw ? JSON.parse(raw) : [];

      return Array.isArray(parsed) ? parsed.map(String) : [];
   } catch {
      return [];
   }
}

/**
 * Показывает ошибку удаления внутри окна подтверждения.
 *
 * @param {string} message Текст ошибки.
 */
function showMaterialDeleteError(message) {
   if (!message) {
      return;
   }

   const modal = document.querySelector('.operation-confirm');

   if (!modal) {
      return;
   }

   let error = modal.querySelector('[data-material-delete-error]');

   if (!error) {
      error = document.createElement('div');
      error.setAttribute('data-material-delete-error', '');
      error.style.color = '#c0392b';
      error.style.paddingTop = '8px';

      const actions = modal.querySelector('.operation-confirm__actions');

      if (actions) {
         modal.insertBefore(error, actions);
      } else {
         modal.appendChild(error);
      }
   }

   error.textContent = message;
}

/**
 * Помечает последнюю видимую строку таблицы склада (_last):
 * у компонента .table :last-child снимает нижний бордер внутри
 * каждой ветки, из-за чего строки раскрытых каталогов посреди
 * списка оставались без разделителя, а низ таблицы не отслеживался.
 *
 * @param {HTMLElement} [container] Контейнер страницы материалов.
 */
function markLastWarehouseRow(container = document) {
   const rows = Array.from(
      container.querySelectorAll('.table--warehouse .table__row-line')
   ).filter((row) => row.getBoundingClientRect().height > 0);

   rows.forEach((row) => row.classList.remove('_last'));

   rows[rows.length - 1]?.classList.add('_last');
}

/**
 * Возвращает признак включённой иерархии каталогов.
 *
 * @returns {boolean} Результат.
 */
function isHierarchyEnabled() {
   return (
      document.querySelector('[data-materials-page]')?.dataset.hierarchy ===
      '1'
   );
}

/**
 * Обрабатывает кнопку «Создать»: при включённой иерархии
 * сначала предлагает выбор между каталогом и материалом.
 */
async function createEntry() {
   if (!isHierarchyEnabled()) {
      await createMaterial();
      return;
   }

   try {
      const response = await fetch('/materials/create-choice', {
         headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
         },
      });

      if (!response.ok) {
         return;
      }

      window.operationModal.open(await response.text());

      const choice = document.querySelector('[data-create-choice]');

      if (!choice) {
         return;
      }

      choice.querySelector('[data-choice-material]')?.addEventListener('click', () => {
         void createMaterial();
      });

      choice.querySelector('[data-choice-catalog]')?.addEventListener('click', () => {
         void createCatalog();
      });
   } catch (error) {
      return;
   }
}

/**
 * Открывает форму создания каталога с родителем = текущий каталог.
 */
async function createCatalog() {
   try {
      const catalogId = getCurrentCatalogId();
      const url = catalogId
         ? `/catalogs/create?parent=${encodeURIComponent(catalogId)}`
         : '/catalogs/create';

      const response = await fetch(url, {
         headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
         },
      });

      if (!response.ok) {
         return;
      }

      window.operationModal.open(await response.text());

      const form = document.querySelector('[data-catalog-form]');

      // Кастомные селекты внутри модалки инициализируются после вставки.
      initSelects(form);

      form?.querySelector('input[name="catalog-name"]')?.focus();
   } catch (error) {
      return;
   }
}

/**
 * Открывает форму создания материала.
 *
 * @param {Object} [extraParams] Дополнительные параметры запроса
 *     (например, {copy_from: id} для копирования материала).
 */
async function createMaterial(extraParams = {}) {
   try {
      const catalogId = getCurrentCatalogId();
      const params = new URLSearchParams(extraParams);

      if (catalogId) {
         params.set('catalog', catalogId);
      }

      const query = params.toString();
      const url = query ? `/materials/create?${query}` : '/materials/create';

      const response = await fetch(url, {
         headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
         },
      });

      if (!response.ok) {
         return;
      }

      const html = await response.text();

      window.operationModal.open(html);

      const form = document.querySelector('[data-material-form]');

      // Кастомные селекты внутри модалки инициализируются после вставки.
      initSelects(form);
      initIdentifierMirror(form);
      initNameMirror(form);

      form?.querySelector('input[name="material-name"]')?.focus();
   } catch (error) {
      return;
   }
}

/**
 * Открывает форму создания материала, заполненную данными
 * копируемого материала.
 *
 * @param {HTMLElement} button Кнопка копирования.
 */
async function copyMaterial(button) {
   const row = button.closest('[data-material-id]');

   if (!row) {
      return;
   }

   const materialId = row.dataset.materialId;

   if (!materialId) {
      return;
   }

   await createMaterial({copy_from: materialId});
}

/**
 * Открывает форму создания каталога, заполненную данными
 * копируемого каталога (имя с суффиксом «(копия)», родитель, статус).
 *
 * @param {string} catalogId Идентификатор каталога-источника.
 */
async function copyCatalog(catalogId) {
   try {
      const response = await fetch(
         `/catalogs/create?copy_from=${encodeURIComponent(catalogId)}`,
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

      const form = document.querySelector('[data-catalog-form]');

      // Кастомные селекты внутри модалки инициализируются после вставки.
      initSelects(form);

      const nameInput = form?.querySelector('input[name="catalog-name"]');

      if (nameInput) {
         nameInput.focus();
         nameInput.select();
      }
   } catch (error) {
      return;
   }
}

/**
 * Сохраняет новый материал.
 *
 * @param {HTMLElement} button Кнопка сохранения.
 */
async function saveMaterial(button) {
   const form = button.closest('[data-material-form]');

   if (!form) {
      return;
   }

   if (!validateMaterialForm(form)) {
      return;
   }

   const data = getMaterialData(form);

   clearMaterialFormError(form);

   button.disabled = true;

   try {
      const csrfToken = document.querySelector(
         'meta[name="csrf-token"]'
      )?.content;

      const response = await fetch('/materials', {
         method: 'POST',
         headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
         },
         body: JSON.stringify(data),
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok || !result.material) {
         showMaterialFormError(form, extractFormError(result));
         button.disabled = false;
         return;
      }

      // Сторонние страницы (форма шаблона линии резки) подхватывают
      // созданный материал по этому событию.
      document.dispatchEvent(
         new CustomEvent('material:saved', {detail: {material: result.material}})
      );

      const refreshed = await refreshMaterialsTable();

      if (!refreshed) {
         button.disabled = false;
         return;
      }

      window.operationModal.close();
   } catch (error) {
      button.disabled = false;
   }
}

/**
 * Открывает форму редактирования материала.
 *
 * @param {HTMLElement} button Кнопка редактирования.
 */
async function editMaterial(button) {
   const row = button.closest('[data-material-id]');

   if (!row) {
      return;
   }

   const materialId = row.dataset.materialId;

   if (!materialId) {
      return;
   }

   try {
      const response = await fetch(`/materials/${materialId}/edit`, {
         headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
         },
      });

      if (!response.ok) {
         return;
      }

      const html = await response.text();

      window.operationModal.open(html);

      const form = document.querySelector('[data-material-form]');

      initSelects(form);
      initIdentifierMirror(form);
      initNameMirror(form);

      form?.querySelector('input[name="material-name"]')?.focus();
   } catch (error) {
      return;
   }
}

/**
 * Обновляет существующий материал.
 *
 * @param {HTMLElement} button Кнопка обновления.
 */
async function updateMaterial(button) {
   const form = button.closest('[data-material-form]');

   if (!form) {
      return;
   }

   const materialId = form.dataset.materialId;

   if (!materialId) {
      return;
   }

   if (!validateMaterialForm(form)) {
      return;
   }

   const data = getMaterialData(form);

   clearMaterialFormError(form);

   button.disabled = true;

   try {
      const csrfToken = document.querySelector(
         'meta[name="csrf-token"]'
      )?.content;

      const response = await fetch(`/materials/${materialId}`, {
         method: 'PUT',
         headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
         },
         body: JSON.stringify(data),
      });

      const result = await response.json().catch(() => ({}));

      if (!response.ok || !result.material) {
         showMaterialFormError(form, extractFormError(result));
         button.disabled = false;
         return;
      }

      document.dispatchEvent(
         new CustomEvent('material:updated', {detail: {material: result.material}})
      );

      const refreshed = await refreshMaterialsTable();

      if (!refreshed) {
         button.disabled = false;
         return;
      }

      window.operationModal.close();
   } catch (error) {
      button.disabled = false;
   }
}

/**
 * Обновляет тело таблицы данными, сформированными Blade.
 *
 * HTML-разметка строки и пустого состояния находится только в Blade.
 *
 * @returns {Promise<boolean>} Результат обновления.
 */
async function refreshMaterialsTable() {
   const tableBody = document.querySelector('[data-materials-body]');

   if (!tableBody) {
      // Таблицы склада на текущей странице нет — обновлять нечего.
      return true;
   }

   // Объединённая страница материалов живёт на складе:
   // перезагружаем текущий адрес со всеми активными фильтрами.
   const url = window.location.pathname + window.location.search;

   try {
      const response = await fetch(url, {
         headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
         },
      });

      if (!response.ok) {
         return false;
      }

      const html = await response.text();
      const parsedDocument = new DOMParser().parseFromString(html, 'text/html');

      const newTableBody = parsedDocument.querySelector(
         '[data-materials-body]'
      );

      if (!newTableBody) {
         return false;
      }

      tableBody.replaceChildren(...Array.from(newTableBody.childNodes));

      // Сервер отдал дерево свёрнутым — раскрытые ветви
      // восстанавливаются из хранилища браузера.
      restoreExpandedCatalogs();

      return true;
   } catch (error) {
      return false;
   }
}

/**
 * Автосборка названия по шаблону (формы создания и редактирования материала):
 * «|» в шаблоне — разделитель, попадает в итоговое название.
 * Одно поле «Название»: набранный текст считается основой и при
 * вводе грамматуры/толщины/формата дополняется по шаблону.
 *
 * @param {HTMLElement} form Контейнер формы материала.
 */
function initNameMirror(form) {
   const nameInput = form?.querySelector("#material-name");
   const templateInput = form?.querySelector("#name-template");

   if (!nameInput || !templateInput) {
      return;
   }

   const trimZeros = (value) =>
      String(value).replace(/(\.\d*?)0+$/, "\$1").replace(/\.$/, "");

   const values = () => {
      const grammage = trimZeros(form.querySelector("input[name=\"grammage\"]")?.value ?? "");
      const thickness = trimZeros(form.querySelector("input[name=\"thickness\"]")?.value ?? "");

      return {
         название: base,
         грамматура: grammage !== "" ? `${grammage} гр` : "",
         толщина: thickness !== "" ? `${thickness} мкм` : "",
         формат: form.querySelector("input[name=\"format\"]")?.value.trim() ?? "",
      };
   };

   /*
    * Собирает название по шаблону: токены заменяются значениями,
    * разделители между токенами сохраняются; пустые токены выпадают
    * вместе с примыкающими разделителями.
    */
   const compose = () => {
      const tokenValues = values();
      const parts = templateInput.value.split(/(название|грамматура|толщина|формат)/i);

      let result = "";
      let pendingSeparator = "";

      parts.forEach((part) => {
         const token = part.trim().toLowerCase();

         if (token === "название" || token === "грамматура" || token === "толщина" || token === "формат") {
            const value = tokenValues[token] ?? "";

            if (value !== "") {
               result += pendingSeparator + value;
               pendingSeparator = "";
            } else {
               // Пустой токен выпадает вместе с разделителем перед ним
               pendingSeparator = "";
            }
         } else {
            pendingSeparator += part;
         }
      });

      // Без ведущих и хвостовых разделителей (крайние токены пусты)
      return result.trim().replace(/^[|\s]+/, '').replace(/[|\s]+$/, '');
   };

   /*
    * Рендеры токенов (грамматура/толщина/формат) как они попадают
    * в собранное название. Сюда же включаются «голые» числа:
    * основа названия могла быть набрана вместе с ними.
    */
   const escape = (part) => part.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

   const tokenRenders = () => {
      const grammage = trimZeros(form.querySelector("input[name=\"grammage\"]")?.value ?? "");
      const thickness = trimZeros(form.querySelector("input[name=\"thickness\"]")?.value ?? "");
      const format = (form.querySelector("input[name=\"format\"]")?.value ?? "").trim();

      const renders = [];

      if (format !== "") {
         renders.push(escape(format));
      }

      if (thickness !== "") {
         renders.push(`${escape(thickness)}\\s*мкм\\.?`);
      }

      if (grammage !== "") {
         renders.push(`${escape(grammage)}\\s*гр\\.?`);
         renders.push(escape(grammage));
      }

      return renders;
   };

   // Рендеры последней сборки: при смене характеристики в поле уже
   // новое значение, а срезать из названия нужно ещё и старое
   let lastRenders = tokenRenders();

   /*
    * Отрезает от набранного названия уже собранные токены (грамматура,
    * толщина, формат): основа не должна содержать их, иначе пересборка
    * по шаблону добавит каждый токен второй раз.
    */
   const stripComposedTokens = (value) => {
      const patterns = [...tokenRenders(), ...lastRenders];

      if (patterns.length === 0) {
         return value.trim();
      }

      // Токены в конце (шаблон «Название | …»)
      const strippedEnd = cutTokens(value.trim(), patterns, "end");

      if (strippedEnd !== "") {
         return strippedEnd;
      }

      // Токены в начале (шаблон «… | Название») — без голого числа
      return cutTokens(value.trim(), patterns.slice(0, 3), "start");

      /*
       * Срезает токены с указанного края; вместе с токеном выпадают
       * примыкающие разделители шаблона («|», пробелы, дефисы).
       */
      function cutTokens(text, tokenPatterns, side) {
         let result = text;
         let cut = false;

         for (const pattern of tokenPatterns) {
            const regex = side === "end"
               ? new RegExp(`[\\s|]*${pattern}[^\\p{L}\\p{N}]*$`, "iu")
               : new RegExp(`^[^\\p{L}\\p{N}]*[\\s|]*${pattern}`, "iu");

            const stripped = result.replace(regex, "").trim();

            if (stripped !== result) {
               result = stripped;
               cut = true;
            }
         }

         if (cut) {
            result = side === "end"
               ? result.replace(/[^\p{L}\p{N}]+$/u, "").trim()
               : result.replace(/^[^\p{L}\p{N}]+/u, "").trim();
         }

         return result;
      }
   };

   let base = "";
   let lastComposed = "";
   let manual = nameInput.value !== "";

   const apply = () => {
      const composed = compose();

      if (composed !== "") {
         nameInput.value = composed;
         lastComposed = composed;
         lastRenders = tokenRenders();
      }
   };

   // Пользователь печатает основу названия
   nameInput.addEventListener("input", () => {
      base = stripComposedTokens(nameInput.value);
      manual = true;
   });

   // Уход из поля названия: если имя не правили вручную,
   // основой остаётся прежняя база, и состав применяется
   nameInput.addEventListener("blur", () => {
      if (!manual && base !== "") {
         apply();
      }
   });

   // Характеристики дополняют название по шаблону
   for (const field of ["grammage", "thickness", "format"]) {
      form
         .querySelector(`input[name="${field}"]`)
         ?.addEventListener("input", () => {
            if (manual) {
               base = stripComposedTokens(nameInput.value);
            }

            manual = false;
            apply();
         });
   }

   // Правка шаблона пересобирает название сразу
   templateInput.addEventListener("input", () => {
      manual = false;
      apply();
   });
}
/**
 * Живой пересчёт идентификатора в форме материала:
 * код + грамматура/толщина + цифры формата.
 * Зеркалит Material::composeIdentifier() на стороне PHP.
 *
 * @param {HTMLElement} form Контейнер формы материала.
 */
export function initIdentifierMirror(form) {
   const identifierInput = form?.querySelector('#identifier');

   if (!identifierInput) {
      return;
   }

   const updateIdentifier = () => {
      identifierInput.value = composeMaterialIdentifier(form);
   };

   for (const field of ['code', 'grammage', 'thickness', 'format']) {
      form
         .querySelector(`input[name="${field}"]`)
         ?.addEventListener('input', updateIdentifier);
   }

   updateIdentifier();
}

/**
 * Вычисляет идентификатор из полей формы материала.
 *
 * @param {HTMLElement} form Контейнер формы материала.
 * @returns {string} Идентификатор.
 */
function composeMaterialIdentifier(form) {
   const code = form.querySelector('input[name="code"]')?.value.trim() || '';
   const grammage = form.querySelector('input[name="grammage"]')?.value || '';
   const thickness = form.querySelector('input[name="thickness"]')?.value || '';
   const format = form.querySelector('input[name="format"]')?.value || '';

   const value = grammage || thickness;
   const formatPart = format.replace(/\D/g, '');

   const parsed = parseFloat(value);
   const valuePart = Number.isFinite(parsed)
      ? String(parsed)
           .replace('.', '')
           .replace(/^0+/, '')
           .padStart(2, '0')
      : '';

   if (!code || !valuePart || !formatPart) {
      return '';
   }

   return code + valuePart + formatPart;
}

/**
 * Собирает данные материала из формы.
 *
 * @param {HTMLElement} form Контейнер формы материала.
 * @returns {Object} Данные материала.
 */
function getMaterialData(form) {
   return {
      name:
         form.querySelector('input[name="material-name"]')?.value.trim() || '',

      name_template:
         form.querySelector('#name-template')?.value.trim() || null,

      code: form.querySelector('input[name="code"]')?.value.trim() || '',

      grammage: form.querySelector('input[name="grammage"]')?.value || null,

      thickness: form.querySelector('input[name="thickness"]')?.value || null,

      format: form.querySelector('input[name="format"]')?.value.trim() || null,

      catalog_id: form.querySelector('[name="catalog_id"]')?.value || null,

      allowed_operations: Array.from(
         form.querySelectorAll('input[name="allowed_operations[]"]:checked')
      ).map((input) => input.value),

      is_active:
         form.querySelector('input[name="is_active"]')?.checked ?? false,
   };
}

/**
 * Извлекает текст ошибки из ответа сервера: сначала валидационные
 * ошибки (result.errors), затем общее сообщение (result.message).
 *
 * @param {Object} result Разобранный JSON-ответ.
 * @returns {string} Текст ошибки.
 */
function extractFormError(result) {
   const errors = result?.errors;

   if (errors && typeof errors === 'object') {
      const first = Object.values(errors).find(
         (messages) => Array.isArray(messages) && messages.length > 0
      );

      if (first) {
         return first[0];
      }
   }

   return result?.message || 'Не удалось сохранить. Попробуйте ещё раз.';
}

/**
 * Показывает ошибку сохранения внутри формы материала.
 *
 * @param {HTMLElement} form Контейнер формы материала.
 * @param {string} message Текст ошибки.
 */
function showMaterialFormError(form, message) {
   if (!form || !message) {
      return;
   }

   let error = form.querySelector('[data-material-form-error]');

   if (!error) {
      error = document.createElement('div');
      error.setAttribute('data-material-form-error', '');
      error.style.color = '#c0392b';
      error.style.paddingTop = '8px';

      const actions = form.querySelector('.material-show__actions');

      if (actions) {
         form.insertBefore(error, actions);
      } else {
         form.appendChild(error);
      }
   }

   error.textContent = message;
}

/**
 * Убирает показанную ошибку сохранения из формы материала.
 *
 * @param {HTMLElement} form Контейнер формы материала.
 */
function clearMaterialFormError(form) {
   form?.querySelector('[data-material-form-error]')?.remove();
}

/**
 * Проверяет поля материала средствами браузера.
 *
 * @param {HTMLElement} form Контейнер формы материала.
 * @returns {boolean} Результат проверки.
 */
function validateMaterialForm(form) {
   const fields = form.querySelectorAll('input, select, textarea');

   for (const field of fields) {
      if (!field.checkValidity()) {
         field.reportValidity();

         return false;
      }
   }

   return true;
}

/**
 * Открывает просмотр материала.
 *
 * @param {HTMLElement} button Кнопка просмотра.
 */
function viewMaterial(button) {
   const row = button.closest('[data-material-id]');

   if (!row) {
      return;
   }

   const materialId = row.dataset.materialId;

   if (!materialId) {
      return;
   }

   window.operationModal.load(
      `/materials/${materialId}`,
      'Не удалось загрузить материал.'
   );
}

/**
 * Открывает подтверждение удаления материала.
 *
 * @param {HTMLElement} button Кнопка удаления.
 */
function deleteMaterial(button) {
   const row = button.closest('[data-material-id]');

   if (!row) {
      return;
   }

   const materialId = row.dataset.materialId;

   if (!materialId) {
      return;
   }

   window.operationModal.load(
      `/materials/${materialId}/delete`,
      'Не удалось загрузить окно удаления материала.'
   );
}

/**
 * Окончательно удаляет материал.
 *
 * @param {HTMLElement} button Кнопка подтверждения.
 */
async function confirmDeleteMaterial(button) {
   const materialId = button.dataset.materialId;

   if (!materialId) {
      return;
   }

   button.disabled = true;

   try {
      const csrfToken = document.querySelector(
         'meta[name="csrf-token"]'
      )?.content;

      const response = await fetch(`/materials/${materialId}`, {
         method: 'DELETE',
         headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
         },
      });

      const result = await response.json();

      if (!response.ok || !result.success) {
         showMaterialDeleteError(result.message);
         button.disabled = false;
         return;
      }

      const refreshed = await refreshMaterialsTable();

      if (!refreshed) {
         button.disabled = false;
         return;
      }

      window.operationModal.close();
   } catch (error) {
      button.disabled = false;
   }
}
