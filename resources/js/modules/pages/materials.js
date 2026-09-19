/**
 * Инициализация страницы справочника материалов.
 *
 * Отвечает за:
 * - открытие формы создания материала;
 * - просмотр материала;
 * - открытие формы редактирования;
 * - удаление материала;
 * - сохранение нового материала;
 * - обновление существующего материала;
 * - просмотр, редактирование и удаление каталога;
 * - обновление таблицы без перезагрузки страницы.
 */
export function initMaterialsModule() {
   const materialsPage = document.querySelector('[data-materials-page]');

   if (!materialsPage) {
      return;
   }

   const createButton = materialsPage.querySelector('[data-material-create]');
   const tableBody = materialsPage.querySelector('[data-materials-body]');

   if (!createButton || !tableBody) {
      return;
   }

   createButton.addEventListener('click', () => {
      void createEntry();
   });

   tableBody.addEventListener('click', (event) => {
      const catalogViewButton = event.target.closest('[data-action="catalog-view"]');

      if (catalogViewButton) {
         const catalogId = catalogViewButton.dataset.catalogId;

         if (catalogId) {
            window.operationModal.load(
               `/catalogs/${catalogId}`,
               'Не удалось загрузить каталог.'
            );
         }

         return;
      }

      const catalogEditButton = event.target.closest('[data-action="catalog-edit"]');

      if (catalogEditButton) {
         void editCatalog(catalogEditButton);
         return;
      }

      const catalogDeleteButton = event.target.closest('[data-action="catalog-delete"]');

      if (catalogDeleteButton) {
         deleteCatalog(catalogDeleteButton);
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

      if (action === 'delete') {
         deleteMaterial(actionButton);
      }
   });

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

      form?.querySelector('input[name="catalog-name"]')?.focus();
   } catch (error) {
      return;
   }
}

/**
 * Открывает форму редактирования каталога.
 *
 * @param {HTMLElement} button Кнопка редактирования каталога.
 */
async function editCatalog(button) {
   const catalogId = button.dataset.catalogId;

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

      window.operationModal.open(await response.text());

      const form = document.querySelector('[data-catalog-form]');

      form?.querySelector('input[name="catalog-name"]')?.focus();
   } catch (error) {
      return;
   }
}

/**
 * Открывает подтверждение удаления каталога.
 *
 * @param {HTMLElement} button Кнопка удаления каталога.
 */
function deleteCatalog(button) {
   const catalogId = button.dataset.catalogId;

   if (!catalogId) {
      return;
   }

   window.operationModal.load(
      `/catalogs/${catalogId}/delete`,
      'Не удалось загрузить окно удаления каталога.'
   );
}

/**
 * Открывает форму создания материала.
 */
async function createMaterial() {
   try {
      const catalogId = getCurrentCatalogId();
      const url = catalogId
         ? `/materials/create?catalog=${encodeURIComponent(catalogId)}`
         : '/materials/create';

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

      form?.querySelector('input[name="material-name"]')?.focus();
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
         showMaterialFormError(form, result.message);
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
         showMaterialFormError(form, result.message);
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
      return false;
   }

   const catalogId = getCurrentCatalogId();
   const url = catalogId ? `/materials?catalog=${encodeURIComponent(catalogId)}` : '/materials';

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

      return true;
   } catch (error) {
      return false;
   }
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

      code: form.querySelector('input[name="code"]')?.value.trim() || '',

      grammage: form.querySelector('input[name="grammage"]')?.value || null,

      thickness: form.querySelector('input[name="thickness"]')?.value || null,

      format: form.querySelector('input[name="format"]')?.value.trim() || '',

      catalog_id: form.querySelector('select[name="catalog_id"]')?.value || null,

      material_type:
         form.querySelector('select[name="material_type"]')?.value || 'raw',

      allowed_operations: Array.from(
         form.querySelectorAll('input[name="allowed_operations[]"]:checked')
      ).map((input) => input.value),

      is_active:
         form.querySelector('input[name="is_active"]')?.checked ?? false,
   };
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
