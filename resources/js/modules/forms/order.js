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
