export function initReceiptRolls() {
   const form = document.querySelector('.receipt-order');

   if (!form) {
      return;
   }

   const rollsList = form.querySelector('[data-receipt-rolls]');

   const addButton = form.querySelector('[data-receipt-roll-add]');

   if (!rollsList || !addButton) {
      return;
   }

   // =========================================================
   // Получить все рулоны
   // =========================================================

   const getRolls = () => {
      return Array.from(rollsList.querySelectorAll('[data-receipt-roll]'));
   };

   // =========================================================
   // Создать новый рулон
   // =========================================================

   const createRoll = (index) => {
      const roll = document.createElement('div');

      roll.className = 'receipt-order__roll';
      roll.dataset.receiptRoll = '';

      roll.innerHTML = `
         <fieldset class="receipt-order__field">
            <label class="receipt-order__label" for="roll_number_${index}" data-receipt-roll-number-label>
               Номер рулона
            </label>

            <input
               class="receipt-order__input"
               id="roll_number_${index}"
               name="rolls[${index}][roll_number]"
               data-receipt-roll-number
               type="text" 
               value="">

         </fieldset>


         <fieldset class="receipt-order__field">

            <label
               class="receipt-order__label"
               for="weight_${index}"
               data-receipt-roll-weight-label>

               Вес, кг

            </label>

            <input
               class="receipt-order__input"
               id="weight_${index}"
               name="rolls[${index}][weight]"
               data-receipt-roll-weight
               type="number"
               step="0.001"
               min="0"
               value="">

         </fieldset>


         <button
            class="receipt-order__roll-remove button"
            type="button"
            data-receipt-roll-remove
            aria-label="Удалить рулон">

            <span>
               Удалить рулон
            </span>

         </button>
      `;

      return roll;
   };

   // =========================================================
   // Добавить рулон
   // =========================================================

   const addRoll = () => {
      const rolls = getRolls();
      const index = rolls.length;

      const roll = createRoll(index);

      rollsList.appendChild(roll);

      const numberInput = roll.querySelector('[data-receipt-roll-number]');

      numberInput?.focus();
   };

   // =========================================================
   // Удалить рулон
   // =========================================================

   const removeRoll = (roll) => {
      if (!roll) {
         return;
      }

      const rolls = getRolls();

      /*
       * Первый рулон удалить нельзя.
       */
      if (rolls.indexOf(roll) === 0) {
         return;
      }

      roll.remove();

      reindexRolls();
   };

   // =========================================================
   //* Переиндексация
   // =========================================================

   const reindexRolls = () => {
      const rolls = getRolls();

      rolls.forEach((roll, index) => {
         const numberInput = roll.querySelector('[data-receipt-roll-number]');

         const numberLabel = roll.querySelector(
            '[data-receipt-roll-number-label]'
         );

         const weightInput = roll.querySelector('[data-receipt-roll-weight]');

         const weightLabel = roll.querySelector(
            '[data-receipt-roll-weight-label]'
         );

         if (numberInput) {
            numberInput.id = `roll_number_${index}`;

            numberInput.name = `rolls[${index}][roll_number]`;
         }

         if (numberLabel) {
            numberLabel.htmlFor = `roll_number_${index}`;
         }

         if (weightInput) {
            weightInput.id = `weight_${index}`;

            weightInput.name = `rolls[${index}][weight]`;
         }

         if (weightLabel) {
            weightLabel.htmlFor = `weight_${index}`;
         }
      });
   };

   // =========================================================
   // Добавление
   // =========================================================

   addButton.addEventListener('click', () => {
      addRoll();
   });

   // =========================================================
   // Удаление
   // =========================================================

   rollsList.addEventListener('click', (event) => {
      const removeButton = event.target.closest('[data-receipt-roll-remove]');

      if (!removeButton) {
         return;
      }

      const roll = removeButton.closest('[data-receipt-roll]');

      removeRoll(roll);
   });

   // =========================================================
   // Начальная индексация
   // =========================================================

   reindexRolls();
}
