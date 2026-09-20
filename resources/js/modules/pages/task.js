/**
 * Страница производственной задачи.
 *
 * Отвечает за:
 * - связку «расход ↔ остаток» по каждому взятому рулону:
 *   второе поле пересчитывается сам; расход больше веса рулона
 *   уводит остаток в минус;
 * - автосохранение расхода через AJAX — правки переживают
 *   обновление страницы;
 * - дозабор рулона без перезагрузки страницы (секция сырья
 *   подменяется свежей разметкой);
 * - произведённые рулоны: «+ Ещё рулон», «Убрать», редактируемые номера
 *   (новой строке подставляется «номерЗадачи/порядковый»).
 */
import { initSelects } from '../../assets/select.js';

export function initTaskPageModule() {
   const form = document.querySelector('[data-task-complete]');

   if (!form) {
      return;
   }

   initSelects(form);

   initRollWeights(form);
   initOutputRolls(form);
   initInputForms(form);
}

/**
 * CSRF-токен страницы.
 */
function csrfToken() {
   return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * Статус сохранения в заголовке секции сырья.
 *
 * @param {string} text
 * @param {boolean} isError
 */
function setStatus(text, isError = false) {
   const status = document.querySelector('[data-task-save-status]');

   if (!status || !text) {
      return;
   }

   status.textContent = text;
   status.style.color = isError ? 'var(--alarm)' : 'var(--text-muted)';

   if (!isError) {
      setTimeout(() => {
         if (status.textContent === text) {
            status.textContent = '';
         }
      }, 2500);
   }
}

/**
 * Первая ошибка из JSON-ответа Laravel.
 *
 * @param {Object} data
 * @returns {string}
 */
function firstError(data) {
   if (data?.message) {
      return data.message;
   }

   const errors = data?.errors || {};

   return Object.values(errors)[0]?.[0] || '';
}

/**
 * Связка расход ↔ остаток + автосохранение расхода.
 * Слушатели делегированы форме — работают после подмены секции сырья.
 *
 * @param {HTMLElement} form
 */
function initRollWeights(form) {
   let timer = null;
   let lastTarget = null;

   const round = (value) => String(Math.round(value * 1000) / 1000);

   /**
    * Сохраняет расход рулона на сервере.
    *
    * @param {HTMLInputElement} target
    */
   const save = (target) => {
      const row = target.closest('[data-task-roll]');
      const inputId = row?.querySelector('input[name$="[id]"]')?.value;
      const taskId = form.dataset.taskId;

      if (!row || !inputId || !taskId) {
         return;
      }

      const used = row.querySelector('[data-roll-used]')?.value ?? '0';

      setStatus('Сохранение…');

      fetch(`/tasks/${taskId}/inputs/${inputId}`, {
         method: 'PUT',
         keepalive: true,
         headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
         },
         body: JSON.stringify({ used }),
      })
         .then(async (response) => {
            if (response.ok) {
               setStatus('Сохранено');
               return;
            }

            throw new Error(
               firstError(await response.json().catch(() => ({}))) ||
                  'Не удалось сохранить.'
            );
         })
         .catch((error) => setStatus(error.message, true));
   };

   form.addEventListener('input', (event) => {
      const target = event.target;

      if (!target.matches?.('[data-roll-used], [data-roll-remaining]')) {
         return;
      }

      const row = target.closest('[data-task-roll]');
      const weight = parseFloat(row?.dataset.rollWeight || '0');
      const usedInput = row?.querySelector('[data-roll-used]');
      const remainingInput = row?.querySelector('[data-roll-remaining]');

      if (!usedInput || !remainingInput) {
         return;
      }

      if (target === usedInput) {
         remainingInput.value = round(weight - Number(usedInput.value || 0));
      } else {
         usedInput.value = round(weight - Number(remainingInput.value || 0));
      }

      lastTarget = target;
      clearTimeout(timer);
      timer = setTimeout(() => {
         lastTarget = null;
         save(target);
      }, 700);
   });

   // Уход из поля сохраняет сразу, без ожидания паузы.
   form.addEventListener('change', (event) => {
      const target = event.target;

      if (!target.matches?.('[data-roll-used], [data-roll-remaining]')) {
         return;
      }

      clearTimeout(timer);
      lastTarget = null;
      save(target);
   });

   // Закрытие страницы с несохранённой правкой — отправляем сразу.
   window.addEventListener('beforeunload', () => {
      if (lastTarget) {
         clearTimeout(timer);
         save(lastTarget);
         lastTarget = null;
      }
   });
}

/**
 * Произведённые рулоны: добавление и удаление строк.
 *
 * Номера редактируются вручную — клонируется только новая строка,
 * введённые номера при удалении не перенумеровываются.
 *
 * @param {HTMLElement} form
 */
function initOutputRolls(form) {
   const wrap = form.querySelector('[data-output-rolls]');
   const addButton = form.querySelector('[data-output-roll-add]');

   if (!wrap || !addButton) {
      return;
   }

   const rows = () => Array.from(wrap.querySelectorAll('[data-output-roll]'));

   /**
    * Переписывает имена полей по порядку строк.
    */
   const reindex = () => {
      rows().forEach((row, index) => {
         row.querySelectorAll('input[name]').forEach((input) => {
            input.name = input.name.replace(/outputs\[\d+\]/, `outputs[${index}]`);
         });
      });
   };

   addButton.addEventListener('click', () => {
      const template = rows()[0];

      if (!template) {
         return;
      }

      const row = template.cloneNode(true);

      // Новой строке — следующий номер, вес заполняет оператор.
      const numberInput = row.querySelector('input[name$="[roll_number]"]');
      const weightInput = row.querySelector('input[name$="[actual_weight]"]');

      if (numberInput) {
         numberInput.value = `${wrap.dataset.taskNumber || ''}/${rows().length + 1}`;
      }

      if (weightInput) {
         weightInput.value = '';
      }

      wrap.appendChild(row);

      numberInput?.focus();

      reindex();
   });

   wrap.addEventListener('click', (event) => {
      const removeButton = event.target.closest('[data-output-roll-remove]');

      if (!removeButton) {
         return;
      }

      // Хотя бы одна строка остаётся — завершение без продукции невозможно.
      if (rows().length <= 1) {
         return;
      }

      removeButton.closest('[data-output-roll]')?.remove();

      reindex();
   });

   reindex();
}

/**
 * Дозабор рулона: мини-формы отправляются через AJAX,
 * после успеха секция сырья подменяется свежей разметкой.
 *
 * @param {HTMLElement} form
 */
function initInputForms(form) {
   document.addEventListener('submit', (event) => {
      const miniForm = event.target.closest?.('form[data-task-input-form]');

      if (!miniForm || !document.contains(miniForm)) {
         return;
      }

      event.preventDefault();

      const button = miniForm.querySelector('button[type="submit"]');
      const errorBox = document.querySelector('[data-task-add-error]');

      if (errorBox) {
         errorBox.hidden = true;
      }

      if (button) {
         button.disabled = true;
      }

      fetch(miniForm.action, {
         method: 'POST',
         headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken(),
         },
         body: new FormData(miniForm),
      })
         .then(async (response) => {
            if (!response.ok) {
               throw new Error(
                  firstError(await response.json().catch(() => ({}))) ||
                     'Не удалось добавить рулон.'
               );
            }

            return refreshInputsSection();
         })
         .then(() => setStatus('Рулон добавлен'))
         .catch((error) => {
            if (errorBox) {
               errorBox.textContent = error.message;
               errorBox.hidden = false;
            }

            setStatus('Не добавлено', true);
         })
         .finally(() => {
            if (button) {
               button.disabled = false;
            }
         });
   });
}

/**
 * Подменяет секцию сырья свежей разметкой текущей страницы.
 */
async function refreshInputsSection() {
   const response = await fetch(window.location.href, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
   });

   if (!response.ok) {
      throw new Error('Не удалось обновить секцию.');
   }

   const html = await response.text();
   const fresh = new DOMParser()
      .parseFromString(html, 'text/html')
      .querySelector('[data-task-inputs-section]');
   const current = document.querySelector('[data-task-inputs-section]');

   if (fresh && current) {
      current.replaceWith(fresh);
      initSelects(fresh);
   }
}
