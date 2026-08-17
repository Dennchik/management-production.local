
---

# 📁 Структура и архитектура Laravel-приложения

## 1. Каталог `app/`

Основной каталог с PHP-кодом приложения.

```
app/
├── Http/          ← Обработка HTTP-запросов
├── Models/        ← Модели данных (Eloquent)
└── Providers/     ← Сервис-провайдеры
```

---

### 1.1. `app/Http/`

Всё, что связано с обработкой входящих HTTP-запросов.

```
app/Http/
├── Controllers/   ← Логика обработки запросов
├── Middleware/    ← Посредники (фильтрация запросов)
└── Requests/      ← Валидация входящих данных
```

#### Controllers/
Контроллер принимает запрос и определяет, что делать дальше.

```
GET /about
    ↓
AboutController
    ↓
Данные
    ↓
Blade-шаблон
```

#### Middleware/
Посредники между запросом и приложением.

```
Браузер → Route → Middleware → Controller → Response
```

**Задачи:** авторизация, CSRF, проверка/изменение запроса.

#### Requests/
Классы валидации (Form Requests).

```php
name     → обязательно
email    → email
phone    → обязательно
```

---

### 1.2. `app/Models/`

Модели Eloquent — объекты, связанные с таблицами БД.

```php
User::all();          // Все пользователи
Order::find(10);      // Заказ по ID
```

---

### 1.3. `app/Providers/`

Сервис-провайдеры — регистрация сервисов, bindings, событий, настроек.

> ⚠️ Для frontend-задач сюда не лезем.

---

## 2. Полная цепочка запроса

```
Route
  ↓
Controller
  ↓
Model / Данные
  ↓
Blade-шаблон
```

---

## 3. Архитектура: Laravel + Vite

```
                    ПРОЕКТ
                       │
          ┌────────────┴────────────┐
          │                         │
       Laravel                    Vite
          │                         │
      routes/web.php          vite.config.js
          │                         │
          ↓                         │
   app.blade.php                    │
          │                         │
          │       @vite(...)        │
          └──────────────┬──────────┘
                         ↓
                  resources/
                  ├── js/
                  └── scss/
                         │
                         ↓
                       Vite
                         │
              ┌──────────┴──────────┐
              ↓                     ↓
         development             production
              │                     │
           HMR :5173          public/build/
                                    │
                                    ├── assets/
                                    └── manifest.json
```

---

### 3.1. Компоненты

| Компонент | Назначение |
|-----------|------------|
| **Laravel** | Бэкенд, маршруты, Blade-шаблоны |
| **Vite** | Сборщик frontend-ресурсов (JS, SCSS) |
| **routes/web.php** | Определение маршрутов |
| **app.blade.php** | Главный шаблон с `@vite(...)` |
| **resources/js/** | Исходники JavaScript |
| **resources/scss/** | Исходники стилей |
| **HMR :5173** | Режим разработки с горячей перезагрузкой |
| **public/build/** | Собранные production-файлы |
| **manifest.json** | Манифест для версионирования ассетов |

---

### 3.2. Режимы Vite

| Режим | Описание |
|-------|----------|
| **Development** | Dev-сервер с HMR (порт 5173) |
| **Production** | Сборка в `public/build/` |

---

*Актуально для Laravel 10+ / 11+ с Vite*