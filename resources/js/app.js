import './modules/ui/modal.js';
import './modules/ui/messages.js';
import './modules/ui/clickable-rows.js';
import './modules/ui/sidebar.js';

//* Импортируем формы
import { initFiltersModule } from './modules/forms/filters.js';
import { initMaterialReceiptModule } from './modules/forms/material-receipt.js';
import { initMaterialIssueModule } from './modules/forms/material-issue.js';
import { initLaminationModule } from './modules/forms/lamination.js';
import { initOrderFormModule } from './modules/forms/order.js';
import { initReceiptRolls } from './modules/ui/receipt-rolls.js';
import { initProductionOperationModule } from './modules/forms/material-operation.js';
//* Импортируем страниц
import { initMaterialRollsModule } from './modules/pages/material-rolls.js';
import { initMaterialMovementsModule } from './modules/pages/material-movements.js';
import { initMaterialsModule } from './modules/pages/materials.js';
import { initCatalogsModule } from './modules/pages/catalogs.js';
import { initProductionOperations } from './modules/pages/production-operations.js';

document.addEventListener('DOMContentLoaded', () => {
   initFiltersModule();
   initMaterialMovementsModule();
   initMaterialRollsModule();
   initMaterialReceiptModule();
   initMaterialIssueModule();
   initLaminationModule();
   initOrderFormModule();
   initReceiptRolls();
   initMaterialsModule();
   initCatalogsModule();
   initProductionOperations();
   initProductionOperationModule();
});
