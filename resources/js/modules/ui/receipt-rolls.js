import { initSelects } from '../../assets/select.js';
import { confirmRowRemoval, rowRemovalText } from './row-remove-confirm.js';

export function initReceiptRolls() {
   const form = document.querySelector('.receipt-order');

   if (!form) {
      return;
   }

   const rollsList = form.querySelector('[data-receipt-rolls]');

   const addButton = form.querySelector('[data-receipt-roll-add]');

   const rowTemplate = document.querySelector('[data-receipt-roll-template]');

   if (!rollsList || !addButton || !rowTemplate) {
      return;
   }

   // =========================================================
   // Получить все строки рулонов
   // =========================================================

   const getRolls = () => {
      return Array.from(rollsList.querySelectorAll('[data-receipt-roll]'));
   };

   // =========================================================
   //* Переиндексация
   // =========================================================

   const reindexRolls = () => {
      getRolls().forEach((roll, index) => {
         const materialInput = roll.querySelector('[data-receipt-roll-material]');

         const numberInput = roll.querySelector('[data-receipt-roll-number]');

         const weightInput = roll.querySelector('[data-receipt-roll-weight]');

         if (materialInput) {
            materialInput.name = `rolls[${index}][material_id]`;
         }

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
   // Создать новую строку рулона из шаблона
   // =========================================================

   const createRoll = () => {
      const roll = rowTemplate.content.cloneNode(true).querySelector('tr');

      rollsList.appendChild(roll);

      // Кастомные селекты клона инициализируются после вставки
      initSelects(roll);

      // Строка, добавленная после переключения режима учёта,
      // наследует текущее скрытие колонки номера
      if (form.querySelector('[data-receipt-mode-input]')?.value === 'total_weight') {
         roll.querySelector('[data-receipt-roll-number-field]')?.setAttribute('hidden', '');
      }

      return roll;
   };

   // =========================================================
   // Добавить рулон
   // =========================================================

   const addRoll = () => {
      const roll = createRoll();

      reindexRolls();

      roll.querySelector('.select__button')?.focus();
   };

   // =========================================================
   // Удалить рулон
   // =========================================================

   const removeRoll = (roll) => {
      if (!roll) {
         return;
      }

      roll.remove();

      reindexRolls();
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

      if (!roll) {
         return;
      }

      confirmRowRemoval({
         text: rowRemovalText(
            roll,
            '[data-receipt-roll-material]',
            '.select__button-text'
         ),
         onConfirm: () => removeRoll(roll),
      });
   });

   // =========================================================
   // Очистка формы: строки пересоздаются из шаблона
   // =========================================================

   form.querySelector('[data-receipt-form-reset]')?.addEventListener('click', () => {
      rollsList.innerHTML = '';

      createRoll();

      reindexRolls();
   });

   // =========================================================
   // Сервер отрендерил old-строки после ошибки валидации —
   // их селекты нужно инициализировать
   // =========================================================

   initSelects(rollsList);

   // =========================================================
   // Начальная индексация
   // =========================================================

   reindexRolls();
}
