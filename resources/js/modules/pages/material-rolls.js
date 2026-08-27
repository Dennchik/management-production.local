import { CustomSelect } from '../../assets/select.js';

export function initMaterialRollsModule() {
   const filter = document.querySelector('.filters-actions');
   if (!filter) return;

   const rollsMaterialEl = filter
      .querySelector('#rolls-material')
      ?.closest('.select');

   const rollsIdentifierEl = filter
      .querySelector('#rolls-identifier')
      ?.closest('.select');

   if (rollsMaterialEl) {
      new CustomSelect(rollsMaterialEl);
   }

   if (rollsIdentifierEl) {
      new CustomSelect(rollsIdentifierEl);
   }
}
