/**
 * Страница пользователя: кастомный селект роли.
 */
import { initSelects } from '../../assets/select.js';

export function initUsersModule() {
   const form = document.querySelector('[data-users-form]');

   if (!form) {
      return;
   }

   initSelects(form);
}
