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
   // Получить все строки рулонов
   // =========================================================

   const getRolls = () => {
      return Array.from(rollsList.querySelectorAll('[data-receipt-roll]'));
   };

   // =========================================================
   // Обновить кнопки удаления: у первой строки кнопки нет
   // =========================================================

   const syncRemoveButtons = () => {
      getRolls().forEach((roll, index) => {
         const removeButton = roll.querySelector('[data-receipt-roll-remove]');

         if (removeButton) {
            removeButton.hidden = index === 0;
         }
      });
   };

   // =========================================================
   // Создать новую строку рулона
   // =========================================================

   const createRoll = (index) => {
      const roll = document.createElement('tr');

      roll.dataset.receiptRoll = '';

      roll.innerHTML = `
         <td data-receipt-roll-number-field>
            <input
               class="receipt-order__input"
               id="roll_number_${index}"
               name="rolls[${index}][roll_number]"
               data-receipt-roll-number
               type="text"
               value=""
               aria-label="Номер рулона">
         </td>

         <td>
            <input
               class="receipt-order__input"
               id="weight_${index}"
               name="rolls[${index}][weight]"
               data-receipt-roll-weight
               type="number"
               step="0.001"
               min="0"
               value=""
               aria-label="Вес, кг">
         </td>

         <td>
            <button
               class="receipt-order__roll-remove button"
               type="button"
               data-receipt-roll-remove
               aria-label="Удалить рулон">

               <span>
                  Удалить
               </span>
            </button>
         </td>
      `;

      return roll;
   };

   // =========================================================
   // Добавить рулон
   // =========================================================

   const addRoll = () => {
      const index = getRolls().length;

      const roll = createRoll(index);

      rollsList.appendChild(roll);

      syncRemoveButtons();

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

      /*
       * Первый рулон удалить нельзя.
       */
      if (getRolls().indexOf(roll) === 0) {
         return;
      }

      roll.remove();

      reindexRolls();
      syncRemoveButtons();
   };

   // =========================================================
   //* Переиндексация
   // =========================================================

   const reindexRolls = () => {
      getRolls().forEach((roll, index) => {
         const numberInput = roll.querySelector('[data-receipt-roll-number]');

         const weightInput = roll.querySelector('[data-receipt-roll-weight]');

         if (numberInput) {
            numberInput.id = `roll_number_${index}`;

            numberInput.name = `rolls[${index}][roll_number]`;
         }

         if (weightInput) {
            weightInput.id = `weight_${index}`;

            weightInput.name = `rolls[${index}][weight]`;
         }
      });
   };

   // =========================================================
   // Добавление и удаление
   // =========================================================

   addButton.addEventListener('click', () => {
      addRoll();
   });

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
   syncRemoveButtons();
}
