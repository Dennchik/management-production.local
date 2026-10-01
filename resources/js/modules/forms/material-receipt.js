const MODE_STORAGE_KEY = 'receipt-mode';

export function initMaterialReceiptModule() {
   const form = document.querySelector('.receipt-order');
   if (!form) return;

   const modeInput = form.querySelector('[data-receipt-mode-input]');
   const modeButtons = form.querySelectorAll('[data-receipt-mode-button]');
   const rollAddButton = form.querySelector('[data-receipt-roll-add]');
   const addButtonText = form.querySelector('[data-receipt-roll-add-text]');
   const totalHint = form.querySelector('[data-receipt-total-hint]');
   const rollsTitle = form.querySelector('[data-receipt-rolls-title]');

   /**
    * Режим учёта: rolls — обычный ввод номеров,
    * total_weight — вес каждого материала в его рулон «Общий вес».
    */
   function setMode(mode) {
      if (modeInput) {
         modeInput.value = mode;
      }

      modeButtons.forEach((button) => {
         button.classList.toggle('_active', button.dataset.mode === mode);
      });

      const isTotal = mode === 'total_weight';

      // Колонка номера скрывается динамически: строки
      // могут быть добавлены уже после переключения режима
      form.querySelectorAll('[data-receipt-roll-number-field]').forEach((field) => {
         field.hidden = isTotal;
      });

      const numberColumn = form.querySelector('[data-receipt-roll-number-column]');

      if (numberColumn) {
         numberColumn.hidden = isTotal;
      }

      if (addButtonText) {
         addButtonText.textContent = isTotal ? 'Добавить материал' : 'Добавить рулон';
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
   function storeMode(mode) {
      try {
         localStorage.setItem(MODE_STORAGE_KEY, mode);
      } catch {
         // localStorage недоступен — режим просто не запоминается
      }
   }

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

   setMode(modeInput?.value === 'total_weight' ? 'total_weight' : 'rolls');

   /**
    * Очистка формы. Строки позиций пересоздает
    * модуль receipt-rolls, это его зона ответственности.
    *
    * Это единственное место, где форма должна
    * очищаться программно.
    */
   const resetButton = form.querySelector('[data-receipt-form-reset]');

   resetButton?.addEventListener('click', () => {
      form.reset();

      setMode('rolls');
   });
}
