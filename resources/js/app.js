import './modules/ui/modal.js';
import './modules/ui/messages.js';
import './modules/ui/clickable-rows.js';
import './modules/ui/sidebar.js';

//* Импортируем формы
import { initFiltersModule } from './modules/forms/filters.js';
import { initMaterialReceiptModule } from './modules/forms/material-receipt.js';
import { initMaterialIssueModule } from './modules/forms/material-issue.js';
import { initReceiptRolls } from './modules/ui/receipt-rolls.js';
//* Импортируем страниц
import { initMaterialRollsModule } from './modules/pages/material-rolls.js';
import { initMaterialMovementsModule } from './modules/pages/material-movements.js';
document.addEventListener('DOMContentLoaded', () => {
   // Безопасный запуск: инициализируются только те модули,
   // чьи HTML-элементы реально присутствуют на текущей странице.
   initFiltersModule();
   initMaterialMovementsModule();
   initMaterialRollsModule();
   initMaterialReceiptModule();
   initMaterialIssueModule();
   initReceiptRolls();
});
//* Импортируем утилиты для инициализации
// import { initSelects } from './assets/select.js';

// ... остальные импорты

//* Инициализация UI-компонентов
// document.addEventListener('DOMContentLoaded', () => {
//    initSelects();
// });
