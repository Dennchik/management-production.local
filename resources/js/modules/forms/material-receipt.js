import { CustomSelect } from '../../assets/select.js';

export function initMaterialReceiptModule() {
   const form = document.querySelector('.receipt-order');
   if (!form) return;

   const materialSelectEl = form.querySelector('.material-select');
   if (!materialSelectEl) return;

   const materialSelect = new CustomSelect(materialSelectEl);

   const inputs = {
      grammage: form.querySelector('#grammage'),
      thickness: form.querySelector('#thickness'),
      format: form.querySelector('#format'),
      identifier: form.querySelector('#identifier'),
   };

   materialSelectEl.addEventListener('select:change', (e) => {
      const { option } = e.detail;

      if (inputs.grammage)
         inputs.grammage.value = option?.dataset.grammage || '';
      if (inputs.thickness)
         inputs.thickness.value = option?.dataset.thickness || '';
      if (inputs.format) inputs.format.value = option?.dataset.format || '';
      if (inputs.identifier)
         inputs.identifier.value = option?.dataset.identifier || '';
   });
}
