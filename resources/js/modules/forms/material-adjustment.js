import { CustomSelect } from '../../assets/select.js';
import { RollsApiService } from '../../services/rollsApi.js';

export function initMaterialAdjustmentModule() {
   const form = document.querySelector('[data-adjustment-order]');

   if (!form) return;

   const selects = form.querySelectorAll('.material-select');

   const materialSelectEl = selects[0];
   const rollSelectEl = selects[1];

   if (!materialSelectEl || !rollSelectEl) {
      return;
   }

   const materialSelect = new CustomSelect(materialSelectEl);

   const rollSelect = new CustomSelect(rollSelectEl, {
      placeholder: 'Сначала выберите материал',
   });

   const materialNameInput = form.querySelector('#material_name');
   const weightBeforeInput = form.querySelector('#weight_before');
   const adjustmentInput = form.querySelector('#adjustment');
   const weightAfterInput = form.querySelector('#weight_after');

   const rollList = rollSelectEl.querySelector('#rolls-list');
   const rollEmptyMsg = rollSelectEl.querySelector('.select__empty');

   /*
    * Материал -> загрузка рулонов материала.
    */
   materialSelectEl.addEventListener('select:change', async (e) => {
      const materialId = e.detail.value;

      if (materialNameInput) {
         materialNameInput.value = e.detail.option?.dataset.name || '';
      }

      resetRollState();

      if (!materialId) {
         return;
      }

      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Загрузка рулонов...';
      }

      try {
         const rolls = await RollsApiService.fetchByMaterial(materialId);

         renderRolls(rolls);
      } catch (error) {
         console.error('Ошибка загрузки рулонов:', error);

         renderRollsError();
      }
   });

   /*
    * Выбор рулона -> текущий вес и конечный вес.
    */
   rollSelectEl.addEventListener('select:change', (e) => {
      setRollWeight(e.detail.option);
   });

   /*
    * Корректировка -> пересчёт конечного веса.
    */
   adjustmentInput?.addEventListener('input', updateWeightAfter);

   /*
    * Сброс формы.
    */
   const resetButton = form.querySelector('.issue-order__button--reset');

   resetButton?.addEventListener('click', () => {
      form.reset();

      materialSelect.selectOption(null);

      resetRollState();

      if (materialNameInput) {
         materialNameInput.value = '';
      }

      updateWeightAfter();
   });

   /*
    * Восстановление после возврата с ошибкой валидации.
    */
   void restoreFormState();

   function resetRollState() {
      if (rollList) {
         rollList.innerHTML = '';
      }

      rollSelect.selectOption(null);

      if (weightBeforeInput) {
         weightBeforeInput.value = '';
      }

      if (rollEmptyMsg) {
         rollEmptyMsg.hidden = true;
         rollEmptyMsg.textContent = 'Нет доступных рулонов';
      }

      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Сначала выберите материал';
      }

      updateWeightAfter();
   }

   function renderRolls(rolls) {
      if (!rollList) {
         return;
      }

      if (!Array.isArray(rolls) || rolls.length === 0) {
         rollList.innerHTML = '';

         if (rollEmptyMsg) {
            rollEmptyMsg.textContent = 'Нет доступных рулонов';
            rollEmptyMsg.hidden = false;
         }

         if (rollSelect.valueSpan) {
            rollSelect.valueSpan.textContent = 'Нет доступных рулонов';
         }

         return;
      }

      if (rollEmptyMsg) {
         rollEmptyMsg.hidden = true;
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
                     ${roll.format ? `ф. ${escapeHtml(roll.format)} | ` : ''}
                     ${escapeHtml(roll.weight ?? '')}
                     кг
                     </span>

                  </button>
                  `
         )
         .join('');

      rollSelect.refresh();

      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Выберите рулон';
      }
   }

   function renderRollsError() {
      if (rollList) {
         rollList.innerHTML = '';
      }

      if (rollEmptyMsg) {
         rollEmptyMsg.textContent = 'Не удалось загрузить рулоны';

         rollEmptyMsg.hidden = false;
      }

      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Не удалось загрузить рулоны';
      }
   }

   function setRollWeight(option) {
      if (weightBeforeInput) {
         weightBeforeInput.value = option?.dataset.weight || '';
      }

      updateWeightAfter();
   }

   /**
    * Конечный вес = текущий вес + корректировка.
    */
   function updateWeightAfter() {
      if (!weightAfterInput) {
         return;
      }

      const before = parseFloat(weightBeforeInput?.value || '');
      const adjustment = parseFloat(adjustmentInput?.value || '');

      weightAfterInput.value =
         Number.isFinite(before) && Number.isFinite(adjustment)
            ? String(Math.round((before + adjustment) * 1000) / 1000)
            : '';
   }

   async function restoreFormState() {
      const materialId = materialSelect.hiddenInput?.value || '';
      const rollId = rollSelect.hiddenInput?.value || '';

      if (!materialId) {
         return;
      }

      const materialOption = materialSelect.optionsList.find(
         (option) => option.dataset.value === materialId
      );

      if (!materialOption) {
         return;
      }

      materialSelect.selectOption(materialOption, false);

      if (materialNameInput) {
         materialNameInput.value = materialOption.dataset.name || '';
      }

      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Загрузка рулонов...';
      }

      try {
         const rolls = await RollsApiService.fetchByMaterial(materialId);

         renderRolls(rolls);

         if (!rollId) {
            return;
         }

         const rollOption = rollSelect.optionsList.find(
            (option) => option.dataset.value === rollId
         );

         if (!rollOption) {
            return;
         }

         rollSelect.selectOption(rollOption, false);

         setRollWeight(rollOption);
      } catch (error) {
         console.error('Ошибка восстановления рулонов:', error);

         renderRollsError();
      }

      updateWeightAfter();
   }

   function escapeHtml(value) {
      return String(value)
         .replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')
         .replace(/>/g, '&gt;')
         .replace(/"/g, '&quot;')
         .replace(/'/g, '&#039;');
   }
}
