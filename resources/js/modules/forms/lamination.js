import { CustomSelect } from '../../assets/select.js';

export function initLaminationModule() {
   const form = document.querySelector('.lamination-form');

   if (!form) return;

   const rollsUrl = form.dataset.rollsUrl || '/api/rolls';

   /**
    * Основа.
    */
   initMaterialRollPair({
      materialInputId: 'base_material_id',
      rollInputId: 'base_roll_id',
      remainingWeightId: 'base_remaining_weight',
   });

   /**
    * Материал для ламинации.
    */
   initMaterialRollPair({
      materialInputId: 'lamination_material_id',
      rollInputId: 'lamination_roll_id',
      remainingWeightId: 'lamination_remaining_weight',
   });

   /**
    * Связка:
    *
    * Материал → Рулон → Остаток.
    */
   function initMaterialRollPair({
      materialInputId,
      rollInputId,
      remainingWeightId,
   }) {
      const materialInput = form.querySelector(`#${materialInputId}`);

      const rollInput = form.querySelector(`#${rollInputId}`);

      const remainingWeightInput = form.querySelector(`#${remainingWeightId}`);

      if (!materialInput || !rollInput || !remainingWeightInput) {
         console.error('Не найдены элементы пары материала и рулона:', {
            materialInputId,
            rollInputId,
            remainingWeightId,
         });

         return;
      }

      const materialSelectElement = materialInput.closest('.material-select');

      const rollSelectElement = rollInput.closest('.roll-select');

      if (!materialSelectElement || !rollSelectElement) {
         console.error('Не найдены select-элементы:', {
            materialInputId,
            rollInputId,
         });

         return;
      }

      const materialSelect = new CustomSelect(materialSelectElement);

      const rollSelect = new CustomSelect(rollSelectElement);

      /*
       * Материал изменился.
       */
      materialSelectElement.addEventListener('select:change', async (event) => {
         const materialId = event.detail.value;

         clearRollSelect();

         remainingWeightInput.value = '';

         if (!materialId) {
            return;
         }

         await loadRolls(materialId);
      });

      /*
       * Рулон изменился.
       */
      rollSelectElement.addEventListener('select:change', (event) => {
         const rollId = event.detail.value;

         if (!rollId) {
            remainingWeightInput.value = '';

            return;
         }

         const option = rollSelectElement.querySelector(
            `.select__item[data-value="${CSS.escape(rollId)}"]`
         );

         if (!option) {
            remainingWeightInput.value = '';

            return;
         }

         const weight = option.dataset.weight;

         remainingWeightInput.value =
            weight !== undefined ? Number(weight).toFixed(3) : '';
      });

      /*
       * Очистить список рулонов.
       */
      function clearRollSelect() {
         rollInput.value = '';
         remainingWeightInput.value = '';

         const dropdown = rollSelectElement.querySelector('.select__dropdown');

         const buttonText = rollSelectElement.querySelector(
            '.select__button-text'
         );

         if (buttonText) {
            buttonText.textContent = 'Загрузка рулонов...';
         }

         dropdown.innerHTML = '';

         rollSelect.refresh();
      }

      /*
       * Загрузить рулоны.
       */
      async function loadRolls(materialId) {
         try {
            const url = `${rollsUrl}?material_id=${encodeURIComponent(
               materialId
            )}`;

            const response = await fetch(url, {
               headers: {
                  Accept: 'application/json',
               },
            });

            if (!response.ok) {
               throw new Error(`HTTP ${response.status}`);
            }

            const rolls = await response.json();

            renderRolls(rolls);
         } catch (error) {
            console.error('Ошибка загрузки рулонов:', error);

            renderRollError();
         }
      }

      /*
       * Отрисовать рулоны.
       */
      function renderRolls(rolls) {
         const dropdown = rollSelectElement.querySelector('.select__dropdown');

         dropdown.innerHTML = '';

         if (!Array.isArray(rolls) || !rolls.length) {
            dropdown.innerHTML = `
               <div class="roll-select__select-empty select__empty">
                  Рулонов в наличии нет
               </div>
            `;

            rollSelect.refresh();

            setRollButtonText('Рулонов нет');

            return;
         }

         rolls.forEach((roll) => {
            const option = document.createElement('button');

            option.className = 'roll-select__select-option select__item';

            option.type = 'button';

            option.setAttribute('role', 'option');

            option.dataset.value = roll.id;
            option.dataset.weight = roll.weight;

            option.innerHTML = `
               <span>
                  Рулон №${escapeHtml(roll.roll_number)}
                  | Остаток:
                  ${Number(roll.weight).toFixed(3)} кг
               </span>
            `;

            dropdown.appendChild(option);
         });

         rollSelect.refresh();

         setRollButtonText('Выберите рулон');
      }

      /*
       * Ошибка загрузки.
       */
      function renderRollError() {
         const dropdown = rollSelectElement.querySelector('.select__dropdown');

         dropdown.innerHTML = `
            <div class="roll-select__select-empty select__empty">
               Не удалось загрузить рулоны
            </div>
         `;

         rollSelect.refresh();

         setRollButtonText('Ошибка загрузки');
      }

      /*
       * Текст кнопки рулона.
       */
      function setRollButtonText(text) {
         const buttonText = rollSelectElement.querySelector(
            '.select__button-text'
         );

         if (buttonText) {
            buttonText.textContent = text;
         }
      }
   }

   /*
    * Безопасный вывод номера рулона.
    */
   function escapeHtml(value) {
      const div = document.createElement('div');

      div.textContent = value ?? '';

      return div.innerHTML;
   }
}
