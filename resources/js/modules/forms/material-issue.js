import { CustomSelect, initSelects } from '../../assets/select.js';
import { RollsApiService } from '../../services/rollsApi.js';
import { confirmRowRemoval, rowRemovalText } from '../ui/row-remove-confirm.js';

export function initMaterialIssueModule() {
   const form = document.querySelector('[data-issue-order]');

   if (!form) return;

   const rowsList = form.querySelector('[data-issue-rows]');
   const addButton = form.querySelector('[data-issue-row-add]');
   const rowTemplate = document.querySelector('[data-issue-row-template]');

   if (!rowsList || !addButton || !rowTemplate) return;

   const commentInput = form.querySelector('#comment');

   /*
    * Строки одного ордера: материал -> рулон -> остаток -> вес расхода.
    * Шаблон строки рендерит сервер, JS клонирует, переиндексирует
    * и вешает логику.
    */
   const getRows = () =>
      Array.from(rowsList.querySelectorAll('[data-issue-row]'));

   const escapeHtml = (value) =>
      String(value)
         .replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')
         .replace(/>/g, '&gt;')
         .replace(/"/g, '&quot;')
         .replace(/'/g, '&#039;');

   // =========================================================
   // Переиндексация имён rows[i][...] после добавления/удаления
   // =========================================================

   const reindexRows = () => {
      getRows().forEach((row, index) => {
         row.querySelector('[data-issue-material]')?.setAttribute(
            'name',
            `rows[${index}][material_id]`
         );

         row.querySelector('[data-issue-roll]')?.setAttribute(
            'name',
            `rows[${index}][roll_id]`
         );

         row.querySelector('[data-issue-input]')?.setAttribute(
            'name',
            `rows[${index}][weight]`
         );
      });
   };

   // =========================================================
   // Живой экземпляр селекта: шаблонный рулон-селект помечен
   // data-select-initialized и массово не пере инициализируется
   // =========================================================

   const findSelectInstance = (container) =>
      (window._activeSelects || []).find(
         (select) => select.container === container
      ) ?? null;

   // =========================================================
   // Загрузка рулонов материала строки
   // =========================================================

   const resetRollCell = (row) => {
      const rollList = row.querySelector('[data-issue-rolls-list]');
      const rollSelectEl = row.querySelector('[data-issue-roll-select]');
      const emptyMsg = rollSelectEl?.querySelector('.select__empty');

      if (rollList) {
         rollList.innerHTML = '';
      }

      const rollHidden = row.querySelector('[data-issue-roll]');

      if (rollHidden) {
         rollHidden.value = '';
      }

      if (emptyMsg) {
         emptyMsg.hidden = true;
      }

      const valueSpan = rollSelectEl?.querySelector('.select__button-text');

      if (valueSpan) {
         valueSpan.textContent = 'Сначала выберите материал';
      }

      const weightBefore = row.querySelector('[data-issue-weight-before]');

      if (weightBefore) {
         weightBefore.value = '';
      }
   };

   const renderRolls = (row, rolls) => {
      const rollList = row.querySelector('[data-issue-rolls-list]');
      const rollSelectEl = row.querySelector('[data-issue-roll-select]');
      const emptyMsg = rollSelectEl?.querySelector('.select__empty');

      if (!rollList) return;

      if (!Array.isArray(rolls) || rolls.length === 0) {
         rollList.innerHTML = '';

         if (emptyMsg) {
            emptyMsg.textContent = 'Нет доступных рулонов';
            emptyMsg.hidden = false;
         }

         return;
      }

      if (emptyMsg) {
         emptyMsg.hidden = true;
      }

      rollList.innerHTML = rolls
         .map(
            (roll) => `
                  <button
                  class="material-select__select-option select__item"
                  type="button"
                  role="option"
                  data-value="${escapeHtml(roll.id ?? '')}"
                  data-weight="${escapeHtml(roll.weight ?? '')}"
                  aria-selected="false">

                     <span>
                     ${escapeHtml(roll.roll_number ?? '')}
                  |
                     ${escapeHtml(roll.weight ?? '')}
                  кг
                     </span>

                  </button>
                  `
         )
         .join('');

      // Селект рулона создаётся один раз на строку: при повторных
      // загрузках обновляем список опций у живого экземпляра
      const select = findSelectInstance(rollSelectEl);

      if (select) {
         select.optionsList = Array.from(
            rollSelectEl.querySelectorAll('.select__item')
         );

         select.bindOptionsEvents();
      } else {
         new CustomSelect(rollSelectEl, {
            placeholder: 'Выберите рулон',
         });
      }
   };

   const loadRolls = async (row, materialId) => {
      resetRollCell(row);

      if (!materialId) {
         return;
      }

      try {
         const rolls = await RollsApiService.fetchByMaterial(materialId);

         renderRolls(row, rolls);
      } catch (error) {
         console.error('Ошибка загрузки рулонов:', error);

         const emptyMsg = row.querySelector('.select__empty');

         if (emptyMsg) {
            emptyMsg.textContent = 'Не удалось загрузить рулоны';
            emptyMsg.hidden = false;
         }
      }
   };

   // =========================================================
   // Создание строки из шаблона
   // =========================================================

   const createRow = () => {
      const templateContent = rowTemplate.content.cloneNode(true);
      const row = templateContent.querySelector('tr');

      rowsList.appendChild(row);

      // Кастомные селекты клона инициализируются после вставки
      // (рулон-селект помечен в шаблоне как уже инициализированный —
      // его экземпляр создаётся при первой загрузке рулонов)
      initSelects(row);

      const materialSelectEl = row.querySelector(
         '[data-issue-material-select]'
      );

      materialSelectEl?.addEventListener('select:change', (e) => {
         void loadRolls(row, e.detail.value);
      });

      row.querySelector('[data-issue-roll-select]')?.addEventListener(
         'select:change',
         (e) => {
            const weight = e.detail.option?.dataset.weight || '';

            const weightBefore = row.querySelector(
               '[data-issue-weight-before]'
            );

            if (weightBefore) {
               weightBefore.value = weight;
            }

            const weightInput = row.querySelector('[data-issue-input]');

            if (weightInput) {
               weightInput.max = weight;
            }
         }
      );

      row.querySelector('[data-issue-row-remove]')?.addEventListener(
         'click',
         () => {
            confirmRowRemoval({
               text: rowRemovalText(
                  row,
                  '[data-issue-material]',
                  '[data-issue-material-select] .select__button-text'
               ),
               onConfirm: () => {
                  row.remove();

                  reindexRows();
               },
            });
         }
      );

      return row;
   };

   // =========================================================
   // Восстановление после возврата с ошибкой валидации:
   // строки из old('rows'), рулоны подгружаются заново
   // =========================================================

   const restoreRows = async () => {
      let oldRows = [];

      try {
         oldRows = JSON.parse(rowsList.dataset.oldRows || '[]') || [];
      } catch {
         oldRows = [];
      }

      if (!Array.isArray(oldRows) || oldRows.length === 0) {
         return;
      }

      for (let index = 0; index < oldRows.length; index += 1) {
         const oldRow = oldRows[index] || {};

         const row = index === 0 ? (getRows()[0] ?? createRow()) : createRow();

         if (oldRow.weight !== undefined && oldRow.weight !== null) {
            const weightInput = row.querySelector('[data-issue-input]');

            if (weightInput) {
               weightInput.value = oldRow.weight;
            }
         }

         const materialId = String(oldRow.material_id ?? '');

         if (!materialId) {
            continue;
         }

         const materialOption = Array.from(
            row.querySelectorAll('.select__item[data-value]')
         ).find((option) => option.dataset.value === materialId);

         const materialSelectEl = row.querySelector(
            '[data-issue-material-select]'
         );

         if (materialOption && materialSelectEl) {
            const materialSelect =
               findSelectInstance(materialSelectEl) ??
               new CustomSelect(materialSelectEl);

            materialSelect.selectOption(materialOption, false);
         }

         const rollId = String(oldRow.roll_id ?? '');

         if (!rollId) {
            continue;
         }

         try {
            const rolls = await RollsApiService.fetchByMaterial(materialId);

            renderRolls(row, rolls);

            const rollOption = Array.from(
               row.querySelectorAll('.select__item[data-value]')
            ).find((option) => option.dataset.value === rollId);

            if (rollOption) {
               row.querySelector('[data-issue-roll]').value = rollId;

               const weight = rollOption.dataset.weight || '';

               const weightBefore = row.querySelector(
                  '[data-issue-weight-before]'
               );

               if (weightBefore) {
                  weightBefore.value = weight;
               }

               const weightInput = row.querySelector('[data-issue-input]');

               if (weightInput) {
                  weightInput.max = weight;
               }
            }
         } catch (error) {
            console.error('Ошибка восстановления рулонов:', error);
         }
      }
   };

   // =========================================================
   // Добавление строки, сабмит, сброс
   // =========================================================

   addButton.addEventListener('click', () => {
      const row = createRow();

      reindexRows();

      row.querySelector('.select__button')?.focus();
   });

   form.addEventListener('submit', () => {
      // Незаполненные строки не отправляются: материал и рулон
      // без значения отключаются вместе с весом.
      getRows().forEach((row) => {
         const material = row.querySelector('[data-issue-material]');
         const roll = row.querySelector('[data-issue-roll]');
         const weight = row.querySelector('[data-issue-input]');

         const filled =
            (material?.value || '') !== '' &&
            (roll?.value || '') !== '' &&
            (weight?.value || '') !== '';

         if (!filled) {
            [material, roll, weight].forEach((input) => {
               if (input) {
                  input.disabled = true;
               }
            });
         }
      });
   });

   const resetButton = form.querySelector('.issue-order__button--reset');

   resetButton?.addEventListener('click', () => {
      form.reset();

      rowsList.innerHTML = '';

      createRow();

      reindexRows();

      if (commentInput) {
         commentInput.value = '';
      }
   });

   // Начальная строка + восстановление после ошибки
   createRow();
   reindexRows();

   void restoreRows();
}
