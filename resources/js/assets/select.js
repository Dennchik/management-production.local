import Collapse from './collapse.js';

export class CustomSelect {
   constructor(element, options = {}) {
      if (!element) return;
      this.container = element;
      this.options = options;

      this.initDOM();
      if (!this.button || !this.dropdown) return;

      // Инициализируем твой оригинальный Collapse
      this.collapse = new Collapse(this.dropdown, options.duration || 150);

      this.bindEvents();
      this.checkInitialState();
   }

   initDOM() {
      this.button = this.container.querySelector(
         '.select-button, .select__button'
      );

      // Находим целевой элемент с классом ._collapse
      this.dropdown = this.container.querySelector('._collapse');

      this.valueSpan = this.container.querySelector(
         '.material-select__select-value'
      );
      this.hiddenInput = this.container.querySelector(
         '#material_id, #roll_id, .select__value, input[type="hidden"]'
      );
      this.searchInput = this.container.querySelector(
         '.material-select__select-search-input, .select__search'
      );
      this.searchClear = this.container.querySelector(
         '.material-select__select-search-clear, .select__search-clear'
      );
      this.emptyMsg = this.container.querySelector(
         '.material-select__select-empty, .select__empty'
      );
      this.optionsList = Array.from(
         this.container.querySelectorAll(
            '.material-select__select-option, .select__item'
         )
      );
   }

   bindEvents() {
      // Клик по кнопке — запуск анимации через Collapse
      this.button.addEventListener('click', (e) => {
         e.preventDefault();
         e.stopPropagation();

         // Закрываем другие открытые Selects перед открытием текущего
         if (!this.isOpen()) {
            CustomSelect.closeAllExcept(this);
         }

         this.toggle();
      });

      // Закрытие при клике вне Select
      document.addEventListener('click', (e) => {
         if (!this.container.contains(e.target) && this.isOpen()) {
            this.hide();
         }
      });

      // Закрытие по Escape
      document.addEventListener('keydown', (e) => {
         if (e.key === 'Escape' && this.isOpen()) {
            this.hide();
            this.button.focus();
         }
      });

      this.bindOptionsEvents();

      this.searchInput?.addEventListener('input', (e) =>
         this.handleSearch(e.target.value)
      );
      this.searchClear?.addEventListener('click', () => this.resetSearch(true));
   }

   bindOptionsEvents() {
      this.optionsList.forEach((opt) => {
         opt.onclick = () => this.selectOption(opt);
      });
   }

   isOpen() {
      return this.dropdown?.classList.contains('_show');
   }

   toggle() {
      this.collapse.toggle();
      this.updateAria();
   }

   show() {
      this.collapse.show();
      this.updateAria(true);
   }

   hide() {
      this.collapse.hide();
      this.updateAria(false);
   }

   updateAria(state = null) {
      const isOpen = state !== null ? state : !this.isOpen();
      this.button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
   }

   selectOption(option, triggerEvent = true) {
      const value = option ? option.dataset.value || '' : '';
      const label = option
         ? option.textContent.trim()
         : this.options.placeholder || 'Выберите значение';

      if (this.hiddenInput) this.hiddenInput.value = value;
      if (this.button && 'value' in this.button) this.button.value = label;
      if (this.valueSpan) this.valueSpan.textContent = label;

      this.optionsList.forEach((item) => {
         const isSelected = item === option;
         item.classList.toggle('_selected', isSelected);
         item.setAttribute('aria-selected', isSelected ? 'true' : 'false');
      });

      this.resetSearch();
      this.hide();

      if (triggerEvent) {
         this.container.dispatchEvent(
            new CustomEvent('select:change', {
               bubbles: true,
               detail: { value, option },
            })
         );
      }
   }

   handleSearch(query) {
      const cleanQuery = query.trim().toLowerCase();
      if (this.searchClear) this.searchClear.hidden = !cleanQuery;

      if (!cleanQuery) {
         this.resetSearchDisplay();
         return;
      }

      const parts = cleanQuery.match(/[a-zа-яё]+|\d+/gi) || [];
      let visibleCount = 0;

      this.optionsList.forEach((opt) => {
         const text = opt.textContent.trim().toLowerCase();
         const match = parts.every((part) => text.includes(part));
         opt.style.display = match ? '' : 'none';
         if (match) visibleCount++;
      });

      if (this.emptyMsg) {
         this.emptyMsg.hidden = visibleCount > 0;
      }
   }

   resetSearch(focusInput = false) {
      if (this.searchInput) this.searchInput.value = '';
      if (this.searchClear) this.searchClear.hidden = true;
      this.resetSearchDisplay();
      if (focusInput) this.searchInput?.focus();
   }

   resetSearchDisplay() {
      this.optionsList.forEach((opt) => (opt.style.display = ''));
      if (this.emptyMsg) this.emptyMsg.hidden = true;
   }

   checkInitialState() {
      if (this.hiddenInput?.value) {
         const selected = this.optionsList.find(
            (opt) => opt.dataset.value === this.hiddenInput.value
         );
         if (selected) this.selectOption(selected, false);
      }
   }

   refresh() {
      this.initDOM();
      this.bindOptionsEvents();
   }

   static closeAllExcept(currentSelect) {
      if (!window._activeSelects) return;
      window._activeSelects.forEach((select) => {
         if (select !== currentSelect && select.isOpen()) {
            select.hide();
         }
      });
   }
}

//* Регистрация в реестре для закрытия соседних выпадающих списков
const originalConstructor = CustomSelect.prototype.constructor;
CustomSelect = function (...args) {
   const instance = new originalConstructor(...args);
   if (!window._activeSelects) window._activeSelects = [];
   if (instance.button && instance.dropdown) {
      window._activeSelects.push(instance);
   }
   return instance;
};
CustomSelect.prototype = originalConstructor.prototype;

export function initSelects(container = document) {
   const selects = container.querySelectorAll('.select, .material-select');
   return Array.from(selects).map((el) => new CustomSelect(el));
}
