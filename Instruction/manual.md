# Этап 1. База данных

```
Справочник типов материалов
│
├── Migration
│   └── materials
│
├── Model
│   └── Material
│
├── Seeder
│   └── MaterialSeeder
│
└── DatabaseSeeder
└── MaterialSeeder
```

1. Создаём миграцию

   1.1 В корне Laravel-проекта
    * ```
        php artisan make:migration create_materials_table
       ``` 
    * ```
        database/migrations/
        └── xxxx_xx_xx_xxxxxx_create_materials_table.php
       ```
   1.2 Применяем:
    * ```
        php artisan migrate
        ```
      Laravel должен выполнить нашу миграцию и создать таблицу:
    * ```
       materials
     
       Вывод:
     
       INFO  Running migrations.

          2026_08_17_134319_create_materials_table ........ DONE
       ```
2. Создаём модель:
    * ```
       В терминале:
       php artisan make:model Material
     
       Должен появиться файл:
       app/Models/Material.php
       ```
3. Создаём Seeder для справочника материалов.
    * ```
         В терминале:
         php artisan make:seeder MaterialSeeder
         Должен появиться файл:
         database/seeders/MaterialSeeder.php
       ```

4. Запускаем заполнение справочника.
    * ```
       В терминале выполни:
       php artisan db:seed
    
       Ожидаемый результат:
       INFO Seeding database.
      ```

   4.1 Справочник материалов
    * ``` 
      Миграция materials       ✅
      Модель Material          ✅
      MaterialSeeder           ✅
      DatabaseSeeder           ✅
      43 типа материалов       ✅
       ```

   4.2 Проверка данных:
    * ```
      Терминал: (Вход в tinker)
      php artisan tinker
  
      Ожидаемый результат:
      Psy Shell v0.12.24 (PHP 8.4.24 — cli) by Justin Hileman
  
      В строке >:
      App\Models\Material::count();
  
      Ожидаемый результат:
      = 43andr@DESKTOP-2AA4ONV MINGW64 /c/OSPanel/domains/management-production.local (main)
      $ php artisan tinker
      ```

   4.3 Проверяем идентификаторы
    * ```
      Получаем список всех 43 материалов
      
      Выход из Tinker:
      exit
      ```

# Этап 2. Физические рулоны

### _Здесь уже начинаем строить складскую часть_

```
    materials
       │
       │ тип материала
       ▼
    material_rolls
       │
       ├── рулон №101 — 500 кг
       ├── рулон №102 — 480 кг
       └── рулон №103 — 520 кг
```

1. Создаём миграцию

   2.1. Создаём миграцию
    ```
     В терминале:
     php artisan make:migration create_material_rolls_table
     
     INFO  Migration [C:\OSPanel\domains\management-production.local\database\migrations\2026_08_17_161037_create_material_rolls_table.php] created successfully. 

    Результат:
       INFO  Migration [C:\OSPanel\domains\management-production.local\database\migrations\2026_08_17_161037_create_material_rolls_table.php] created successfully.  
    ```
   2.2 Содержимое файла
    ```
        <?php
        
        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;
        
        return new class extends Migration
        {
            /**
             * Создаёт таблицу физических рулонов.
             *
             * Каждый рулон относится к определённому типу материала
             * и хранит его текущий фактический остаток в килограммах.
             */
            public function up(): void
            {
                Schema::create('material_rolls', function (Blueprint $table) {
                    $table->id();

            // Ссылка на тип материала из справочника materials.
            $table->foreignId('material_id')
                ->constrained('materials')
                ->restrictOnDelete();

            // Номер конкретного физического рулона.
            $table->string('roll_number', 50);

            // Текущий фактический вес рулона в килограммах.
            $table->decimal('weight', 10, 3);

            $table->timestamps();

            // Один номер рулона может повторяться у разных материалов,
            // но не должен дублироваться внутри одного типа материала.
            $table->unique(['material_id', 'roll_number']);
        });
            }
        
            /**
             * Удаляет таблицу физических рулонов.
             */
            public function down(): void
            {
                Schema::dropIfExists('material_rolls');
            }
        };
    ```
   ```
       materials
       │
       │ id
       ▼
    material_rolls
    │
    ├── material_id → 1
    │   roll_number → 101
    │   weight      → 500.000
    │
    ├── material_id → 1
    │   roll_number → 102
    │   weight      → 480.000
    │
    └── material_id → 1
    roll_number → 103
    weight      → 520.000
   ```
   структура БД:
    ```
   users
    cache
    jobs
    materials
    │
    │ 1 : N
    ▼
    material_rolls
   ```
   2.2 Создаём Eloquent-модель:
    ```
   php artisan make:model MaterialRoll
   ```

   MaterialRoll.php:
   ```
   <?php

    namespace App\Models;
    
    use Illuminate\Database\Eloquent\Attributes\Fillable;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\BelongsTo;
    
    #[Fillable([
    'material_id',
    'roll_number',
    'weight',
    ])]
    class MaterialRoll extends Model
    {
    /**
    * Материал, к которому относится физический рулон.
      *
      * Каждый рулон принадлежит одному типу материала
      * из справочника materials.
      */
      public function material(): BelongsTo
      {
      return $this->belongsTo(Material::class);
      }
      }
   ```

   Material.php:
    ```
   <?php
    
    namespace App\Models;
    
    use Illuminate\Database\Eloquent\Attributes\Fillable;
    use Illuminate\Database\Eloquent\Model;
    use Illuminate\Database\Eloquent\Relations\HasMany;
    
    #[Fillable([
    'name',
    'code',
    'grammage',
    'thickness',
    'format',
    'identifier',
    ])]
    class Material extends Model
    {
    /**
    * Физические рулоны данного типа материала.
      *
      * Один тип материала может иметь любое количество
      * физических рулонов с разным текущим весом.
      */
      public function rolls(): HasMany
      {
      return $this->hasMany(MaterialRoll::class);
      }
      }
   ```
   2.4 — Проверка связи
    ```
   Запуск Tinker:
   php artisan tinker
   
    В Tinker выполни:
   $material = App\Models\Material::where('identifier', '1360820')->first();
   ```
   2.5 Теперь проверим обратную связь:
    ```
   $material->rolls;
   ```
   2.6 Создаём тестовый физический рулон
    ```
   $roll = App\Models\MaterialRoll::create([
    'material_id' => $material->id,
    'roll_number' => '101',
    'weight' => 500,
    ]);
    ```

# Этап 3. Оприходование сырья

3.1 Создаём миграцию:

```
php artisan make:migration create_material_receipts_table

database/migrations/
    ..._create_material_receipts_table.php
    
    php artisan migrate
    
       INFO  Running migrations.  

  2026_08_17_173248_create_material_receipts_table ......................... 459.74ms DONE
    
```

3.2 Создаем модель MaterialReceipt:

```
php artisan make:model MaterialReceipt

INFO  Model [C:\OSPanel\domains\management-production.local\app\Models\MaterialReceipt.ph
```

3.3 Теперь начинаем строить пользовательский сценарий:

```
Оприходование
      ↓
Новое оприходование
      ↓
Выбор материала
      ↓
Система показывает:
  • наименование
  • идентификатор
  • грамматуру / толщину
  • формат
      ↓
Номер рулона
      ↓
Вес
      ↓
Комментарий
      ↓
Провести
      ↓
Создаётся операция
      +
создаётся рулон
```

* переходим от структуры БД к Laravel-приложению:

```
Model
   ↓
Controller
   ↓
Route
   ↓
Blade
   ↓
Форма
```

3.4 Создаем контроллер оприходования:

```
app/Http/Controllers/MaterialReceiptController.php

   INFO  Controller [C:\OSPanel\domains\management-production.local\app\Http\
   Controllers\MaterialReceiptController.php] created successfully.  
   
   app/Http/Controllers/MaterialReceiptController.php
```

3.5 — создаём представление

Я предлагаю двигаться в таком порядке:

Приходный ордер — довести до полного рабочего состояния:
валидация;
сохранение материала и рулона;
сообщения об ошибках/успехе;
проверка old() после ошибки;
корректное обновление остатков.
Склад
список материалов;
количество рулонов;
общий остаток;
карточка материала;
просмотр конкретных рулонов.
Расход
выбор материала;
выбор конкретного рулона;
списание веса/рулона;
журнал операции.
Затем производственные операции:
Ламинация
Праймирование
Резка
Печать
Движение материалов
После этого:
Справочники
Отчёты
Пользователи и права
Журнал операций