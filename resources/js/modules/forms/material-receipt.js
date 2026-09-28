import { CustomSelect } from '../../assets/select.js';

const MODE_STORAGE_KEY = 'receipt-mode';

export function initMaterialReceiptModule() {
   const form = document.querySelector('.receipt-order');
   if (!form) return;

   const materialSelectEl = form.querySelector('.material-select');
   if (!materialSelectEl) return;

   const materialSelect = new CustomSelect(materialSelectEl);

   const inputs = {
      grammage: form.querySelector('#grammage'),
      thickness: form.querySelector('#thickness'),
      format: form.querySelector('#material-format'),
      identifier: form.querySelector('#identifier'),
   };

   const modeInput = form.querySelector('[data-receipt-mode-input]');
   const modeButtons = form.querySelectorAll('[data-receipt-mode-button]');
   const rollNumberFields = form.querySelectorAll('[data-receipt-roll-number-field]');
   const rollAddButton = form.querySelector('[data-receipt-roll-add]');
   const totalHint = form.querySelector('[data-receipt-total-hint]');
   const rollsTitle = form.querySelector('[data-receipt-rolls-title]');

   /**
    * Выбранный пункт материала (или null).
    */
   const selectedMaterialOption = () =>
      materialSelectEl.querySelector('.select__item._selected');

   /**
    * Подставляет идентификатор выбранного материала.
    */
   function updateIdentifier() {
      if (!inputs.identifier) return;

      inputs.identifier.value =
         selectedMaterialOption()?.dataset.identifier || '';
   }

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

      updateIdentifier();
      applyStoredMode();
   }

   /**
    * Режим учёта: rolls — обычный ввод номеров,
    * total_weight — весь вес в рулон «Общий вес».
    */
   function setMode(mode) {
      if (modeInput) {
         modeInput.value = mode;
      }

      modeButtons.forEach((button) => {
         button.classList.toggle('_active', button.dataset.mode === mode);
      });

      const isTotal = mode === 'total_weight';

      rollNumberFields.forEach((field) => {
         field.hidden = isTotal;
      });

      const numberColumn = form.querySelector('[data-receipt-roll-number-column]');

      if (numberColumn) {
         numberColumn.hidden = isTotal;
      }

      if (rollAddButton) {
         rollAddButton.hidden = isTotal;
      }

      if (totalHint) {
         totalHint.hidden = !isTotal;
      }

      if (rollsTitle) {
         rollsTitle.textContent = isTotal ? 'Общий вес' : 'Рулоны';
      }
   }

   /**
    * Последний выбранный режим запоминается в localStorage.
    */
   function applyStoredMode() {
      let stored = null;

      try {
         stored = localStorage.getItem(MODE_STORAGE_KEY);
      } catch {
         stored = null;
      }

      setMode(stored === 'total_weight' ? 'total_weight' : 'rolls');
   }

   function storeMode(mode) {
      try {
         localStorage.setItem(MODE_STORAGE_KEY, mode);
      } catch {
         // localStorage недоступен — режим просто не запоминается
      }
   }

   /**
    * Выбор материала пользователем.
    */
   materialSelectEl.addEventListener('select:change', (e) => {
      fillMaterialData(e.detail.option);
   });

   /**
    * Переключение режима учёта.
    */
   modeButtons.forEach((button) => {
      button.addEventListener('click', () => {
         const mode = button.dataset.mode === 'total_weight'
            ? 'total_weight'
            : 'rolls';

         setMode(mode);
         storeMode(mode);
      });
   });

   /**
    * Восстановление состояния формы после
    * возврата Laravel с ошибкой валидации.
    *
    * CustomSelect уже восстановил selected option,
    * но событие select:change при этом не вызывается.
    */
   const selectedMaterial = selectedMaterialOption();

   if (selectedMaterial) {
      fillMaterialData(selectedMaterial);
   }

   setMode(modeInput?.value === 'total_weight' ? 'total_weight' : 'rolls');

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

      setMode('rolls');
   });
}
