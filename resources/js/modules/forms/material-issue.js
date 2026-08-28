import { CustomSelect } from '../../assets/select.js';
import { RollsApiService } from '../../services/rollsApi.js';

export function initMaterialIssueModule() {
   const form = document.querySelector('.issue-order');

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
   const materialIdentifierInput = form.querySelector('#material_identifier');

   const remainingWeightInput = form.querySelector('#remaining_weight');

   const weightInput = form.querySelector('#weight');

   const rollList = rollSelectEl.querySelector('#rolls-list');

   const rollEmptyMsg = rollSelectEl.querySelector('.select__empty');

   /*
    * Материал -> загрузка доступных рулонов.
    */
   materialSelectEl.addEventListener('select:change', async (e) => {
      const option = e.detail.option;
      const materialId = e.detail.value;

      fillMaterialInfo(option);

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
    * Выбор рулона -> отображение текущего остатка
    * и установка максимального веса расхода.
    */
   rollSelectEl.addEventListener('select:change', (e) => {
      const option = e.detail.option;

      setRollWeight(option);
   });

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

      if (materialIdentifierInput) {
         materialIdentifierInput.value = '';
      }

      if (weightInput) {
         weightInput.removeAttribute('max');
      }
   });

   /*
    * Восстановление формы после неудачной отправки.
    *
    * Laravel возвращает старые значения через withInput().
    * Hidden input material_id уже содержит old('material_id'),
    * а roll_id содержит old('roll_id').
    *
    * CustomSelect восстанавливает визуальный выбор материала,
    * но намеренно не вызывает select:change.
    * Поэтому здесь вручную запускаем необходимую логику.
    */
   restoreFormState();

   /*
    * Заполняет информацию о выбранном материале.
    */
   function fillMaterialInfo(option) {
      if (!option) {
         if (materialNameInput) {
            materialNameInput.value = '';
         }

         if (materialIdentifierInput) {
            materialIdentifierInput.value = '';
         }

         return;
      }

      if (materialNameInput) {
         materialNameInput.value = option.dataset.name || '';
      }

      if (materialIdentifierInput) {
         materialIdentifierInput.value = option.dataset.identifier || '';
      }
   }

   /*
    * Сбрасывает состояние рулона.
    */
   function resetRollState() {
      if (rollList) {
         rollList.innerHTML = '';
      }

      rollSelect.selectOption(null);

      if (remainingWeightInput) {
         remainingWeightInput.value = '';
      }

      if (weightInput) {
         weightInput.removeAttribute('max');
      }

      if (rollEmptyMsg) {
         rollEmptyMsg.hidden = true;
         rollEmptyMsg.textContent = 'Нет доступных рулонов';
      }

      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Сначала выберите материал';
      }
   }

   /*
    * Отображает загруженные рулоны.
    */
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
data-roll="${escapeHtml(roll.roll_number ?? '')}"
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

      /*
       * Обновляем DOM-список внутри CustomSelect.
       */
      rollSelect.refresh();

      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Выберите рулон';
      }
   }

   /*
    * Состояние ошибки загрузки рулонов.
    */
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

   /*
    * Отображает остаток выбранного рулона
    * и ограничивает поле веса расхода.
    */
   function setRollWeight(option) {
      const weight = option?.dataset.weight || '';

      if (remainingWeightInput) {
         remainingWeightInput.value = weight;
      }

      if (weightInput) {
         if (weight) {
            weightInput.max = weight;
         } else {
            weightInput.removeAttribute('max');
         }
      }
   }

   /*
    * Восстанавливает состояние формы после
    * возврата Laravel с withInput().
    */
   async function restoreFormState() {
      const materialId = materialSelect.hiddenInput?.value || '';
      const rollId = rollSelect.hiddenInput?.value || '';

      if (!materialId) {
         return;
      }

      /*
       * Находим материал, который был выбран до отправки.
       */
      const materialOption = materialSelect.optionsList.find(
         (option) => option.dataset.value === materialId
      );

      if (!materialOption) {
         return;
      }

      /*
       * Восстанавливаем визуальный выбор материала.
       * triggerEvent=false, чтобы не создавать лишние события.
       */
      materialSelect.selectOption(materialOption, false);

      /*
       * Заполняем readonly-поля материала.
       */
      fillMaterialInfo(materialOption);

      /*
       * Показываем состояние загрузки рулонов.
       */
      if (rollSelect.valueSpan) {
         rollSelect.valueSpan.textContent = 'Загрузка рулонов...';
      }

      try {
         const rolls = await RollsApiService.fetchByMaterial(materialId);

         renderRolls(rolls);

         /*
          * После загрузки рулонов восстанавливаем
          * ранее выбранный рулон.
          */
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

         /*
          * Восстанавливаем остаток и ограничение веса.
          */
         setRollWeight(rollOption);
      } catch (error) {
         console.error('Ошибка восстановления рулонов:', error);

         renderRollsError();
      }
   }

   /*
    * Безопасная вставка значений API в HTML.
    */
   function escapeHtml(value) {
      return String(value)
         .replace(/&/g, '&amp;')
         .replace(/</g, '&lt;')
         .replace(/>/g, '&gt;')
         .replace(/"/g, '&quot;')
         .replace(/'/g, '&#039;');
   }
}
