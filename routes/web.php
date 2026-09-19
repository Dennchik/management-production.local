<?php

	use App\Http\Controllers\AuthController;
	use App\Http\Controllers\CatalogController;
	use App\Http\Controllers\DashboardController;
	use App\Http\Controllers\MaterialController;
	use App\Http\Controllers\MaterialIssueController;
	use App\Http\Controllers\MaterialMovementController;
	use App\Http\Controllers\MaterialReceiptController;
	use App\Http\Controllers\MaterialRollController;
	use App\Http\Controllers\ProductionOperationController;
	use App\Http\Controllers\ProductionTaskController;
	use App\Http\Controllers\WarehouseController;
	use App\Http\Controllers\RoleController;
	use App\Http\Controllers\UserController;
	use Illuminate\Support\Facades\Route;

	/*
	|--------------------------------------------------------------------------
	| Вход и выход
	|--------------------------------------------------------------------------
	*/

	Route::middleware('guest')->group(function () {
		Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
		Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
	});

	Route::post('/logout', [AuthController::class, 'logout'])
			->middleware('auth.session')
			->name('logout');

	/*
	|--------------------------------------------------------------------------
	| Закрытая часть системы
	|--------------------------------------------------------------------------
	*/

	Route::middleware('auth.session')->group(function () {

		Route::get('/', [DashboardController::class, 'index'])
				->name('dashboard');

		/*
		|--------------------------------------------------------------------------
		| Производственные задачи
		|--------------------------------------------------------------------------
		*/

		Route::get('/tasks', [ProductionTaskController::class, 'index'])
				->middleware('can.do:tasks')
				->name('tasks.index');

		Route::get('/tasks/create', [ProductionTaskController::class, 'create'])
				->middleware('can.do:tasks,create')
				->name('tasks.create');

		Route::post('/tasks', [ProductionTaskController::class, 'store'])
				->middleware('can.do:tasks,create')
				->name('tasks.store');

		Route::get('/tasks/{task}/edit', [ProductionTaskController::class, 'edit'])
				->middleware('can.do:tasks,edit')
				->name('tasks.edit');

		Route::put('/tasks/{task}', [ProductionTaskController::class, 'update'])
				->middleware('can.do:tasks,edit')
				->name('tasks.update');

		Route::get('/tasks/{task}/start',[ProductionTaskController::class, 'startForm'])
				->middleware('can.do:tasks,edit')
				->name('tasks.start-form');

		Route::post('/tasks/{task}/start', [ProductionTaskController::class, 'start'])
				->middleware('can.do:tasks,edit')
				->name('tasks.start');

		Route::get('/tasks/{task}/complete', [ProductionTaskController::class, 'completeForm'])
				->middleware('can.do:tasks,edit')
				->name('tasks.complete-form');

		Route::post('/tasks/{task}/complete', [ProductionTaskController::class, 'complete'])
				->middleware('can.do:tasks,edit')
				->name('tasks.complete');

		Route::get('/tasks/{task}', [ProductionTaskController::class, 'show'])
				->middleware('can.do:tasks')
				->name('tasks.show');

		/*
		|--------------------------------------------------------------------------
		| Производственные операции
		|--------------------------------------------------------------------------
		*/

		Route::get('/production/operations', [ProductionOperationController::class, 'index'])
				->middleware('can.do:operations')
				->name('production.operations.index');

		Route::get('/production/operations/create', [ProductionOperationController::class, 'create'])
				->middleware('can.do:operations,create')
				->name('production.operations.create');

		Route::post('/production/operations', [ProductionOperationController::class, 'store'])
				->middleware('can.do:operations,create')
				->name('production.operations.store');

		Route::get('/production/operations/{productionOperation}', [ProductionOperationController::class, 'show'])
				->middleware('can.do:operations')
				->name('production.operations.show');

		Route::get('/production/operations/{productionOperation}/edit', [ProductionOperationController::class, 'edit'])
				->middleware('can.do:operations,edit')
				->name('production.operations.edit');

		Route::put('/production/operations/{productionOperation}', [ProductionOperationController::class, 'update'])
				->middleware('can.do:operations,edit')
				->name('production.operations.update');

		Route::get('/production/operations/{productionOperation}/delete', [ProductionOperationController::class, 'delete'])
				->middleware('can.do:operations,delete')
				->name('production.operations.delete');

		Route::delete('/production/operations/{productionOperation}', [ProductionOperationController::class, 'destroy'])
				->middleware('can.do:operations,delete')
				->name('production.operations.destroy');

		/*
		|--------------------------------------------------------------------------
		| Производственные линии
		|--------------------------------------------------------------------------
		*/

		Route::get('/production/lines/{productionOperation}', [ProductionOperationController::class, 'lines'])
				->middleware('can.do:operations')
				->name('production.lines.show');

		Route::get('/production/lines/{productionOperation}/create', [ProductionOperationController::class, 'createLine'])
				->middleware('can.do:operations,create')
				->name('production.lines.create');


		Route::get('/production/lines/{productionOperation}/{productionLine}', [ProductionOperationController::class, 'showLine'])
				->middleware('can.do:operations')
				->whereNumber('productionLine')
				->name('production.lines.line.show');

		Route::get('/production/lines/{productionOperation}/{productionLine}/edit', [ProductionOperationController::class, 'editLine'])
				->middleware('can.do:operations,edit')
				->whereNumber('productionLine')
				->name('production.lines.line.edit');

		Route::put('/production/lines/{productionOperation}/{productionLine}', [ProductionOperationController::class, 'updateLine'])
				->middleware('can.do:operations,edit')
				->whereNumber('productionLine')
				->name('production.lines.line.update');
		Route::post('/production/lines/{productionOperation}', [ProductionOperationController::class, 'storeLine'])
				->middleware('can.do:operations,create')
				->name('production.lines.store');

		Route::get('/production/lines/{productionLine}/delete', [ProductionOperationController::class, 'deleteLine'])
				->middleware('can.do:operations,delete')
				->name('production.lines.delete');

		Route::delete('/production/lines/{productionLine}', [ProductionOperationController::class, 'destroyLine'])
				->middleware('can.do:operations,delete')
				->name('production.lines.destroy');

		/*
		|--------------------------------------------------------------------------
		| Движение материалов
		|--------------------------------------------------------------------------
		*/

		Route::get('/material-movements', [MaterialMovementController::class, 'index'])
				->middleware('can.do:warehouse')
				->name('material-movements.index');

		/*
		|--------------------------------------------------------------------------
		| Склад
		|--------------------------------------------------------------------------
		*/

		Route::get('/warehouse', [WarehouseController::class, 'index'])
				->middleware('can.do:warehouse')
				->name('warehouse.index');

		Route::get('/warehouse/materials/{material}', [WarehouseController::class, 'material'])
				->middleware('can.do:warehouse')
				->name('warehouse.material');

		/*
		|--------------------------------------------------------------------------
		| Приходные ордера
		|--------------------------------------------------------------------------
		*/

		Route::get('/receipts', [MaterialReceiptController::class, 'index'])
				->middleware('can.do:warehouse')
				->name('material-receipts.index');

		Route::get('/receipts/create', [MaterialReceiptController::class, 'create'])
				->middleware('can.do:warehouse,create')
				->name('material-receipts.create');

		Route::post('/receipts', [MaterialReceiptController::class, 'store'])
				->middleware('can.do:warehouse,create')
				->name('material-receipts.store');

		Route::get('/receipts/{receipt}', [MaterialReceiptController::class, 'show'])
				->middleware('can.do:warehouse')
				->name('material-receipts.show');

		/*
		|--------------------------------------------------------------------------
		| Расходные ордера
		|--------------------------------------------------------------------------
		*/

		Route::get('/issues', [MaterialIssueController::class, 'index'])
				->middleware('can.do:warehouse')
				->name('material-issues.index');

		Route::get('/issues/create', [MaterialIssueController::class, 'create'])
				->middleware('can.do:warehouse,create')
				->name('material-issues.create');

		Route::post('/issues', [MaterialIssueController::class, 'store'])
				->middleware('can.do:warehouse,create')
				->name('material-issues.store');

		Route::get('/issues/{issue}', [MaterialIssueController::class, 'show'])
				->middleware('can.do:warehouse')
				->name('material-issues.show');

		/*
		|--------------------------------------------------------------------------
		| Физические рулоны
		|--------------------------------------------------------------------------
		*/

		Route::get('/rolls', [MaterialRollController::class, 'index'])
				->middleware('can.do:rolls')
				->name('material-rolls.index');

		Route::get('/rolls/{roll}', [MaterialRollController::class, 'show'])
				->middleware('can.do:rolls')
				->name('material-rolls.show');

		/*
		|--------------------------------------------------------------------------
		| Справочник материалов и каталоги
		|--------------------------------------------------------------------------
		*/

		Route::get('/materials', [MaterialController::class, 'index'])
				->middleware('can.do:materials')
				->name('materials.index');

		Route::get('/materials/create', [MaterialController::class, 'create'])
				->middleware('can.do:materials,create')
				->name('materials.create');

		Route::get('/materials/create-choice', [MaterialController::class, 'createChoice'])
				->middleware('can.do:materials,create')
				->name('materials.create-choice');

		Route::post('/materials', [MaterialController::class, 'store'])
				->middleware('can.do:materials,create')
				->name('materials.store');

		Route::get('/materials/{material}/edit', [MaterialController::class, 'edit'])
				->middleware('can.do:materials,edit')
				->name('materials.edit');

		Route::get('/materials/{material}/delete', [MaterialController::class, 'delete'])
				->middleware('can.do:materials,delete')
				->name('materials.delete');

		Route::get('/materials/{material}', [MaterialController::class, 'show'])
				->middleware('can.do:materials')
				->name('materials.show');

		Route::put('/materials/{material}', [MaterialController::class, 'update'])
				->middleware('can.do:materials,edit')
				->name('materials.update');

		Route::delete('/materials/{material}', [MaterialController::class, 'destroy'])
				->middleware('can.do:materials,delete')
				->name('materials.destroy');

		Route::get('/catalogs', [CatalogController::class, 'index'])
				->middleware('can.do:materials')
				->name('catalogs.index');

		Route::get('/catalogs/create', [CatalogController::class, 'create'])
				->middleware('can.do:materials,create')
				->name('catalogs.create');

		Route::post('/catalogs', [CatalogController::class, 'store'])
				->middleware('can.do:materials,create')
				->name('catalogs.store');

		Route::post('/catalogs/hierarchy-toggle', [CatalogController::class, 'toggleHierarchy'])
				->middleware('can.do:materials,edit')
				->name('catalogs.hierarchy-toggle');

		Route::get('/catalogs/{catalog}/edit', [CatalogController::class, 'edit'])
				->middleware('can.do:materials,edit')
				->name('catalogs.edit');

		Route::get('/catalogs/{catalog}/delete', [CatalogController::class, 'delete'])
				->middleware('can.do:materials,delete')
				->name('catalogs.delete');

		Route::get('/catalogs/{catalog}', [CatalogController::class, 'show'])
				->middleware('can.do:materials')
				->name('catalogs.show');

		Route::put('/catalogs/{catalog}', [CatalogController::class, 'update'])
				->middleware('can.do:materials,edit')
				->name('catalogs.update');

		Route::delete('/catalogs/{catalog}', [CatalogController::class, 'destroy'])
				->middleware('can.do:materials,delete')
				->name('catalogs.destroy');

		/*
		|--------------------------------------------------------------------------
		| Пользователи и роли
		|--------------------------------------------------------------------------
		*/

		Route::get('/users', [UserController::class, 'index'])
				->middleware('can.do:users')
				->name('users.index');

		Route::get('/users/create', [UserController::class, 'create'])
				->middleware('can.do:users,create')
				->name('users.create');

		Route::post('/users', [UserController::class, 'store'])
				->middleware('can.do:users,create')
				->name('users.store');

		Route::get('/users/{user}/edit', [UserController::class, 'edit'])
				->middleware('can.do:users,edit')
				->name('users.edit');

		Route::post('/users/{user}', [UserController::class, 'update'])
				->middleware('can.do:users,edit')
				->name('users.update');

		Route::get('/roles', [RoleController::class, 'index'])
				->middleware('can.do:users')
				->name('roles.index');

		Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])
				->middleware('can.do:users,edit')
				->name('roles.edit');

		Route::put('/roles/{role}', [RoleController::class, 'update'])
				->middleware('can.do:users,edit')
				->name('roles.update');

		/*
		|--------------------------------------------------------------------------
		| API для получения рулонов по материалу
		|--------------------------------------------------------------------------
		*/

		Route::get('/api/rolls', [MaterialRollController::class, 'getRollsByMaterial'])
				->name('api.rolls.by-material');
	});
