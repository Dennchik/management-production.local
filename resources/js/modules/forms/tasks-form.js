/**
 * Форма производственной задачи.
 *
 * Отвечает за:
 * - каскад «линия → шаблоны производства» (оба — кастомные селекты);
 * - заполнение таблиц входных/выходных материалов по выбранному шаблону;
 * - блокировку состава материалов, взятого из шаблона;
 * - кнопку «Изменить материалы вручную»: открывает состав задачи
 *   для правки (без шаблона — все материалы, линия не выбрана).
 *
 * Добавление/удаление строк и сабмит пустых селектов обрабатывает
 * модуль production-lines.js — форма подключается его атрибутами.
 */
import { initSelects } from '../../assets/select.js';

/**
 * Экземпляр CustomSelect контейнера (инициализируются в production-lines.js).
 *
 * @param {HTMLElement|null} container
 * @returns {Object|null}
 */
function selectInstance(container) {
   return (window._activeSelects || []).find(
      (select) => select.container === container
   );
}

export function initTasksFormModule() {
   const form = document.querySelector('[data-tasks-form]');
   if (!form) return;

   const operationSelect = form.querySelector('[data-task-operation-select]');
   const templateSelect = form.querySelector('[data-task-template-select]');
   const sections = form.querySelector('[data-task-materials-sections]');
   const unlockButton = form.querySelector('[data-task-unlock-materials]');

   let templates = {};
   let allowed = {};

   try {
      const data = JSON.parse(
         form.querySelector('[data-task-form-data]')?.textContent || '{}'
      );

      templates = data.templates || {};
      allowed = data.allowed || {};
   } catch (error) {
      templates = {};
      allowed = {};
   }

   /**
    * Значение кастомного селекта — скрытый input.
    *
    * @param {HTMLElement|null} select
    * @returns {string}
    */
   function selectValue(select) {
      return select?.querySelector('.select__value')?.value || '';
   }

   /**
    * Контейнер строк материалов задачи.
    *
    * @param {string} kind input|output.
    * @returns {HTMLElement|null}
    */
   function rowsList(kind) {
      return form.querySelector(
         `[data-task-material-table="${kind}"] [data-production-line-materials]`
      );
   }

   /**
    * Показывает секции материалов задачи.
    */
   function showSections() {
      if (sections) {
         sections.hidden = false;
      }
   }

   /**
    * Блокирует/разблокирует состав материалов.
    *
    * @param {boolean} locked
    */
   function setLocked(locked) {
      form.querySelectorAll('[data-task-material-table]').forEach((wrapper) => {
         wrapper
            .querySelectorAll('.operation-form__section')
            .forEach((section) => {
               section.classList.toggle(
                  'operation-form__section--locked',
                  locked
               );
            });
      });
   }

   /**
    * Оставляет в списке шаблонов только шаблоны выбранной линии.
    * Варианты скрываются атрибутом hidden (как в фильтрации материалов).
    */
   function filterTemplateOptions() {
      const operationId = selectValue(operationSelect);

      templateSelect
         ?.querySelectorAll('.select__item[data-operation-id]')
         .forEach((option) => {
            option.hidden =
               operationId !== '' &&
               option.dataset.operationId !== operationId;
         });

      // Шаблон другой линии больше не доступен — сбрасываем выбор.
      const selectedId = selectValue(templateSelect);

      if (selectedId === '') {
         return;
      }

      const selectedOption = templateSelect?.querySelector(
         `.select__item[data-value="${selectedId}"]`
      );

      if (selectedOption && selectedOption.hidden) {
         const emptyOption = templateSelect.querySelector(
            '.select__item[data-value=""]'
         );

         const instance = selectInstance(templateSelect);

         if (instance && emptyOption) {
            instance.selectOption(emptyOption, false);
         } else {
            const hidden = templateSelect.querySelector('.select__value');

            if (hidden) {
               hidden.value = '';
            }
         }
      }
   }

   /**
    * Фильтрует варианты входных материалов по разрешённым
    * материалам выбранной линии; без линии — все материалы.
    * Поиск CustomSelect управляет style.display и не снимает фильтр.
    */
   function filterInputOptions() {
      const operationId = operationSelect?.value || '';
      const allowedIds =
         operationId !== '' ? allowed[operationId] || [] : null;

      form
         .querySelectorAll(
            '[data-task-material-table="input"] .select__item[data-value]'
         )
         .forEach((option) => {
            option.hidden =
               allowedIds !== null &&
               !allowedIds.includes(String(option.dataset.value));
         });
   }

   /**
    * Убирает отметки выбранных вариантов в клоне строки.
    *
    * @param {HTMLElement} row
    */
   function clearOptionStates(row) {
      row.querySelectorAll('.select__item').forEach((option) => {
         option.classList.remove('_selected');
         option.setAttribute('aria-selected', 'false');
      });
   }

   /**
    * Заполняет строку материала данными шаблона.
    *
    * @param {HTMLElement} row
    * @param {Object|null} material Материал+формат шаблона или null для пустой строки.
    */
   function applyRow(row, material) {
      const valueInput = row.querySelector('.select__value');
      const formatInput = row.querySelector('.select__format-value');
      const buttonText = row.querySelector('.select__button-text');
      const identifierCell = row.querySelector('[data-cell-identifier]');
      const formatCell = row.querySelector('[data-cell-format]');

      if (material) {
         if (valueInput) {
            valueInput.value = material.id;
         }

         if (formatInput) {
            formatInput.value = material.format ?? '';
         }

         if (buttonText) {
            buttonText.textContent = material.label || material.name;
         }

         if (identifierCell) {
            identifierCell.textContent = material.identifier || '—';
         }

         if (formatCell) {
            formatCell.textContent = material.format || '—';
         }

         // Вариант выбирается по материалу И формату.
         const option = Array.from(
            row.querySelectorAll('.select__item[data-value]')
         ).find(
            (item) =>
               item.dataset.value === String(material.id) &&
               (item.dataset.format || '') === String(material.format ?? '')
         );

         if (option) {
            row.querySelectorAll('.select__item').forEach((item) => {
               item.classList.remove('_selected');
               item.setAttribute('aria-selected', 'false');
            });

            option.classList.add('_selected');
            option.setAttribute('aria-selected', 'true');
         }
      } else {
         if (valueInput) {
            valueInput.value = '';
         }

         if (formatInput) {
            formatInput.value = '';
         }

         if (buttonText) {
            buttonText.textContent = 'Выберите материал';
         }

         if (identifierCell) {
            identifierCell.textContent = '';
         }

         if (formatCell) {
            formatCell.textContent = '';
         }
      }
   }

   /**
    * Пересчитывает порядковые номера строк.
    *
    * @param {HTMLElement} list
    */
   function renumber(list) {
      list.querySelectorAll('[data-line-index]').forEach((cell, index) => {
         cell.textContent = String(index + 1);
      });
   }

   /**
    * Заменяет строки таблицы материалов данными шаблона.
    *
    * @param {string} kind input|output.
    * @param {Array} materials Материалы шаблона (материал+формат).
    */
   function fillTable(kind, materials) {
      const list = rowsList(kind);
      if (!list) return;

      const template = list.querySelector('[data-production-line-material]');
      if (!template) return;

      list.innerHTML = '';

      const items = materials.length > 0 ? materials : [null];

      items.forEach((material) => {
         const row = template.cloneNode(true);

         clearOptionStates(row);
         applyRow(row, null);

         list.appendChild(row);

         // Инициализация на пустой строке, затем — значение и формат.
         initSelects(row);

         if (material) {
            applyRow(row, material);
         }
      });

      renumber(list);
   }

   /**
    * Выбор шаблона: заполняет состав и блокирует его.
    */
   function onTemplateChange() {
      const lineId = selectValue(templateSelect);
      const lineTemplate = templates[lineId];

      if (!lineId || !lineTemplate) {
         return;
      }

      fillTable('input', lineTemplate.inputs || []);
      fillTable('output', lineTemplate.outputs || []);

      showSections();
      setLocked(true);
      filterInputOptions();
   }

   // Каскад «линия → шаблоны» на кастомных селектах.
   operationSelect?.addEventListener('select:change', () => {
      filterTemplateOptions();
      filterInputOptions();
   });

   templateSelect?.addEventListener('select:change', onTemplateChange);

   unlockButton?.addEventListener('click', () => {
      showSections();
      setLocked(false);
   });

   // Начальное состояние: шаблон выбран — состав показан заблокированным.
   filterTemplateOptions();

   if (selectValue(templateSelect)) {
      showSections();
      setLocked(true);
      filterInputOptions();
   }
}
