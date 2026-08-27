import { CustomSelect } from '../../assets/select.js';

export function initMaterialMovementsModule() {
   const filter = document.querySelector('.filters-actions');
   if (!filter) return;

   const materialSelectEl = filter
      .querySelector('#material-movements-material')
      ?.closest('.select');

   if (materialSelectEl) {
      new CustomSelect(materialSelectEl);
   }
}
