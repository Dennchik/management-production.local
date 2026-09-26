import './modules/ui/modal.js';
import './modules/ui/messages.js';
import './modules/ui/clickable-rows.js';
import './modules/ui/sidebar.js';

//* Импортируем формы
import { initFiltersModule } from './modules/forms/filters.js';
import { initMaterialReceiptModule } from './modules/forms/material-receipt.js';
import { initMaterialIssueModule } from './modules/forms/material-issue.js';
import { initMaterialAdjustmentModule } from './modules/forms/material-adjustment.js';
import { initReceiptRolls } from './modules/ui/receipt-rolls.js';
import { initProductionOperationModule } from './modules/forms/material-operation.js';
import { initTasksFormModule } from './modules/forms/tasks-form.js';
//* Импортируем страниц
import { initMaterialRollsModule } from './modules/pages/material-rolls.js';
import { initMaterialMovementsModule } from './modules/pages/material-movements.js';
import { initMaterialsModule } from './modules/pages/materials.js';
import { initCatalogsModule } from './modules/pages/catalogs.js';
import { initProductionLinesModule } from './modules/pages/production-lines.js';
import { initProductionOperations } from './modules/pages/production-operations.js';
import { initTaskPageModule } from './modules/pages/task.js';
import { initUsersModule } from './modules/pages/users.js';

document.addEventListener('DOMContentLoaded', () => {
   initFiltersModule();
   initMaterialMovementsModule();
   initMaterialRollsModule();
   initMaterialReceiptModule();
   initMaterialIssueModule();
   initMaterialAdjustmentModule();
   initReceiptRolls();
   initMaterialsModule();
   initCatalogsModule();
   initProductionLinesModule();
   initProductionOperations();
   initProductionOperationModule();
   initTasksFormModule();
   initTaskPageModule();
   initUsersModule();
});
