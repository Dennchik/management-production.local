/**
 * Корзина: восстановление и удаление навсегда.
 */
export function initTrashModule() {
   const page = document.querySelector('[data-trash-page]');

   if (!page) return;

   page.addEventListener('click', async (event) => {
      const restoreButton = event.target.closest('[data-trash-restore]');
      const deleteButton = event.target.closest('[data-trash-delete]');

      if (!restoreButton && !deleteButton) {
         return;
      }

      const button = restoreButton ?? deleteButton;
      const action = restoreButton ? 'restore' : 'destroy';
      const { trashType: type, trashId: id } = button.dataset;

      button.disabled = true;

      try {
         const csrfToken = document.querySelector(
            'meta[name="csrf-token"]'
         )?.content;

         const response = await fetch(`/trash/${type}/${id}`, {
            method: action === 'restore' ? 'POST' : 'DELETE',
            headers: {
               Accept: 'application/json',
               'X-CSRF-TOKEN': csrfToken,
            },
         });

         const result = await response.json().catch(() => ({}));

         if (!response.ok || !result.success) {
            alert(result.message || 'Не удалось выполнить действие.');
            button.disabled = false;
            return;
         }

         // Строка исчезает из корзины; восстановленный каталог
         // может вернуть много материалов — проще перезагрузить.
         window.location.reload();
      } catch (error) {
         button.disabled = false;
      }
   });
}
