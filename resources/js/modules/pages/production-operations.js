export function initProductionOperations() {
   const page = document.querySelector('[data-production-operations-page]');

   if (!page) return;

   const createButton = page.querySelector(
      '[data-production-operation-create]'
   );

   if (createButton) {
      createButton.addEventListener('click', () => {
         window.operationModal?.load(
            '/production/operations/create',
            'Не удалось загрузить форму создания технологической линии.'
         );
      });
   }

   document.addEventListener('click', (event) => {
      const deleteButton = event.target.closest(
         '[data-production-operation-delete]'
      );

      if (deleteButton) {
         event.preventDefault();

         const operationId = deleteButton.dataset.productionOperationDelete;

         if (!operationId) {
            return;
         }

         window.operationModal?.load(
            `/production/operations/${operationId}/delete`,
            'Не удалось загрузить окно удаления технологической линии.'
         );

         return;
      }

      const confirmDeleteButton = event.target.closest(
         '[data-production-operation-confirm-delete]'
      );

      if (!confirmDeleteButton) {
         return;
      }

      event.preventDefault();

      void deleteProductionOperation(confirmDeleteButton);
   });
}

async function deleteProductionOperation(button) {
   const deleteForm = button.closest('[data-production-operation-delete-form]');

   if (!deleteForm) {
      return;
   }

   const operationId = deleteForm.dataset.productionOperationId;

   if (!operationId) {
      return;
   }

   button.disabled = true;

   try {
      const csrfToken = document.querySelector(
         'meta[name="csrf-token"]'
      )?.content;

      const response = await fetch(`/production/operations/${operationId}`, {
         method: 'DELETE',
         headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
         },
      });

      const result = await response.json();

      if (!response.ok || !result.success) {
         button.disabled = false;
         return;
      }

      const operationsContent = document.querySelector(
         '[data-production-operations-content]'
      );

      if (!operationsContent) {
         window.operationModal?.close();
         return;
      }

      const listResponse = await fetch('/production/operations', {
         headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
         },
      });

      if (!listResponse.ok) {
         button.disabled = false;
         return;
      }

      const html = await listResponse.text();

      const parsedDocument = new DOMParser().parseFromString(html, 'text/html');

      const newContent = parsedDocument.querySelector(
         '[data-production-operations-content]'
      );

      if (!newContent) {
         button.disabled = false;
         return;
      }

      operationsContent.replaceChildren(...Array.from(newContent.childNodes));

      window.operationModal?.close();
   } catch (error) {
      button.disabled = false;
   }
}
