import { CustomSelect, initSelects } from '../../assets/select.js';
import { RollsApiService } from '../../services/rollsApi.js';
import { confirmRowRemoval, rowRemovalText } from '../ui/row-remove-confirm.js';

export function initMaterialAdjustmentModule() {
   const form = document.querySelector('[data-adjustment-order]');

   if (!form) return;

   const rowsList = form.querySelector('[data-adjustment-rows]');
   const addButton = form.querySelector('[data-adjustment-row-add]');
   const rowTemplate = document.querySelector('[data-adjustment-row-template]');

   if (!rowsList || !addButton || !rowTemplate) return;

   const commentInput = form.querySelector('#comment');

   /*
    * Строки одного ордера: материал -> рулон -> учётный остаток ->
    * отклонение -> новый остаток. Шаблон строки рендерит сервер,
    * JS клонирует, переиндексирует и вешает логику.
    */
   const getRows = () =>
      Array.from(rowsList.querySelectorAll('[data-adjustment-row]'));

   const escapeHtml = (value) =>
      String(value)
         .replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')
         .replace(/>/g, '&gt;')
         .replace(/"/g, '&quot;')
         .replace(/'/g, '&#039;');

   const trimZeros = (value) =>
      String(value).replace(/(\.\d*?)0+$/, '$1').replace(/\.$/, '');

   // =========================================================
   // Переиндексация имён rows[i][...] после добавления/удаления
   // =========================================================

   const reindexRows = () => {
      getRows().forEach((row, index) => {
         row
            .querySelector('[data-adjustment-material]')
            ?.setAttribute('name', `rows[${index}][material_id]`);

         row
            .querySelector('[data-adjustment-roll]')
            ?.setAttribute('name', `rows[${index}][roll_id]`);

         row
            .querySelector('[data-adjustment-input]')
            ?.setAttribute('name', `rows[${index}][adjustment]`);
      });
   };

   // =========================================================
   // Пересчёт «новый остаток» строки
   // =========================================================

   const updateRowAfter = (row) => {
      const before = parseFloat(
         row.querySelector('[data-adjustment-weight-before]')?.value || ''
      );
      const adjustment = parseFloat(
         row.querySelector('[data-adjustment-input]')?.value || ''
      );

      const afterInput = row.querySelector('[data-adjustment-weight-after]');

      if (afterInput) {
         afterInput.value =
            Number.isFinite(before) && Number.isFinite(adjustment)
               ? String(Math.round((before + adjustment) * 1000) / 1000)
               : '';
      }
   };

   // =========================================================
   // Загрузка рулонов материала строки
   // =========================================================

   const resetRollCell = (row) => {
      const rollSelectEl = row.querySelector('[data-adjustment-roll-select]');
      const rollList = row.querySelector('[data-adjustment-rolls-list]');
      const emptyMsg = rollSelectEl?.querySelector('.select__empty');

      if (rollList) {
         rollList.innerHTML = '';
      }

      const rollHidden = row.querySelector('[data-adjustment-roll]');

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

      const weightBefore = row.querySelector('[data-adjustment-weight-before]');

      if (weightBefore) {
         weightBefore.value = '';
      }

      updateRowAfter(row);
   };

   const renderRolls = (row, rolls) => {
      const rollList = row.querySelector('[data-adjustment-rolls-list]');
      const rollSelectEl = row.querySelector('[data-adjustment-roll-select]');
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
                  data-roll="${escapeHtml(roll.roll_number ?? '')}"
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

      const select = new CustomSelect(rollSelectEl, {
         placeholder: 'Выберите рулон',
      });

      rollSelectEl.addEventListener('select:change', (e) => {
         const weightBefore = row.querySelector('[data-adjustment-weight-before]');

         if (weightBefore) {
            weightBefore.value = e.detail.option?.dataset.weight || '';
         }

         updateRowAfter(row);
      });
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
      initSelects(row);

      const materialSelectEl = row.querySelector('.material-select');

      materialSelectEl?.addEventListener('select:change', (e) => {
         void loadRolls(row, e.detail.value);
      });

      row
         .querySelector('[data-adjustment-input]')
         ?.addEventListener('input', () => updateRowAfter(row));

      row
         .querySelector('[data-adjustment-row-remove]')
         ?.addEventListener('click', () => {
            confirmRowRemoval({
               text: rowRemovalText(
                  row,
                  '[data-adjustment-material]',
                  '.material-select .select__button-text'
               ),
               onConfirm: () => {
                  row.remove();

                  reindexRows();
               },
            });
         });

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

         const row = index === 0 ? getRows()[0] ?? createRow() : createRow();

         if (oldRow.adjustment !== undefined && oldRow.adjustment !== null) {
            const adjustmentInput = row.querySelector('[data-adjustment-input]');

            if (adjustmentInput) {
               adjustmentInput.value = oldRow.adjustment;
            }
         }

         const materialId = String(oldRow.material_id ?? '');

         if (!materialId) {
            continue;
         }

         const materialOption = Array.from(
            row.querySelectorAll('.select__item[data-value]')
         ).find((option) => option.dataset.value === materialId);

         const materialSelectEl = row.querySelector('.material-select');
         const materialSelect = materialSelectEl
            ? new CustomSelect(materialSelectEl)
            : null;

         if (materialOption && materialSelect) {
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
               row.querySelector('[data-adjustment-roll]').value = rollId;

               const weightBefore = row.querySelector(
                  '[data-adjustment-weight-before]'
               );

               if (weightBefore) {
                  weightBefore.value = rollOption.dataset.weight || '';
               }
            }
         } catch (error) {
            console.error('Ошибка восстановления рулонов:', error);
         }

         updateRowAfter(row);
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
      // без значения отключаются вместе с корректировкой.
      getRows().forEach((row) => {
         const material = row.querySelector('[data-adjustment-material]');
         const roll = row.querySelector('[data-adjustment-roll]');
         const adjustment = row.querySelector('[data-adjustment-input]');

         const filled =
            (material?.value || '') !== '' &&
            (roll?.value || '') !== '' &&
            (adjustment?.value || '') !== '';

         if (!filled) {
            [material, roll, adjustment].forEach((input) => {
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
