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
      identifier: form.querySelector('#identifier'),
   };

   const formatInput = form.querySelector('#format');

   /**
    * Вычисляет идентификатор рулона:
    * код материала + граммаж|толщина + цифры формата.
    * Зеркалит MaterialRoll::composeIdentifier() на стороне PHP.
    */
   function composeIdentifier(option, format) {
      const code = option?.dataset.code || '';
      const value = option?.dataset.grammage || option?.dataset.thickness || '';
      const formatPart = (format || '').replace(/\D/g, '');

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
    * Пересчитывает readonly-поле идентификатора.
    */
   function updateIdentifier() {
      if (!inputs.identifier) return;

      const selected = materialSelectEl.querySelector('.select__item._selected');

      inputs.identifier.value = composeIdentifier(
         selected,
         formatInput?.value
      );
   }

   /**
    * Заполняет характеристики выбранного материала.
    * Формат подставляется из выбранного пункта
    * (материал + формат), но остаётся редактируемым.
    */
   function fillMaterialData(option) {
      if (inputs.grammage) {
         inputs.grammage.value = option?.dataset.grammage || '';
      }

      if (inputs.thickness) {
         inputs.thickness.value = option?.dataset.thickness || '';
      }

      if (formatInput) {
         formatInput.value = option?.dataset.format || '';
      }

      updateIdentifier();
   }

   /**
    * Выбор материала пользователем.
    */
   materialSelectEl.addEventListener('select:change', (e) => {
      const { option } = e.detail;

      fillMaterialData(option);
   });

   /**
    * Ввод формата — идентификатор пересчитывается на лету.
    */
   formatInput?.addEventListener('input', updateIdentifier);

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

      if (formatInput) {
         formatInput.value = '';
      }

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
