# Карта проекта management-production.local

> Laravel 13 (PHP 8.4), монолит на Blade + Alpine.js + Vite. Система управления производством и складом рулонных материалов (полиграфия/ламинирование). Без админ-панелей и внешних бизнес-пакетов — вся логика в контроллерах и моделях.

---

## 1. Назначение

- Учёт материалов: приход/расход/рулоны/движения.
- Производство: операции, линии, маршруты, задачи (jobs), исполнения.
- Заказы, каталоги, настройки.
- Пользователи + собственная ролевая модель (permissions, не Laravel-gate).
- Документация по этапам разработки: `Instruction/` (manual.md, plan.md и др.), `README.md` — общее описание Laravel.

## 2. Роуты (`routes/web.php`, всё в одном файле)

Middleware-алиасы в `bootstrap/app.php`:
- `auth.session` → `EnsureAuthenticated`
- `can.do` → `EnsurePermission` (сигнатура `can.do:object[,action]`)

| Группа | URL | Контроллер | Права |
|---|---|---|---|
| guest | `GET/POST /login` | AuthController | — |
| auth | `POST /logout` | — | auth.session |
| Задачи | `/tasks` CRUD + `start`/`complete` | ProductionTaskController | `can.do:tasks[,create\|edit]` |
| Операции | `/production/operations` CRUD | ProductionOperationController | — |
| Линии | `/production/lines/{operation}...` | вложенные в operations | — |
| Движения | `/material-movements` | MaterialMovementController | `can.do:warehouse` |
| Склад | `/warehouse`, `/warehouse/materials/{material}` | WarehouseController | — |
| Приходы | `/receipts` | MaterialReceiptController | `can.do:warehouse` |
| Расходы | `/issues` | MaterialIssueController | `can.do:warehouse` |
| Рулоны | `/rolls`, `/rolls/{roll}` | MaterialRollController | `can.do:rolls` |
| Материалы | `/materials` CRUD + `create-choice` | MaterialController | `can.do:materials` |
| Каталоги | `/catalogs` CRUD + `hierarchy-toggle` | CatalogController | — |
| Пользователи | `/users` | UserController | `can.do:users` |
| Роли | `/roles`, `/roles/{role}/edit` | RoleController | `can.do:users` |
| API | `GET /api/rolls` (по материалу) | MaterialRollController@getRollsByMaterial | без can.do |

## 3. Контроллеры (`app/Http/Controllers/`, все в корне)

- `AuthController` — логин/выход.
- `DashboardController` — главная.
- `ProductionTaskController` — CRUD задач + жизненный цикл start/complete.
- `ProductionOperationController` — CRUD операций + управление линиями (storeLine/updateLine/destroyLine).
- `MaterialMovementController` — журнал движений.
- `WarehouseController` — сводка склада + карточка материала.
- `MaterialReceiptController` / `MaterialIssueController` — приходные/расходные накладные.
- `MaterialRollController` — рулоны + AJAX-выдача рулонов по материалу.
- `MaterialController` — справочник материалов + выбор типа при создании.
- `CatalogController` — иерархические каталоги (AJAX store/update/destroy, toggleHierarchy).
- `UserController`, `RoleController` — пользователи и права.

Паттерн: валидация в контроллере (`$request->validate`), Form Request-классов нет; store/update/destroy часто возвращают JsonResponse (AJAX UI), show/delete — View; удаление через GET-форму подтверждения (`/delete` → view → DELETE).

## 4. Модели (`app/Models/`)

**Пользователи/права**
- `User` — belongsTo Role, Machine; `may(object, action)` — проверка прав; fillable через PHP-атрибуты `#[Fillable]`/`#[Hidden]` (laravel/pao).
- `Role` — hasMany RolePermission; `RolePermission` (пары object/action).

**Материалы/склад**
- `Catalog` — дерево (parent/children), hasMany Material; хелперы `hierarchyEnabled()`, `selectableParents()`, `descendantIds()`, `pathMap()`.
- `Material` — belongsTo Catalog; belongsToMany ProductionOperation; hasMany MaterialRoll, MaterialReceipt, MaterialIssue.
- `MaterialRoll` — belongsTo Material.
- `MaterialReceipt` / `MaterialReceiptItem` — накладная + позиции.
- `MaterialIssue` — belongsTo Material, MaterialRoll, User.

**Производство**
- `ProductionOperation` — belongsToMany Material (allowedOperations); hasMany ProductionLine, ProductionRouteStep, ProductionOperationComponent.
- `ProductionOperationComponent` — hasMany inputs/outputs (`ProductionOperationInput`/`Output`, оба → material/roll).
- `ProductionLine` — belongsTo Operation; belongsToMany Material с pivot direction (inputMaterials()/outputMaterials()).
- `ProductionRoute` / `ProductionRouteStep` — маршруты; step hasMany executions.
- `ProductionJob` — belongsTo Route; hasMany ProductionOperationExecution; execution hasMany inputs/outputs.
- `ProductionTask` — belongsTo Order, Material, Machine, User; hasMany TaskInput/TaskOutput; `isEditable()`, `statusLabel()`, `recipeInputs()`.

**Прочее**
- `Order` / `OrderItem` — `nextNumber()`, `isClosed()`, `statusLabel()`, `productionRecipes()`.
- `Machine`, `Setting`.

## 5. База данных (42 миграции, `database/migrations/`)

Домены: users/cache/jobs (база) → materials (+ rolls, receipts, receipt_items, issues, серия add_fields_to_*) → lamination_orders → production (operations, routes, route_steps, jobs, executions, inputs/outputs) → компоненты операций + material_production_operation → catalogs/settings/orders → production_lines + line_material (+direction).

Стиль: эволюционный — новая функциональность добавляется отдельными миграциями `add_fields_to_*`, старые не переписываются.

Seeders: `DatabaseSeeder`, `MaterialSeeder`, `UserSeeder`. Фабрика только `UserFactory`. СУБД по умолчанию — SQLite (`.env.example`), session/cache/queue — database.

## 6. Доменная логика

- **Нет** Services/Actions/Repositories/Jobs/Events/Policies — вся бизнес-логика в контроллерах и моделях.
- Права: middleware `EnsurePermission` → `User::may()` → таблица `role_permissions` (object/action). При отказе — 403 «Недостаточно прав».
- При добавлении нового раздела: добавить права в RoleController/сеялки, middleware `can.do:раздел` на роуты, пункты в sidebar (`partials`).

## 7. Фронтенд

**Views** (`resources/views/`, папки по доменам): auth, dashboard, materials, material-receipts, material-issues, material-rolls, material-movements, warehouse, production, tasks, catalogs, users, roles, layouts, partials.

**JS** (`resources/js/`):
- вход `app.js` (Alpine.js);
- `modules/forms/` — filters, material-issue, material-operation, material-receipt;
- `modules/pages/` — catalogs, materials, material-movements, material-rolls, production-lines, production-operations;
- `modules/ui/` — clickable-rows, messages, modal, receipt-rolls, sidebar;
- `services/rollsApi.js`.

**SCSS** (`resources/scss/`): `app.scss` + core/ (переменные, mixins, reset, шрифты, ui), components/, layouts/, modules/, pages/, partials/.

**Vite** (`vite.config.js`): laravel-vite-plugin (inputs scss+js), кастомные таски `vite/tasks/` (fontsStyle.js — генерация шрифтов, webp.js — конвертация картинок), PostCSS (autoprefixer, sort-media-queries), alias `@` → resources/, manualChunks vendor/utils/charts, выход `public/build`.

Runtime npm-зависимости: alpinejs, gsap, swiper. `scripts/open-browser.cmd` — Windows-хелпер для composer dev/preview.

## 8. Тесты

Практически отсутствуют: только ExampleTest в Feature/Unit. `composer test` → `artisan test`.

## 9. Конвенции

- Табы вместо пробелов; русские комментарии и flash-сообщения.
- Заголовки-разделители `/*====| Название |====*/` в файлах моделей.
- Собственная RBAC вместо Spatie/Policies.
- Windows-ориентированная разработка.
- Новые поля таблиц — отдельными миграциями.

## 10. Типовые сценарии изменений (шпаргалка)

**Новый CRUD-раздел «X»:**
1. Миграция + модель в `app/Models`.
2. Контроллер в `app/Http/Controllers` (валидация в методах, store/update — JsonResponse).
3. Роуты в `routes/web.php` внутри группы `auth.session` с `can.do:x`.
4. Views в `resources/views/x/` (layout из `layouts`, модалка из `partials`).
5. JS-модуль при необходимости в `resources/js/modules/pages/`, подключить в точке входа.
6. SCSS в `resources/scss/pages/`.
7. Права: добавить object в редактирование ролей + `User::may` заработает автоматически.

**Новое поле в существующей таблице:** отдельная миграция `add_x_to_<table>_table`, обновить `$fillable`/атрибуты модели, формы и валидацию контроллера, view.

**Новая роль/право:** строки в `role_permissions` (object+action), UI редактирования — `roles/{id}/edit`.
