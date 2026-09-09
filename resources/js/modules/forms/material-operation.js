import { CustomSelect } from '../../assets/select.js';

export function initProductionOperationModule() {
   const form = document.querySelector('[data-production-operation-form]');

   if (!form) return;

   /**
    * Инициализирует CustomSelect на всех элементах внутри указанного контейнера.
    */
   function initSelectsInContainer(container) {
      const selectElements = container.querySelectorAll('.select');

      selectElements.forEach((el) => {
         if (!el.dataset.selectInitialized) {
            new CustomSelect(el);
            el.dataset.selectInitialized = 'true';
         }
      });
   }

   initSelectsInContainer(form);

   /**
    * Добавление новой строки (вход/выход).
    */
   function addComponentRow(type, containerSelector) {
      const listContainer = form.querySelector(containerSelector);

      if (!listContainer) return;

      const items = listContainer.querySelectorAll('.operation-component');

      if (items.length === 0) return;

      const template = items[0];
      const newRow = template.cloneNode(true);
      const newIndex = items.length;

      newRow.setAttribute('data-component-index', newIndex);

      newRow.querySelectorAll('input, select, textarea').forEach((input) => {
         if (input.type === 'checkbox') {
            input.checked = input.defaultChecked || false;
         } else if (
            input.type === 'hidden' &&
            input.name.endsWith('[is_required]')
         ) {
            // Оставляем hidden input для checkbox.
         } else if (!input.classList.contains('select__value')) {
            input.value =
               input.name && input.name.endsWith('[unit]') ? 'kg' : '';
         }
      });

      const selectEl = newRow.querySelector('.select');

      if (selectEl) {
         delete selectEl.dataset.selectInitialized;

         const hiddenInput = selectEl.querySelector('.select__value');
         const valueSpan = selectEl.querySelector('.select__button-text');
         const searchInput = selectEl.querySelector('.select__search');
         const options = selectEl.querySelectorAll('.select__item');

         if (hiddenInput) hiddenInput.value = '';
         if (valueSpan) valueSpan.textContent = 'Выберите материал';
         if (searchInput) searchInput.value = '';

         options.forEach((opt) => {
            opt.classList.remove('_selected');
            opt.setAttribute('aria-selected', 'false');
            opt.style.display = '';
         });

         const dropdown = selectEl.querySelector('._collapse');

         if (dropdown) {
            dropdown.classList.remove('_show', 'collapsing');
            dropdown.style.height = '';
         }
      }

      updateRowIndexes(newRow, type, newIndex);

      listContainer.appendChild(newRow);

      initSelectsInContainer(newRow);
   }

   /**
    * Удаление строки.
    */
   function removeComponentRow(btn) {
      const row = btn.closest('.operation-component');
      const listContainer = row?.parentElement;

      if (!row || !listContainer) return;

      const allRows = listContainer.querySelectorAll('.operation-component');

      if (allRows.length <= 1) {
         return;
      }

      const selectEl = row.querySelector('.select');

      if (selectEl && window._activeSelects) {
         window._activeSelects = window._activeSelects.filter(
            (inst) => inst.container !== selectEl
         );
      }

      row.remove();

      reindexRows(listContainer);
   }

   /**
    * Обновление name, id, for при изменении состава строк.
    */
   function updateRowIndexes(row, type, index) {
      const singleType = type === 'inputs' ? 'input' : 'output';

      row.querySelectorAll('[id]').forEach((el) => {
         el.id = el.id.replace(
            new RegExp(`${singleType}[_-]\\w+[_-]\\d+`),
            (match) => match.replace(/\d+$/, index)
         );
      });

      row.querySelectorAll('label[for]').forEach((label) => {
         const currentFor = label.getAttribute('for');

         if (currentFor) {
            label.setAttribute(
               'for',
               currentFor.replace(
                  new RegExp(`${singleType}[_-]\\w+[_-]\\d+`),
                  (match) => match.replace(/\d+$/, index)
               )
            );
         }
      });

      row.querySelectorAll('[name]').forEach((el) => {
         const name = el.getAttribute('name');

         if (name) {
            const updatedName = name.replace(
               new RegExp(`${type}\\[\\d+\\]`),
               `${type}[${index}]`
            );

            el.setAttribute('name', updatedName);
         }
      });
   }

   /**
    * Переиндексация строк после удаления.
    */
   function reindexRows(listContainer) {
      const isInput = listContainer.hasAttribute(
         'data-production-operation-inputs'
      );

      const type = isInput ? 'inputs' : 'outputs';
      const rows = listContainer.querySelectorAll('.operation-component');

      rows.forEach((row, index) => {
         row.setAttribute('data-component-index', index);
         updateRowIndexes(row, type, index);
      });
   }

   /**
    * Обработка сохранения производственной операции.
    */
   async function submitOperation(event) {
      event.preventDefault();

      const operationForm = event.currentTarget;

      if (!operationForm.checkValidity()) {
         operationForm.reportValidity();
         return;
      }

      const submitButton = operationForm.querySelector('button[type="submit"]');

      if (submitButton) {
         submitButton.disabled = true;
      }

      try {
         const response = await fetch(operationForm.action, {
            method: operationForm.method || 'POST',
            headers: {
               'X-Requested-With': 'XMLHttpRequest',
               Accept: 'application/json',
            },
            body: new FormData(operationForm),
         });

         const result = await response.json();

         if (!response.ok || !result.success) {
            if (submitButton) {
               submitButton.disabled = false;
            }

            return;
         }

         const modal = document.querySelector('[data-operation-modal]');
         const modalIsOpen = modal?.classList.contains('is-open');

         if (modalIsOpen) {
            const operationsContent = document.querySelector(
               '[data-production-operations-content]'
            );

            if (operationsContent) {
               const listResponse = await fetch('/production/operations', {
                  headers: {
                     'X-Requested-With': 'XMLHttpRequest',
                     Accept: 'text/html',
                  },
               });

               if (listResponse.ok) {
                  const html = await listResponse.text();
                  const documentParser = new DOMParser().parseFromString(
                     html,
                     'text/html'
                  );

                  const newContent = documentParser.querySelector(
                     '[data-production-operations-content]'
                  );

                  if (newContent) {
                     operationsContent.replaceChildren(
                        ...Array.from(newContent.childNodes)
                     );
                  }
               }
            }

            window.operationModal?.close();

            return;
         }

         window.location.href = '/production/operations';
      } catch (error) {
         if (submitButton) {
            submitButton.disabled = false;
         }
      }
   }

   /**
    * Навешиваем клики на добавление/удаление строк.
    */
   form.addEventListener('click', (event) => {
      if (event.target.closest('[data-production-operation-add-input]')) {
         event.preventDefault();

         addComponentRow('inputs', '[data-production-operation-inputs]');
      }

      if (event.target.closest('[data-production-operation-add-output]')) {
         event.preventDefault();

         addComponentRow('outputs', '[data-production-operation-outputs]');
      }

      const removeBtn = event.target.closest(
         '[data-production-operation-remove-input], [data-production-operation-remove-output]'
      );

      if (removeBtn) {
         event.preventDefault();

         removeComponentRow(removeBtn);
      }
   });

   /**
    * Перехватываем стандартную отправку формы.
    */
   const operationForm = form.querySelector(
      '[data-production-operation-create-form]'
   );

   if (operationForm) {
      operationForm.addEventListener('submit', submitOperation);
   }
}
