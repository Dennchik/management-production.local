document.addEventListener('DOMContentLoaded', () => {
   const handler = (e) => {
      const row = e.target.closest('[data-row-link]');
      if (!row) return;

      // Клик по кнопке действия или ссылке внутри строки
      // не должен открывать карточку строки.
      if (e.target.closest('a, button, [data-action]')) return;

      const url = row.dataset.rowLink;
      if (!url) return;

      if (e.type === 'keydown' && e.key !== 'Enter' && e.key !== ' ') return;

      if (e.type === 'keydown') {
         e.preventDefault();
      }

      window.location.href = url;
   };

   document.addEventListener('click', handler);
   document.addEventListener('keydown', handler);
});
