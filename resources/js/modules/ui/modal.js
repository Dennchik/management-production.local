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

      const receipt = event.target.closest('[data-receipt-modal-open]');

      if (receipt) {
         event.preventDefault();

         load(`/receipts/${receipt.dataset.receiptId}`);

         return;
      }

      const issue = event.target.closest('[data-issue-modal-open]');

      if (issue) {
         event.preventDefault();

         load(`/issues/${issue.dataset.issueId}`);

         return;
      }

      const adjustment = event.target.closest('[data-adjustment-modal-open]');

      if (adjustment) {
         event.preventDefault();

         load(`/adjustments/${adjustment.dataset.adjustmentId}`);

         return;
      }

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
