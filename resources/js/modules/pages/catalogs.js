/**
 * Инициализация страницы каталогов.
 *
 * Отвечает за:
 * - переключение иерархии каталогов;
 * - открытие формы создания каталога;
 * - просмотр каталога;
 * - открытие формы редактирования;
 * - удаление каталога;
 * - сохранение и обновление каталога;
 * - обновление таблицы без перезагрузки страницы.
 */
import { initSelects } from '../../assets/select.js';

export function initCatalogsModule() {
   const catalogsPage = document.querySelector('[data-catalogs-page]');

   // Обработчик форм каталога нужен на любой странице
   // (форма создания каталога открывается и со страницы материалов).
   document.addEventListener('click', (event) => {
      const formActionButton = event.target.closest(
         '[data-catalog-form] [data-action]'
      );

      if (formActionButton) {
         const action = formActionButton.dataset.action;

         if (action === 'save') {
            void saveCatalog(formActionButton);
            return;
         }

         if (action === 'update') {
            void updateCatalog(formActionButton);
            return;
         }
      }

      const confirmButton = event.target.closest(
         '[data-catalog-delete-confirm]'
      );

      if (!confirmButton) {
         return;
      }

      void confirmDeleteCatalog(confirmButton);
   });

   if (!catalogsPage) {
      return;
   }

   const createButton = catalogsPage.querySelector('[data-catalog-create]');
   const tableBody = catalogsPage.querySelector('[data-catalogs-body]');
   const hierarchyToggle = catalogsPage.querySelector(
      'input[name="hierarchy_enabled"]'
   );

   createButton?.addEventListener('click', () => {
      void createCatalog();
   });

   hierarchyToggle?.addEventListener('change', () => {
      void toggleHierarchy(hierarchyToggle);
   });

   tableBody?.addEventListener('click', (event) => {
      const actionButton = event.target.closest('[data-action]');

      if (!actionButton) {
         return;
      }

      const action = actionButton.dataset.action;

      if (action === 'view') {
         viewCatalog(actionButton);
         return;
      }

      if (action === 'edit') {
         void editCatalog(actionButton);
         return;
      }

      if (action === 'delete') {
         deleteCatalog(actionButton);
      }
   });
}

/**
 * Переключает иерархию каталогов и перезагружает страницу.
 *
 * @param {HTMLInputElement} toggle Переключатель иерархии.
 */
async function toggleHierarchy(toggle) {
   const previousValue = !toggle.checked;

   toggle.disabled = true;

   try {
      const csrfToken = document.querySelector(
         'meta[name="csrf-token"]'
      )?.content;

      const response = await fetch('/catalogs/hierarchy-toggle', {
         method: 'POST',
         headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
         },
      });

      const result = await response.json();

      if (!response.ok || !result.success) {
         toggle.checked = previousValue;
         toggle.disabled = false;
         return;
      }

      window.location.reload();
   } catch (error) {
      toggle.checked = previousValue;
      toggle.disabled = false;
   }
}

/**
 * Обновляет данные после сохранения каталога: на странице
 * каталогов — таблицу, на странице материалов — всю страницу.
 *
 * @returns {Promise<boolean>} Результат обновления.
 */
async function refreshAfterCatalogChange() {
   if (document.querySelector('[data-materials-page]')) {
      window.location.reload();
      return true;
   }

   return refreshCatalogsTable();
}

/**
 * Открывает форму создания каталога.
 */
async function createCatalog() {
   try {
      const response = await fetch('/catalogs/create', {
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

      const form = document.querySelector('[data-catalog-form]');

      // Кастомные селекты внутри модалки инициализируются после вставки.
      initSelects(form);

      form?.querySelector('input[name="catalog-name"]')?.focus();
   } catch (error) {
      return;
   }
}

/**
 * Сохраняет новый каталог.
 *
 * @param {HTMLElement} button Кнопка сохранения.
 */
async function saveCatalog(button) {
   const form = button.closest('[data-catalog-form]');

   if (!form) {
      return;
   }

   if (!validateCatalogForm(form)) {
      return;
   }

   button.disabled = true;

   try {
      const csrfToken = document.querySelector(
         'meta[name="csrf-token"]'
      )?.content;

      const response = await fetch('/catalogs', {
         method: 'POST',
         headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
         },
         body: JSON.stringify(getCatalogData(form)),
      });

      const result = await response.json();

      if (!response.ok || !result.catalog) {
         button.disabled = false;
         return;
      }

      const refreshed = await refreshAfterCatalogChange();

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
 * Открывает форму редактирования каталога.
 *
 * @param {HTMLElement} button Кнопка редактирования.
 */
async function editCatalog(button) {
   const row = button.closest('[data-catalog-id]');

   if (!row) {
      return;
   }

   const catalogId = row.dataset.catalogId;

   if (!catalogId) {
      return;
   }

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

      const html = await response.text();

      window.operationModal.open(html);

      const form = document.querySelector('[data-catalog-form]');

      initSelects(form);

      form?.querySelector('input[name="catalog-name"]')?.focus();
   } catch (error) {
      return;
   }
}

/**
 * Обновляет существующий каталог.
 *
 * @param {HTMLElement} button Кнопка обновления.
 */
async function updateCatalog(button) {
   const form = button.closest('[data-catalog-form]');

   if (!form) {
      return;
   }

   const catalogId = form.dataset.catalogId;

   if (!catalogId) {
      return;
   }

   if (!validateCatalogForm(form)) {
      return;
   }

   button.disabled = true;

   try {
      const csrfToken = document.querySelector(
         'meta[name="csrf-token"]'
      )?.content;

      const response = await fetch(`/catalogs/${catalogId}`, {
         method: 'PUT',
         headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
         },
         body: JSON.stringify(getCatalogData(form)),
      });

      const result = await response.json();

      if (!response.ok || !result.catalog) {
         button.disabled = false;
         return;
      }

      const refreshed = await refreshAfterCatalogChange();

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
 * @returns {Promise<boolean>} Результат обновления.
 */
async function refreshCatalogsTable() {
   const tableBody = document.querySelector('[data-catalogs-body]');

   if (!tableBody) {
      return false;
   }

   try {
      const response = await fetch('/catalogs', {
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
         '[data-catalogs-body]'
      );

      if (!newTableBody) {
         return false;
      }

      tableBody.replaceChildren(...Array.from(newTableBody.childNodes));

      return true;
   } catch (error) {
      return false;
   }
}

/**
 * Собирает данные каталога из формы.
 *
 * @param {HTMLElement} form Контейнер формы каталога.
 * @returns {Object} Данные каталога.
 */
function getCatalogData(form) {
   const parentId = form.querySelector('[name="parent_id"]')?.value;

   return {
      name:
         form.querySelector('input[name="catalog-name"]')?.value.trim() || '',

      parent_id: parentId === '' ? null : Number(parentId),

      sort_order: form.querySelector('input[name="sort_order"]')?.value || 0,

      is_active:
         form.querySelector('input[name="is_active"]')?.checked ?? false,
   };
}

/**
 * Проверяет поля каталога средствами браузера.
 *
 * @param {HTMLElement} form Контейнер формы каталога.
 * @returns {boolean} Результат проверки.
 */
function validateCatalogForm(form) {
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
 * Открывает просмотр каталога.
 *
 * @param {HTMLElement} button Кнопка просмотра.
 */
function viewCatalog(button) {
   const row = button.closest('[data-catalog-id]');

   if (!row) {
      return;
   }

   const catalogId = row.dataset.catalogId;

   if (!catalogId) {
      return;
   }

   window.operationModal.load(
      `/catalogs/${catalogId}`,
      'Не удалось загрузить каталог.'
   );
}

/**
 * Открывает подтверждение удаления каталога.
 *
 * @param {HTMLElement} button Кнопка удаления.
 */
function deleteCatalog(button) {
   const row = button.closest('[data-catalog-id]');

   if (!row) {
      return;
   }

   const catalogId = row.dataset.catalogId;

   if (!catalogId) {
      return;
   }

   window.operationModal.load(
      `/catalogs/${catalogId}/delete`,
      'Не удалось загрузить окно удаления каталога.'
   );
}

/**
 * Окончательно удаляет каталог.
 *
 * @param {HTMLElement} button Кнопка подтверждения.
 */
async function confirmDeleteCatalog(button) {
   const catalogId = button.dataset.catalogId;

   if (!catalogId) {
      return;
   }

   button.disabled = true;

   try {
      const csrfToken = document.querySelector(
         'meta[name="csrf-token"]'
      )?.content;

      const response = await fetch(`/catalogs/${catalogId}`, {
         method: 'DELETE',
         headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken,
         },
      });

      const result = await response.json();

      if (!response.ok || !result.success) {
         button.disabled = false;
         return;
      }

      const refreshed = await refreshAfterCatalogChange();

      if (!refreshed) {
         button.disabled = false;
         return;
      }

      window.operationModal.close();
   } catch (error) {
      button.disabled = false;
   }
}
