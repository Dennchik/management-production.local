import { initSelects } from '../../assets/select.js';

export function initFiltersModule() {
   const filterContainer = document.querySelector('.filters-actions');
   if (!filterContainer) return;

   initSelects(filterContainer);
}
