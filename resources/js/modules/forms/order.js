import { CustomSelect } from '../../assets/select.js';

/**
 * Инициализация формы заказа: выбор материала и управление позициями.
 */
export function initOrderFormModule() {
   const form = document.querySelector('[data-order-form]');

   if (!form) {
      return;
   }

   const itemsContainer = form.querySelector('[data-order-items]');

   const initSelect = (scope) => {
      scope.querySelectorAll('[data-select]').forEach((el) => {
         if (!el.dataset.selectInitialized) {
            new CustomSelect(el);
            el.dataset.selectInitialized = '1';
         }
      });
   };

   initSelect(form);

   initTaskOrderProducts(form);

   const updateRemoveButtons = () => {
      const rows = form.querySelectorAll('[data-order-item]');

      rows.forEach((row) => {
         const removeButton = row.querySelector('[data-order-item-remove]');

         if (removeButton) {
            removeButton.hidden = rows.length <= 1;
         }
      });
   };

   form.querySelector('[data-order-item-add]')?.addEventListener('click', () => {
      const rows = form.querySelectorAll('[data-order-item]');
      const lastRow = rows[rows.length - 1];

      if (!lastRow) {
         return;
      }

      const newRow = lastRow.cloneNode(true);

      // cloneNode не копирует обработчики — переинициализируем селект.
      newRow.querySelectorAll('[data-select]').forEach((el) => {
         delete el.dataset.selectInitialized;
         const dropdown = el.querySelector('.select__dropdown');

         if (dropdown) {
            dropdown.classList.add('_collapse');
         }
      });

      newRow
         .querySelectorAll('input[type="number"], input.select__value')
         .forEach((input) => {
            if (input.classList.contains('select__value')) {
               input.value = '';
            } else {
               input.value = '';
            }
         });

      const buttonText = newRow.querySelector('.select__button-text');

      if (buttonText) {
         buttonText.textContent = 'Выберите материал';
      }

      itemsContainer.appendChild(newRow);
      initSelect(newRow);
      reindexItems(form);
      updateRemoveButtons();
   });

   form.addEventListener('click', (event) => {
      const removeButton = event.target.closest('[data-order-item-remove]');

      if (!removeButton) {
         return;
      }

      const row = removeButton.closest('[data-order-item]');

      if (form.querySelectorAll('[data-order-item]').length > 1) {
         row.remove();
         reindexItems(form);
         updateRemoveButtons();
      }
   });

   form.querySelector('[data-output-roll-add]')?.addEventListener('click', () => {
      const container = form.querySelector('[data-output-rolls]');
      const rows = container.querySelectorAll('[data-output-roll]');
      const lastRow = rows[rows.length - 1];

      if (!lastRow) {
         return;
      }

      const newRow = lastRow.cloneNode(true);

      newRow.querySelectorAll('input').forEach((input) => {
         input.value = '';
      });

      container.appendChild(newRow);
      reindexOutputs(container);
   });

   /**
    * Перенумровывает поля выходных рулонов: outputs[N][...].
    */
   function reindexOutputs(container) {
      container.querySelectorAll('[data-output-roll]').forEach((row, index) => {
         row.querySelectorAll('input').forEach((input) => {
            const name = input.getAttribute('name');

            if (name) {
               input.setAttribute(
                  'name',
                  name.replace(/outputs\[\d+\]/, `outputs[${index}]`)
               );
            }
         });
      });
   }

   /**
    * Перенумровывает поля позиций: items[N][...].
    */
   function reindexItems(scope) {
      scope.querySelectorAll('[data-order-item]').forEach((row, index) => {
         row.querySelectorAll('input').forEach((input) => {
            const name = input.getAttribute('name');

            if (name) {
               input.setAttribute(
                  'name',
                  name.replace(/items\[\d+\]/, `items[${index}]`)
               );
            }
         });
      });
   }
}

/**
 * Форма производственной задачи: при выбранном заказе
 * в списке продукции остаются только материалы из позиций заказа.
 */
function initTaskOrderProducts(form) {
   const orderSelect = form.querySelector('[data-task-order]');
   const productSelectEl = form.querySelector('[data-task-product-select]');

   if (!orderSelect || !productSelectEl) {
      return;
   }

   const listEl = productSelectEl.querySelector('.select__dropdown');
   const emptyEl = productSelectEl.querySelector('.select__empty');
   const allOptions = Array.from(productSelectEl.querySelectorAll('.select__item'));

   const getSelectInstance = () =>
      (window._activeSelects || []).find((select) =>
         productSelectEl.contains(select.hiddenInput)
      );

   const applyFilter = () => {
      const selectedOrder = orderSelect.selectedOptions[0];
      const materialIds = selectedOrder?.value
         ? JSON.parse(selectedOrder.dataset.materials || '[]').map(String)
         : null;

      const visibleOptions = materialIds
         ? allOptions.filter((option) => materialIds.includes(option.dataset.value))
         : allOptions;

      allOptions.forEach((option) => option.remove());
      visibleOptions.forEach((option) => listEl.insertBefore(option, emptyEl));

      const select = getSelectInstance();

      if (!select) {
         return;
      }

      select.refresh();

      // Сбрасываем выбранную продукцию, если её нет в заказе
      const current = select.hiddenInput?.value;

      if (current && !visibleOptions.some((option) => option.dataset.value === current)) {
         select.selectOption(null, false);
      }
   };

   orderSelect.addEventListener('change', applyFilter);

   applyFilter();
}
