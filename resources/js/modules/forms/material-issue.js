import { CustomSelect } from '../../assets/select.js';
import { RollsApiService } from '../../services/rollsApi.js';

export function initMaterialIssueModule() {
   const form = document.querySelector('.issue-order');
   if (!form) return;

   const selects = form.querySelectorAll('.material-select');
   const materialSelectEl = selects[0];
   const rollSelectEl = selects[1];

   if (!materialSelectEl || !rollSelectEl) return;

   const materialSelect = new CustomSelect(materialSelectEl);
   const rollSelect = new CustomSelect(rollSelectEl, {
      placeholder: 'Сначала выберите материал',
   });

   const remainingWeightInput = form.querySelector('#remaining_weight');
   const weightInput = form.querySelector('#weight');
   const rollList = rollSelectEl.querySelector('#rolls-list');
   const rollEmptyMsg = rollSelectEl.querySelector('.select__empty');

   // Обработка выбора материала -> динамическая загрузка рулонов
   materialSelectEl.addEventListener('select:change', async (e) => {
      const materialId = e.detail.value;

      resetRollState();

      if (!materialId) {
         rollSelect.selectOption(null);
         return;
      }

      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Загрузка рулонов...';
      }

      const rolls = await RollsApiService.fetchByMaterial(materialId);
      renderRolls(rolls);
   });

   // Обработка выбора рулона -> заполнение ограничения веса
   rollSelectEl.addEventListener('select:change', (e) => {
      const option = e.detail.option;
      const weight = option?.dataset.weight || '';

      if (remainingWeightInput) remainingWeightInput.value = weight;
      if (weightInput) weightInput.max = weight;
   });

   // Кнопка сброса формы
   const resetButton = form.querySelector('.issue-order__button--reset');
   resetButton?.addEventListener('click', () => {
      form.reset();
      materialSelect.selectOption(null);
      resetRollState();
      if (weightInput) weightInput.removeAttribute('max');
   });

   function resetRollState() {
      if (rollList) rollList.innerHTML = '';
      rollSelect.selectOption(null);
      if (remainingWeightInput) remainingWeightInput.value = '';
   }

   function renderRolls(rolls) {
      if (!rollList) return;

      if (!Array.isArray(rolls) || rolls.length === 0) {
         if (rollEmptyMsg) {
            rollEmptyMsg.textContent = 'Нет доступных рулонов';
            rollEmptyMsg.hidden = false;
         }
         if (rollSelect.valueSpan) {
            rollSelect.valueSpan.textContent = 'Нет доступных рулонов';
         }
         return;
      }

      if (rollEmptyMsg) rollEmptyMsg.hidden = true;

      rollList.innerHTML = rolls
         .map(
            (roll) => `
            <button class="material-select__select-option select__item" 
                  type="button" 
                  role="option" 
                  data-value="${roll.id}" 
                  data-roll="${roll.roll_number ?? ''}" 
                  data-weight="${roll.weight ?? ''}">
               <span>${roll.roll_number ?? ''} | ${roll.weight ?? ''} кг</span>
            </button>
            `
         )
         .join('');

      rollSelect.refresh();
      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Выберите рулон';
      }
   }
}
