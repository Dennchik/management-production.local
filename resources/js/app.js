import './modules/ui/modal.js';
import './modules/ui/messages.js';
import './modules/ui/clickable-rows.js';
import './modules/ui/sidebar.js';

//* Импортируем формы
import { initFiltersModule } from './modules/forms/filters.js';
import { initMaterialReceiptModule } from './modules/forms/material-receipt.js';
import { initMaterialIssueModule } from './modules/forms/material-issue.js';
import { initLaminationModule } from './modules/forms/lamination.js';
import { initReceiptRolls } from './modules/ui/receipt-rolls.js';
//* Импортируем страниц
import { initMaterialRollsModule } from './modules/pages/material-rolls.js';
import { initMaterialMovementsModule } from './modules/pages/material-movements.js';
//* Импортируем утилиты для инициализации
// import { initSelects } from './assets/select.js';
document.addEventListener('DOMContentLoaded', () => {
   initFiltersModule();
   initMaterialMovementsModule();
   initMaterialRollsModule();
   initMaterialReceiptModule();
   initMaterialIssueModule();
   initLaminationModule();
   initReceiptRolls();
   // initSelects();
});
