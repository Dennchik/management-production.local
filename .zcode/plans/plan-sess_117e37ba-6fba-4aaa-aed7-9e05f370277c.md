# План: производственные линии, каталоги, разрешённые операции

Все изменения — без новых миграций, на существующих структурах (`material_production_operation`, `production_line_material`).

## 1. Сайдбар: «Производственные линии (шаблоны)» → /production/operations

- `resources/views/layouts/sidebar.blade.php`:
  - Секция «Производственные линии (шаблоны)» (стр. 74–92): вместо цикла со списком каждого шаблона — один пункт-ссылка на `route('production.operations.index')`; активность при `routeIs('production.operations.*')` или `production.lines.*`.
  - Из секции «Справочники» (стр. 94–124) убрать пункт «Технологические линии» (стр. 110–116).
- `app/Providers/AppServiceProvider.php`: удалить view-composer, передающий `productionOperations` в sidebar (стр. 21–31), — больше не нужен.

## 2. Список каталогов (/catalogs): td-ссылка + убрать лишние колонки

- `resources/views/catalogs/index.blade.php`:
  - Колонка «Каталог»: весь `td` — ссылка `<a href="{{ route('materials.index', ['catalog' => $catalog->id]) }}">` на всю ячейку (block), с сохранением отступа дерева `--catalog-depth`.
  - Удалить колонки «Родительский каталог», «Сортировка», «Статус» (th + td); обновить `colspan` пустого состояния (7 → 4).
- `app/Http/Controllers/CatalogController.php` `index()`: добавить `withCount('materials')` — колонка «Материалов» остаётся, уходит N+1-запрос.

## 3. Сортировка по умолчанию 500

- `database/seeders/MaterialSeeder.php` (`seedCatalogNode`, стр. 33–36): fallback `sort_order` с 0 на 500.
- `app/Http/Controllers/CatalogController.php` `store()` (стр. 49): `?? 500` вместо `?? 0`.
- `resources/views/catalogs/_create.blade.php` (стр. 31): `value="500"`.

## 4. Автоимя линии из выходного материала (/production/lines/{id}/create)

- `resources/views/production/operations/_line-materials-table.blade.php`: добавить в `<option>` атрибут `data-name` с именем материала (сейчас есть только data-value/identifier/format).
- `resources/js/modules/pages/production-lines.js`: на странице создания линии при выборе опции в таблице «Материалы (выход)» (`inputName=output_materials`) заполнять поле `input[name="name"]` названием выбранного материала (перезапись при каждом выборе; только создание, не редактирование).

## 5. Разрешённые операции по каталогам (новый функционал)

Семантика: «разрешить операцию (шаблон) для каталога» = выдать/отозвать разрешение этой операции **всем материалам поддерева** каталога (сам каталог + все вложенные). Состояние чекбокса каталога вычисляется по материалам поддерева: все разрешены → checked, часть → indeterminate, ни один → unchecked. Отдельной каталог-таблицы не заводим — пишем в существующий pivot `material_production_operation`.

### Роуты (`routes/web.php`, рядом с группой operations)
- `GET /production/operations/{productionOperation}/allowed-catalogs` → `allowedCatalogs`, имя `production.operations.allowed-catalogs`, `can.do:operations` — контент модалки.
- `POST /production/operations/{productionOperation}/allowed-catalogs` → `updateAllowedCatalogs`, `can.do:operations,edit`.

### Контроллер `ProductionOperationController`
- `allowedCatalogs(Operation $operation)`: каталоги (sort_order+name); счётчики одним-двумя groupBy-запросами: количество материалов по catalog_id и количество из них с разрешённой операцией (join pivot); рекурсивно агрегировать по поддеревьям; partial `production.operations._allowed-catalogs` (модалка).
- `updateAllowedCatalogs(Request $request, Operation $operation)`: validate `catalog_id` (exists), `allowed` (boolean); ids = [catalog_id + `Catalog::descendantIds()`]; материалы `whereIn('catalog_id', ids)`:
  - `allowed=true` → `allowedOperations()->syncWithoutDetaching([$operation->id])` (уникальный индекс уже есть);
  - `allowed=false` → `allowedOperations()->detach([$operation->id])`.
  - JSON `{success, message: «Разрешение выдано/отозвано для N материалов»}`.

### View `resources/views/production/operations/_allowed-catalogs.blade.php`
- Дерево каталогов (рекурсивный обход как в catalogs/index, с учётом `Catalog::hierarchyEnabled()`): только название + чекбокс (`data-allowed-catalog`, `data-catalog-id`, `data-materials-count`), indeterminate для частичных состояний.
- Скрытый блок подтверждения: текст-предупреждение «Изменение будет применено ко всем вложенным подкаталогам и материалам (N шт.). Продолжить?» + кнопки «Применить» / «Отмена».

### Кнопка на странице /production/lines/{id}
- `resources/views/production/operations/lines.blade.php`: рядом с «Добавить» кнопка «Разрешённые операции» с `data-allowed-catalogs-open="{{ route(...) }}"`.

### JS (`resources/js/modules/pages/production-lines.js`, делегированные обработчики)
- Клик `data-allowed-catalogs-open` → `operationModal.load(url)`.
- `change` чекбокса → показать блок-предупреждение; «Отмена» возвращает прежнее состояние чекбокса; «Применить» → `fetch POST` JSON `{catalog_id, allowed}` (CSRF из meta, как в catalogs.js) → при успехе заново `operationModal.load(...)` для обновления состояний + сообщение об успехе.

## Проверка
- `php artisan optimize:clear`, пересборка ассетов `npm run production` (или dev-сервер), ручная проверка: сайдбар, /catalogs, seeder (`php artisan db:seed` на dev-данных), создание линии, модалка разрешённых операций с каскадом и подтверждением.