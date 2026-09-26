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
 * - произведённые рулоны: каждая строка сохраняется сразу по AJAX,
 *   «+ Ещё рулон» и «Убрать» тоже уходят на сервер без перезагрузки.
 */
import { initSelects } from '../../assets/select.js';

export function initTaskPageModule() {
   // Модалка смены статуса нужна на любой задаче —
   // в том числе выполненной, где формы завершения нет
   initStatusModal();

   const form = document.querySelector('[data-task-complete]');

   if (!form) {
      return;
   }

   initSelects(form);

   initRollWeights(form);
   initOutputRolls(form);
   initInputForms(form);
   initRollTakeWeight();
}

/**
 * Кнопка «Изменить статус»: открывает модалку с выбором статуса.
 * Доступна только пользователям с правом tasks,status — кнопка
 * рендерится на сервере, здесь только модалка и отправка формы.
 */
function initStatusModal() {
   document.addEventListener('click', (event) => {
      const trigger = event.target.closest?.('[data-task-status-open]');

      if (!trigger || !window.operationModal) {
         return;
      }

      event.preventDefault();

      let statuses = {};

      try {
         statuses = JSON.parse(trigger.dataset.statuses || '{}');
      } catch {
         statuses = {};
      }

      const current = trigger.dataset.current;
      const taskId = trigger.dataset.taskId;
      const csrfToken =
         document.querySelector('meta[name="csrf-token"]')?.content || '';

      const options = Object.entries(statuses)
         .filter(([value]) => value !== 'cancelled')
         .map(
            ([value, label]) => `
               <label style="display: flex; gap: 0.8rem; align-items: center;
                       padding: 0.6rem 0.8rem; border-radius: var(--border-radius);
                       cursor: pointer; background: var(--background-gray);">
                  <input type="radio" name="status" value="${value}"
                        ${value === current ? 'checked' : ''}>
                  <span>${label}</span>
               </label>
            `
         )
         .join('');

      const html = `
         <div class="material-receipt">
            <div class="material-receipt__header">
               <h2 class="main-content__title">Изменить статус задачи</h2>
            </div>

            <form method="POST" action="/tasks/${taskId}/status">
               <input type="hidden" name="_token" value="${csrfToken}">

               <div class="material-receipt__content"
                       style="display: flex; flex-direction: column; gap: 0.5rem;">
                  ${options}
               </div>

               <div style="display: flex; gap: 1.5rem; margin-top: 1.5rem;">
                  <button class="button button--primary" type="submit">
                     <span>Сохранить</span>
                  </button>

                  <button class="button" type="button" data-operation-modal-close>
                     <span>Отмена</span>
                  </button>
               </div>
            </form>
         </div>
      `;

      window.operationModal.open(html);
   });
}

/**
 * CSRF-токен страницы.
 */
function csrfToken() {
   return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

/**
 * Индикатор сохранения в заголовке секции.
 *
 * @param {string} text
 * @param {boolean} isError
 * @param {string} selector
 */
function setStatus(text, isError = false, selector = '[data-task-save-status]') {
   const status = document.querySelector(selector);

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
 * Обновляет строку прогресса «сделано из плана, осталось N».
 *
 * @param {number} made
 * @param {number} left
 */
function updateProgress(made, left) {
   const block = document.querySelector('[data-task-progress]');

   if (!block) {
      return;
   }

   const trim = (value) => String(Math.round(value * 1000) / 1000);

   block.querySelector('[data-task-progress-made]').textContent = trim(made);

   const leftSpan = block.querySelector('[data-task-progress-left]');
   leftSpan.textContent = `осталось ${trim(left)}`;
   leftSpan.classList.remove('text-red-soft');
   leftSpan.style.color = 'var(--text-muted)';
}

/**
 * AJAX-запрос с JSON-телом и CSRF-токеном.
 *
 * @param {string} url
 * @param {string} method
 * @param {Object|null} body
 */
function jsonRequest(url, method, body = null) {
   return fetch(url, {
      method,
      keepalive: true,
      headers: {
         'Content-Type': 'application/json',
         Accept: 'application/json',
         'X-Requested-With': 'XMLHttpRequest',
         'X-CSRF-TOKEN': csrfToken(),
      },
      body: body === null ? null : JSON.stringify(body),
   }).then(async (response) => {
      if (response.ok) {
         return response.json().catch(() => ({}));
      }

      throw new Error(
         firstError(await response.json().catch(() => ({}))) ||
            'Не удалось сохранить.'
      );
   });
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

      jsonRequest(`/tasks/${taskId}/inputs/${inputId}`, 'PUT', { used })
         .then(() => setStatus('Сохранено'))
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
 * Произведённые рулоны: каждая строка автосохраняется по AJAX
 * (PUT tasks.outputs.update), «+ Ещё рулон» и «Убрать» тоже уходят
 * на сервер; после них секция подменяется свежей разметкой.
 *
 * @param {HTMLElement} form
 */
function initOutputRolls(form) {
   let timer = null;
   let lastTarget = null;

   const taskId = form.dataset.taskId;

   const rows = () =>
      Array.from(document.querySelectorAll('[data-output-roll]'));

   /**
    * Сохраняет строку продукции.
    *
    * @param {HTMLInputElement} target
    */
   const save = (target) => {
      const row = target.closest('[data-output-roll]');
      const outputId = row?.dataset.outputId;

      if (!row || !outputId || !taskId) {
         return;
      }

      const number = row.querySelector('[data-output-number]')?.value ?? '';
      const weight = row.querySelector('[data-output-weight]')?.value ?? '';

      setStatus('Сохранение…', false, '[data-output-save-status]');

      jsonRequest(`/tasks/${taskId}/outputs/${outputId}`, 'PUT', {
         roll_number: number,
         actual_weight: weight === '' ? null : weight,
      })
         .then((data) => {
            setStatus('Сохранено', false, '[data-output-save-status]');

            if (typeof data.made === 'number') {
               updateProgress(data.made, data.left ?? 0);
            }
         })
         .catch((error) =>
            setStatus(error.message, true, '[data-output-save-status]')
         );
   };

   form.addEventListener('input', (event) => {
      const target = event.target;

      if (!target.matches?.('[data-output-number], [data-output-weight]')) {
         return;
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

      if (!target.matches?.('[data-output-number], [data-output-weight]')) {
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

   // «+ Ещё рулон»: строка создаётся на сервере, секция обновляется.
   document.addEventListener('click', (event) => {
      if (event.target.closest?.('[data-output-roll-add]')) {
         event.preventDefault();

         setStatus('Сохранение…', false, '[data-output-save-status]');

         jsonRequest(`/tasks/${taskId}/outputs`, 'POST')
            .then(() => refreshSection('[data-task-outputs-section]'))
            .then(() =>
               setStatus('Рулон добавлен', false, '[data-output-save-status]')
            )
            .catch((error) =>
               setStatus(error.message, true, '[data-output-save-status]')
            );
      }

      const removeButton = event.target.closest?.('[data-output-roll-remove]');

      if (removeButton) {
         event.preventDefault();

         const row = removeButton.closest('[data-output-roll]');

         // Хотя бы одна строка остаётся — завершение без продукции невозможно.
         if (!row || rows().length <= 1) {
            return;
         }

         setStatus('Сохранение…', false, '[data-output-save-status]');

         jsonRequest(`/tasks/${taskId}/outputs/${row.dataset.outputId}`, 'DELETE')
            .then(() => refreshSection('[data-task-outputs-section]'))
            .then(() =>
               setStatus('Рулон убран', false, '[data-output-save-status]')
            )
            .catch((error) =>
               setStatus(error.message, true, '[data-output-save-status]')
            );
      }
   });
}

/**
 * Поле «Вес рулона» при дозаборе: у обычного рулона
 * подставляется доступный вес, у рулона «Общий вес» остаётся
 * пустым — оператор указывает вес сам.
 */
function initRollTakeWeight() {
   document.addEventListener('select:change', (event) => {
      const selectEl = event.target.closest?.('[data-task-roll-select]');

      if (!selectEl) {
         return;
      }

      const weightInput = selectEl.parentElement?.querySelector(
         '[data-roll-take-weight]'
      );

      if (!weightInput) {
         return;
      }

      const option = event.detail?.option;

      weightInput.value =
         option && option.dataset.shared !== '1'
            ? option.dataset.available || ''
            : '';
   });
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

            return refreshSection('[data-task-inputs-section]');
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
 * Подменяет секцию свежей разметкой текущей страницы.
 *
 * @param {string} selector
 */
async function refreshSection(selector) {
   const response = await fetch(window.location.href, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
   });

   if (!response.ok) {
      throw new Error('Не удалось обновить секцию.');
   }

   const html = await response.text();
   const fresh = new DOMParser()
      .parseFromString(html, 'text/html')
      .querySelector(selector);
   const current = document.querySelector(selector);

   if (fresh && current) {
      current.replaceWith(fresh);
      initSelects(fresh);
   }
}
