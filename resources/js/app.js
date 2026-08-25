import './modules/ui/modal.js';
import './modules/ui/messages.js';
import './modules/ui/clickable-rows.js';
import './modules/ui/sidebar.js';

//* Импортируем формы
import './modules/forms/material-receipt.js';
import './modules/forms/material-issue.js';
import './modules/forms/filters.js';


//* Импортируем утилиты для инициализации
import { initSelects } from './assets/select.js';
//* Импортируем страницы
// import './modules/pages/material-movements.js';

// ... остальные импорты

//* Инициализация UI-компонентов
document.addEventListener('DOMContentLoaded', () => {
	initSelects();

});

