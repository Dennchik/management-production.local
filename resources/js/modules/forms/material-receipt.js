import { CustomSelect } from '../../assets/select.js';

export function initMaterialReceiptModule() {
   const form = document.querySelector('.receipt-order');
   if (!form) return;

   const materialSelectEl = form.querySelector('.material-select');
   if (!materialSelectEl) return;

   const materialSelect = new CustomSelect(materialSelectEl);

   const inputs = {
      grammage: form.querySelector('#grammage'),
      thickness: form.querySelector('#thickness'),
      format: form.querySelector('#format'),
      identifier: form.querySelector('#identifier'),
   };

   /**
    * Заполняет характеристики выбранного материала.
    */
   function fillMaterialData(option) {
      if (inputs.grammage) {
         inputs.grammage.value = option?.dataset.grammage || '';
      }

      if (inputs.thickness) {
         inputs.thickness.value = option?.dataset.thickness || '';
      }

      if (inputs.format) {
         inputs.format.value = option?.dataset.format || '';
      }

      if (inputs.identifier) {
         inputs.identifier.value = option?.dataset.identifier || '';
      }
   }

   /**
    * Выбор материала пользователем.
    */
   materialSelectEl.addEventListener('select:change', (e) => {
      const { option } = e.detail;

      fillMaterialData(option);
   });

   /**
    * Восстановление состояния формы после
    * возврата Laravel с ошибкой валидации.
    *
    * CustomSelect уже восстановил selected option,
    * но событие select:change при этом не вызывается.
    */
   const selectedMaterial = materialSelectEl.querySelector(
      '.select__item._selected'
   );

   if (selectedMaterial) {
      fillMaterialData(selectedMaterial);
   }

   /**
    * Очистка формы.
    *
    * Это единственное место, где форма должна
    * очищаться программно.
    */
   const resetButton = form.querySelector('[data-receipt-form-reset]');

   resetButton?.addEventListener('click', () => {
      form.reset();

      materialSelect.selectOption(null);

      Object.values(inputs).forEach((input) => {
         if (input) {
            input.value = '';
         }
      });

      const rollsList = form.querySelector('[data-receipt-rolls]');

      if (rollsList) {
         const firstRoll = rollsList.querySelector('[data-receipt-roll]');

         rollsList.innerHTML = '';

         if (firstRoll) {
            rollsList.appendChild(firstRoll);

            const rollNumberInput = firstRoll.querySelector(
               '[data-receipt-roll-number]'
            );

            const weightInput = firstRoll.querySelector(
               '[data-receipt-roll-weight]'
            );

            if (rollNumberInput) {
               rollNumberInput.value = '';
            }

            if (weightInput) {
               weightInput.value = '';
            }
         }
      }
   });
}
