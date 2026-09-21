# Переработка производственных задач (`/tasks`)

Решения (из ответов): статусы — **Ожидает=серый, В работе=жёлтый, Выполнена=зелёный, Отменена=красный**; оператор — необязательное поле при создании, **если пусто — заполняется текущим пользователем при старте задачи**; чипы статусов — только на страницах задач.

## 1. Миграция + модели

Новая миграция `2026_09_20_000004_...` (SQLite — с `disableForeignKeyConstraints`, при блокировке дропа FK-колонки — перестройка таблицы):
- `production_tasks`: убрать `machine_id`, добавить `operator_id` (FK → users, nullOnDelete);
- `production_task_inputs`, `production_task_outputs`: убрать `planned_weight` (больше не используется);
- `users`: убрать `machine_id`; удалить таблицу `machines`.

Модели:
- `ProductionTask`: fillable `machine_id`→`operator_id`, связь `machine()`→`operator()` (User); метод `statusClass()`: pending→`gray`, in_progress→`yellow`, done→`green`, cancelled→`red`.
- `ProductionTaskInput` / `ProductionTaskOutput`: убрать `planned_weight`.
- Удалить `app/Models/Machine.php`; из `User` убрать `machine_id`/`machine()`.
- `UserController`: убрать machines (eager load, списки, валидация). `UserSeeder`: убрать Machine.

## 2. Контроллер `ProductionTaskController` + маршруты

- routes: удалить GET `/tasks/{task}/start` (форма) и GET `/tasks/{task}/complete` (форма); оставить POST start/inputs/complete.
- `index()`: убрать станочный фильтр и `ownMachineOnly`; `with(['material','productionLine.operation','operator'])->withSum('outputs','actual_weight')`.
- `store()`/`update()`: `machine_id` → `operator_id` (`nullable|exists:users,id`); в ветке in_progress редактируемые: количество, оператор, комментарий.
- `startForm()`/`completeForm()` — удалить. `start()`: без payload — просто `status=in_progress`, `started_at`, и **если `operator_id` пуст — записать `auth()->id()`**.
- `addInput()` (дозабор): убрать `planned_weight`.
- `complete()`:
  - валидация: `inputs.*.remaining` nullable numeric (без `min:0`), `inputs.*.used` nullable numeric (запасной вариант), `outputs.*.roll_number` required string (номер теперь редактируемый), `outputs.*.actual_weight` required numeric;
  - убрать запрет «остаток > вес рулона» — остаток может уходить в минус; `remaining` берётся из поля, иначе `вес − расход`; списание MaterialIssue = расход (даже если больше исходного веса рулона); вес рулона обновляется на остаток (возможно отрицательный);
  - `roll_number` из запроса (дефолт-подстановка `{номерЗадачи}/{i}` остаётся на клиенте).
- `formOptions()`: убрать `machines`, добавить `operators` (все пользователи, по имени).

## 3. View-ы

**`tasks/index.blade.php`**: колонки `№ | Материал | Количество, кг | Оператор | Статус`. В ячейке количества: «сделано X из Y · осталось Z» (из `withSum`). Статус — чип. Убрать баннер станка.

**`tasks/show.blade.php`** — главный передел:
- инфо-таблица: Линия / Шаблон / Материал / Количество (`план · сделано · осталось`) / **Оператор** (вместо Станка) / Статус (чип) / даты / комментарий;
- `pending`: кнопка «Начать задачу» — простой POST (без страницы выбора рулонов);
- `in_progress`: обе секции внутри одной формы POST `/tasks/{task}/complete`:
  - «Взятое сырьё»: колонки `Рулон | Вес рулона, кг | Расход, кг | Остаток, кг` — по каждому взятому рулону два связанных поля (расход/остаток, пересчёт друг из друга, остаток может быть отрицательным), скрытый `id`, вес рулона в `data-roll-weight`; колонку «Планируемый вес» убрать;
  - дозабор «Взять рулон» — кастомный селект (варианты рендерит сервер, паттерн material-issue), поле «Плановый вес» убрать; мини-форма отвязана от общей через атрибут `form="..."`;
  - «Произведённые рулоны»: строки `Номер рулона` (текст, редактируемый, предзаполнен `{номер}/{i}`) + `Вес, кг` (первая строка = количество) + кнопка «Убрать» на строку; «+ Ещё рулон» добавляет строку со следующим номером; удаление не перенумеровывает введённые номера;
  - кнопка «Завершить задачу» сабмитит всё;
- `done`: сырьё показывает расход (`actual_weight`) и остаток рулона; произведённые рулоны — как сейчас.

**`tasks/_form.blade.php`**: оба филдсета «Станок» → «Оператор» (кастомный селект). Селекты «Линия» и «Шаблон производства» перевести на разметку CustomSelect (скрытый `.select__value`, `.select__item` с `data-operation-id`).

**Удалить**: `tasks/start.blade.php`, `tasks/complete.blade.php`.

**Остальные селекты → кастомный селект** (разметка по образцу `material-issues/create` + стили `its-select.scss`): `catalogs/_create`, `catalogs/_edit` (parent_id), `materials/_create`, `materials/_edit` (catalog_id, material_type), `users/edit` (role_id; филдсет Станок убрать), `users/index` — убрать колонку Станок. Проверить/обновить `catalogs.js`, `materials.js` там, где они читают эти select-ы.

## 4. JS

- Новый `resources/js/modules/pages/task.js` (+ импорт в `app.js`): связка расход↔остаток (событие `input`, округление до 3 знаков, расход = вес − остаток и наоборот, без ограничения снизу у остатка); произведённые рулоны: добавить/убрать строку, подстановка номера новой строке (перенос и доработка `initCompleteOutputs` из tasks-form.js); инициализация CustomSelect для дозабора.
- `tasks-form.js`: каскад «линия → шаблон» переписать под CustomSelect (события `select:change`, фильтрация `.select__item` через `hidden` по `data-operation-id`, сброс выбора шаблона при смене линии); `initCompleteOutputs` удалить.

## 5. SCSS

- Новый `resources/scss/components/status-chip.scss` (импорт в `app.scss`): `.status-chip` — пилюля, тонкий бордер 1px, лёгкая тень (`--shadow`), светлый фон; модификаторы `--gray/--yellow/--green/--red` (по `statusClass()` модели). Применяется только в задачах (index + show).
- Проверить, что `[hidden]` скрывает `.select__item` (для фильтрации шаблонов) — если нет, добавить правило.
- Новые строки форм — на существующих классах (`issue-order__*`), минимум новых стилей.

## 6. Проверка

- `php artisan migrate` на SQLite (если дропы упрутся — fallback через перестройку таблиц, перепроверить на `migrate:fresh --seed`);
- Tinker-рендер (многострочный код через временный файл в `storage/`): index/show всех статусов, форма, 404 старых GET-маршрутов;
- `npm run production`;
- браузерная проверка `http://localhost:8000/tasks`: создание (каскад селектов), старт без выбора рулонов, дозабор, пересчёт расход/остаток (в т.ч. минусовой остаток), добавить/убрать/переименовать произведённый рулон, завершение со списанием и оприходованием, чипы статусов, оператор.