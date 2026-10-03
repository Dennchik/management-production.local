/**
 * Подтверждение удаления позиции ордера через общую модалку:
 * «Удалить» — строка удаляется, отмена (крестик, Escape, клик
 * по оверлею) просто закрывает модалку. Подтверждение строится
 * по образцу materials/_delete.blade.php (operation-confirm).
 *
 * @param {Object} options
 * @param {string} options.text Текст вопроса, уже экранированный.
 * @param {Function} options.onConfirm Действие при подтверждении.
 */
export function confirmRowRemoval({ text, onConfirm }) {
   const modal = window.operationModal;

   // Без модалки (нет разметки layout) — удаляем без подтверждения
   if (!modal) {
      onConfirm();
      return;
   }

   modal.open(`
      <div class="operation-confirm">
         <div class="operation-confirm__header">
            <h2 class="operation-confirm__title">Удаление позиции</h2>
         </div>

         <div class="operation-confirm__text">${text}</div>

         <div class="operation-confirm__actions">
            <button type="button" class="button button--primary" data-row-remove-confirm>
               <span>Удалить</span>
            </button>
         </div>
      </div>
   `);

   document
      .querySelector('[data-operation-modal] [data-row-remove-confirm]')
      ?.addEventListener('click', () => {
         onConfirm();
         modal.close();
      });
}

/**
 * Экранирует текст для вставки в модалку подтверждения.
 *
 * @param {string} value Исходный текст.
 * @returns {string} Экранированный текст.
 */
export function escapeConfirmText(value) {
   return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
}

/**
 * Текст подтверждения для строки ордера: с названием выбранного
 * материала или общий, если строка пустая.
 *
 * @param {HTMLElement} row Строка позиции.
 * @param {string} materialSelector Селектор hidden-инпута материала.
 * @param {string} buttonTextSelector Селектор текста кнопки селекта.
 * @returns {string} Экранированный текст подтверждения.
 */
export function rowRemovalText(row, materialSelector, buttonTextSelector) {
   const materialValue = row.querySelector(materialSelector)?.value || '';
   const materialName = materialValue
      ? row.querySelector(buttonTextSelector)?.textContent.trim()
      : '';

   return materialName
      ? `Удалить позицию «${escapeConfirmText(materialName)}»?`
      : 'Удалить пустую позицию?';
}
