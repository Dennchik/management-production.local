1. Существующие складские сущности
   Оставляем:
   ```
	materials
	material_rolls
	material_receipts
	material_issues
	```
   materials — справочник материалов.
   material_rolls — физические рулоны с остатком веса.

2. Справочник технологических операций
   production_operations
   ```
	id
	name
	code
	description
	is_active
	created_at
	updated_at
	```
   Примеры:
   ```
	1 | Ламинация
	2 | Кэширование
	3 | Праймирование
	4 | Печать
	5 | Резка
	```

3. Технологические маршруты
   production_routes
   ```
	id
	name
	code
	description
	is_active
	created_at
	updated_at
	```
   Например:
   ```
	id: 1
	name: Производство ПФ №1
	```

4. Операции внутри маршрута
   production_route_steps

   Это очень важная таблица.
   ```
	id
	route_id
	operation_id
	step_number
	name
	description
	created_at
	updated_at
	```
   Связи:
   ```
	production_routes
			  │
			  │ 1:N
			  ▼
	production_route_steps
			  │
			  │ N:1
			  ▼
	production_operations
	```
   Например:
   ```
	Маршрут №1
	
	step 1 → Ламинация
	step 2 → Кэширование
	step 3 → Печать
	step 4 → Резка
	```
   Другой маршрут:
    ```
	step 1 → Ламинация
	step 2 → Печать
	step 3 → Резка
	```
5. Производственное задание
   production_jobs
   ```
	id
	job_number
	route_id
	status
	planned_weight
	comment
	user_id
	started_at
	completed_at
	created_at
	updated_at
	```
   Связь:
   ```
	production_routes
			  │
			  │ 1:N
			  ▼
	production_jobs
	```
   Например:
   ```
	Задание №000125
	Маршрут: Производство ПФ №1
	Статус: В работе
	```

6. Выполнение операций
   production_job_operations

   Это конкретное выполнение конкретного шага маршрута.
   ```
	id
	production_job_id
	route_step_id
	operation_id
	step_number
	status
	started_at
	completed_at
	comment
	created_at
	updated_at
	```

   Связь:
   ```
	production_jobs
			  │
			  │ 1:N
			  ▼
	production_job_operations
	```

   Например:
   ```
	Задание №125
	
	Операция №1 — Ламинация       ✓
	Операция №2 — Кэширование     ✓
	Операция №3 — Печать          → текущая
	Операция №4 — Резка           ○
	```

7. Входные материалы операции
   production_job_operation_inputs
   ```
	id
	job_operation_id
	material_id
	roll_id
	weight
	input_type
	is_waste
	comment
	created_at
	updated_at
	```

   Здесь фиксируем что реально пошло в операцию.

   Например:
   ```
	Ламинация
	
	Бумага ВП 60
	Рулон №101
	500 кг
	
	FPO
	Рулон №205
	200 кг
	```

   Или для кэширования:
   ```
	ПФ после ламинации
	Рулон №301
	480 кг
	
	FPO
	Рулон №205
	190 кг
	```

8. Выходные полуфабрикаты
   production_job_operation_outputs
   ```
	id
	job_operation_id
	material_id
	roll_id
	weight
	output_type
	comment
	created_at
	updated_at
	```
   Например:
   ```
	Ламинация
			  ↓
	ПФ
			  ↓
	Рулон №301
	480 кг
	```
   Этот рулон затем может стать входом следующей операции:
   ```
	Кэширование
			  ↑
	Рулон №301
	```

9. Общая схема
   ```
									 ┌─────────────────┐
									 │    materials    │
									 └────────┬────────┘
												 │
												 ▼
									 ┌─────────────────┐
									 │ material_rolls  │
									 └────────┬────────┘
												 │
						  ┌────────────────┴────────────────┐
						  │                                 │
						  ▼                                 ▼
		  operation_inputs                    operation_outputs
						  │                                 │
						  └──────────────┬──────────────────┘
											  │
											  ▼
						  production_job_operations
											  │
											  ▼
								  production_jobs
											  │
											  ▼
								 production_routes
											  │
											  ▼
							 production_route_steps
											  │
											  ▼
							production_operations
	```

	Но есть важное уточнение: material_rolls не должен быть просто справочной таблицей для производства. Это физический
	объект склада. Поэтому входной рулон может существовать до производства, а выходной рулон должен появляться в результате
	производства.
10. Полная структура БД на данный момент
	```
	СКЛАД
	
	materials
	material_rolls
	material_receipts
	material_issues
	
	
	СПРАВОЧНИКИ
	
	production_operations
	production_routes
	production_route_steps
	
	
	ПРОИЗВОДСТВО
	
	production_jobs
	production_job_operations
	production_job_operation_inputs
	production_job_operation_outputs
	```