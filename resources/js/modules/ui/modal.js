import { initProductionOperationForm } from '../forms/material-operation.js';

document.addEventListener('DOMContentLoaded', () => {
   const modal = document.querySelector('[data-operation-modal]');

   if (!modal) return;

   const content = modal.querySelector('[data-operation-modal-content]');
   const closeButtons = modal.querySelectorAll('[data-operation-modal-close]');
   let previouslyFocusedElement = null;

   if (!content) return;

   function open() {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('modal-open');
   }

   function close() {
      if (modal.contains(document.activeElement)) {
         document.activeElement.blur();
      }

      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('modal-open');
      content.innerHTML = '';

      previouslyFocusedElement?.focus();
      previouslyFocusedElement = null;
   }

   function load(url) {
      previouslyFocusedElement = document.activeElement;

      open();

      fetch(url, {
         headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'text/html',
         },
      })
         .then((response) => {
            if (!response.ok) {
               throw new Error();
            }

            return response.text();
         })
         .then((html) => {
            content.innerHTML = html;

            // Формы в загруженном контенте (создание/редактирование
            // линии) требуют инициализации обработчиков.
            const form = content.querySelector(
               '[data-production-operation-create-form], [data-production-operation-update-form], [data-production-operation-form]'
            );

            if (form) {
               initProductionOperationForm(form);
            }
         })
         .catch(() => {
            close();
         });
   }

   function openContent(html) {
      previouslyFocusedElement = document.activeElement;

      content.innerHTML = html;
      open();
   }

   window.operationModal = {
      open: openContent,
      close,
      load,
   };

   document.addEventListener('click', (event) => {
      const productionOperation = event.target.closest(
         '[data-production-operation-open]'
      );

      if (productionOperation) {
         event.preventDefault();

         load(
            `/production/operations/${productionOperation.dataset.productionOperationId}`
         );

         return;
      }

      // Ордера (приход, расход, корректировка) открываются
      // на отдельных страницах — через data-row-link в списках.

      const closeButton = event.target.closest('[data-operation-modal-close]');

      if (closeButton) {
         close();
      }
   });

   closeButtons.forEach((button) => {
      button.addEventListener('click', close);
   });

   document.addEventListener('keydown', (event) => {
      if (
         event.key === 'Escape' &&
         modal.getAttribute('aria-hidden') === 'false'
      ) {
         close();
      }
   });
});
