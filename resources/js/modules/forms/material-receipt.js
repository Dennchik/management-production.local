import { CustomSelect } from '../../assets/select.js';

const MODE_STORAGE_PREFIX = 'receipt-mode:';

export function initMaterialReceiptModule() {
   const form = document.querySelector('.receipt-order');
   if (!form) return;

   const materialSelectEl = form.querySelector('.material-select:not([data-format-select])');
   if (!materialSelectEl) return;

   const materialSelect = new CustomSelect(materialSelectEl);

   const formatSelectEl = form.querySelector('[data-format-select]');
   const formatSelect = formatSelectEl
      ? new CustomSelect(formatSelectEl, { placeholder: 'Сначала выберите материал' })
      : null;

   const inputs = {
      grammage: form.querySelector('#grammage'),
      thickness: form.querySelector('#thickness'),
      identifier: form.querySelector('#identifier'),
   };

   const formatInput = form.querySelector('#format');

   const formatOptionsContainer = form.querySelector('[data-format-options]');
   const formatNewOption = form.querySelector('[data-format-new-option]');
   const formatNewField = form.querySelector('[data-format-new-field]');
   const formatNewInput = form.querySelector('[data-format-new-input]');

   const modeInput = form.querySelector('[data-receipt-mode-input]');
   const modeButtons = form.querySelectorAll('[data-receipt-mode-button]');
   const rollNumberFields = form.querySelectorAll('[data-receipt-roll-number-field]');
   const rollAddButton = form.querySelector('[data-receipt-roll-add]');
   const totalHint = form.querySelector('[data-receipt-total-hint]');
   const rollsTitle = form.querySelector('[data-receipt-rolls-title]');

   /**
    * Выбранный пункт материала (или null).
    */
   const selectedMaterialOption = () =>
      materialSelectEl.querySelector('.select__item._selected');

   /**
    * Текущее значение формата: селект или свободный ввод нового.
    */
   const currentFormat = () => {
      if (formatInput && formatInput.value === '__new__') {
         return formatNewInput?.value || '';
      }

      return formatInput?.value || '';
   };

   /**
    * Вычисляет идентификатор рулона:
    * код материала + граммаж|толщина + цифры формата.
    * Зеркалит MaterialRoll::composeIdentifier() на стороне PHP.
    */
   function composeIdentifier(option, format) {
      const code = option?.dataset.code || '';
      const value = option?.dataset.grammage || option?.dataset.thickness || '';
      const formatPart = (format || '').replace(/\D/g, '');

      const parsed = parseFloat(value);
      const valuePart = Number.isFinite(parsed)
         ? String(parsed)
              .replace('.', '')
              .replace(/^0+/, '')
              .padStart(2, '0')
         : '';

      if (!code || !valuePart || !formatPart) {
         return '';
      }

      return code + valuePart + formatPart;
   }

   /**
    * Пересчитывает readonly-поле идентификатора.
    */
   function updateIdentifier() {
      if (!inputs.identifier) return;

      inputs.identifier.value = composeIdentifier(
         selectedMaterialOption(),
         currentFormat()
      );
   }

   /**
    * Заполняет характеристики выбранного материала
    * и перестраивает список его форматов.
    */
   function fillMaterialData(option) {
      if (inputs.grammage) {
         inputs.grammage.value = option?.dataset.grammage || '';
      }

      if (inputs.thickness) {
         inputs.thickness.value = option?.dataset.thickness || '';
      }

      rebuildFormatOptions(option);
      updateIdentifier();
      applyStoredMode(option);
   }

   /**
    * Строит пункты селекта формата из существующих форматов
    * материала; пункт «Новый формат…» статический.
    */
   function rebuildFormatOptions(option) {
      if (!formatOptionsContainer || !formatSelect) return;

      let formats = [];

      try {
         formats = JSON.parse(option?.dataset.formats || '[]');
      } catch {
         formats = [];
      }

      formatOptionsContainer.innerHTML = '';

      formats.forEach((format) => {
         const item = document.createElement('button');

         item.type = 'button';
         item.className = 'material-select__select-option select__item';
         item.setAttribute('role', 'option');
         item.dataset.value = format;
         item.dataset.search = String(format);
         item.setAttribute('aria-selected', 'false');
         item.textContent = format;

         formatOptionsContainer.appendChild(item);
      });

      formatNewOption?.setAttribute(
         'aria-selected',
         'false'
      );

      const emptyMsg = formatSelectEl.querySelector('.select__empty');

      if (emptyMsg) {
         emptyMsg.textContent = formats.length
            ? 'Ничего не найдено'
            : 'Форматов ещё нет — выберите «Новый формат…»';
      }

      formatSelect.refresh();
      formatSelect.options.placeholder = option
         ? 'Выберите формат'
         : 'Сначала выберите материал';
      formatSelect.selectOption(null);
      showNewFormatField(false);
   }

   /**
    * Поле «новый формат» показывается, только когда
    * в селекте выбран пункт «Новый формат…».
    */
   function showNewFormatField(show) {
      if (formatNewField) {
         formatNewField.hidden = !show;
      }
   }

   /**
    * Режим учёта: rolls — обычный ввод номеров,
    * total_weight — весь вес в рулон «Общий вес».
    */
   function setMode(mode) {
      if (modeInput) {
         modeInput.value = mode;
      }

      modeButtons.forEach((button) => {
         button.classList.toggle('_active', button.dataset.mode === mode);
      });

      const isTotal = mode === 'total_weight';

      rollNumberFields.forEach((field) => {
         field.hidden = isTotal;
      });

      const numberColumn = form.querySelector('[data-receipt-roll-number-column]');

      if (numberColumn) {
         numberColumn.hidden = isTotal;
      }

      if (rollAddButton) {
         rollAddButton.hidden = isTotal;
      }

      if (totalHint) {
         totalHint.hidden = !isTotal;
      }

      if (rollsTitle) {
         rollsTitle.textContent = isTotal ? 'Общий вес' : 'Рулоны';
      }
   }

   /**
    * Последний режим запоминается отдельно для товара
    * и для сырья (рулонов) по типу материала.
    */
   function applyStoredMode(materialOption) {
      const type = materialOption?.dataset.type;

      if (!type) {
         setMode('rolls');
         return;
      }

      let stored = null;

      try {
         stored = localStorage.getItem(MODE_STORAGE_PREFIX + type);
      } catch {
         stored = null;
      }

      setMode(stored === 'total_weight' ? 'total_weight' : 'rolls');
   }

   function storeMode(mode) {
      const type = selectedMaterialOption()?.dataset.type;

      if (!type) return;

      try {
         localStorage.setItem(MODE_STORAGE_PREFIX + type, mode);
      } catch {
         // localStorage недоступен — режим просто не запоминается
      }
   }

   /**
    * Выбор материала пользователем.
    */
   materialSelectEl.addEventListener('select:change', (e) => {
      fillMaterialData(e.detail.option);
   });

   /**
    * Выбор формата: существующий — просто выбор,
    * «Новый формат…» — показ свободного ввода.
    */
   formatSelectEl?.addEventListener('select:change', (e) => {
      const isNew = e.detail.option?.dataset.value === '__new__';

      showNewFormatField(isNew);

      if (isNew && formatInput) {
         formatInput.value = formatNewInput?.value || '';
      }

      if (!isNew) {
         formatNewInput.value = '';
      }

      updateIdentifier();
   });

   /**
    * Ввод нового формата — в hidden input и в идентификатор.
    */
   formatNewInput?.addEventListener('input', () => {
      if (formatInput && formatInput.value === '__new__') {
         formatInput.value = formatNewInput.value;
      }

      updateIdentifier();
   });

   /**
    * Переключение режима учёта.
    */
   modeButtons.forEach((button) => {
      button.addEventListener('click', () => {
         const mode = button.dataset.mode === 'total_weight'
            ? 'total_weight'
            : 'rolls';

         setMode(mode);
         storeMode(mode);
      });
   });

   /**
    * Восстановление состояния формы после
    * возврата Laravel с ошибкой валидации.
    *
    * CustomSelect уже восстановил selected option,
    * но событие select:change при этом не вызывается.
    */
   const selectedMaterial = selectedMaterialOption();

   /*
    * Старое значение формата читается до восстановления материала:
    * перестройка списка очищает hidden input.
    */
   const oldFormat = formatInput?.value;

   if (selectedMaterial) {
      fillMaterialData(selectedMaterial);

      /*
       * Восстановление выбранного формата: существующий
       * или введённый как «новый».
       */
      if (oldFormat) {
         const match = formatSelectEl.querySelector(
            `[data-format-options] .select__item[data-value="${oldFormat}"]`
         );

         if (match) {
            formatSelect.selectOption(match, false);
         } else {
            formatSelect.selectOption(formatNewOption, false);
            showNewFormatField(true);
         }
      }
   }

   setMode(modeInput?.value === 'total_weight' ? 'total_weight' : 'rolls');

   /**
    * Очистка формы.
    *
    * Это единственное место, где форма должна
    * очищаться программно.
    */
   const resetButton = form.querySelector('[data-receipt-form-reset]');

   resetButton?.addEventListener('click', () => {
      form.reset();

      materialSelect.selectOption(null);
      formatSelect?.selectOption(null);

      Object.values(inputs).forEach((input) => {
         if (input) {
            input.value = '';
         }
      });

      if (formatInput) {
         formatInput.value = '';
      }

      if (formatNewInput) {
         formatNewInput.value = '';
      }

      showNewFormatField(false);

      const rollsList = form.querySelector('[data-receipt-rolls]');

      if (rollsList) {
         const firstRoll = rollsList.querySelector('[data-receipt-roll]');

         rollsList.innerHTML = '';

         if (firstRoll) {
            rollsList.appendChild(firstRoll);

            const rollNumberInput = firstRoll.querySelector(
               '[data-receipt-roll-number]'
            );

            const weightInput = firstRoll.querySelector(
               '[data-receipt-roll-weight]'
            );

            if (rollNumberInput) {
               rollNumberInput.value = '';
            }

            if (weightInput) {
               weightInput.value = '';
            }
         }
      }

      setMode('rolls');
   });
}
