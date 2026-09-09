<?php

	use App\Http\Controllers\DashboardController;
	use App\Http\Controllers\MaterialController;
	use App\Http\Controllers\MaterialIssueController;
	use App\Http\Controllers\MaterialMovementController;
	use App\Http\Controllers\MaterialReceiptController;
	use App\Http\Controllers\MaterialRollController;
	use App\Http\Controllers\ProductionOperationController;
	use App\Http\Controllers\WarehouseController;
	use App\Http\Controllers\LaminationController;
	use Illuminate\Support\Facades\Route;

	Route::get('/', [DashboardController::class, 'index'])
			->name('dashboard');

	/*
	|--------------------------------------------------------------------------
	| Ламинация
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/lamination',
			[LaminationController::class, 'index']
	)->name('lamination.index');

	Route::get(
			'/lamination/create',
			[LaminationController::class, 'create']
	)->name('lamination.create');

	Route::post(
			'/lamination',
			[LaminationController::class, 'store']
	)->name('lamination.store');

	/*
	|--------------------------------------------------------------------------
	| Производственные операции
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/production/operations',
			[ProductionOperationController::class, 'index']
	)->name('production.operations.index');

	Route::get(
			'/production/operations/create',
			[ProductionOperationController::class, 'create']
	)->name('production.operations.create');

	Route::post(
			'/production/operations',
			[ProductionOperationController::class, 'store']
	)->name('production.operations.store');

	Route::get(
			'/production/operations/{productionOperation}',
			[ProductionOperationController::class, 'show']
	)->name('production.operations.show');

	Route::get(
			'/production/operations/{productionOperation}/edit',
			[ProductionOperationController::class, 'edit']
	)->name('production.operations.edit');

	Route::put(
			'/production/operations/{productionOperation}',
			[ProductionOperationController::class, 'update']
	)->name('production.operations.update');

	Route::get(
			'/production/operations/{productionOperation}/delete',
			[ProductionOperationController::class, 'delete']
	)->name('production.operations.delete');

	Route::delete(
			'/production/operations/{productionOperation}',
			[ProductionOperationController::class, 'destroy']
	)->name('production.operations.destroy');

	/*
	|--------------------------------------------------------------------------
	| Движение материалов
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/material-movements',
			[MaterialMovementController::class, 'index']
	)->name('material-movements.index');

	/*
	|--------------------------------------------------------------------------
	| Склад
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/warehouse',
			[WarehouseController::class, 'index']
	)->name('warehouse.index');

	Route::get(
			'/warehouse/materials/{material}',
			[WarehouseController::class, 'material']
	)->name('warehouse.material');

	/*
	|--------------------------------------------------------------------------
	| Приходные ордера
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/receipts',
			[MaterialReceiptController::class, 'index']
	)->name('material-receipts.index');

	Route::get(
			'/receipts/create',
			[MaterialReceiptController::class, 'create']
	)->name('material-receipts.create');

	Route::post(
			'/receipts',
			[MaterialReceiptController::class, 'store']
	)->name('material-receipts.store');

	Route::get(
			'/receipts/{receipt}',
			[MaterialReceiptController::class, 'show']
	)->name('material-receipts.show');

	/*
	|--------------------------------------------------------------------------
	| Расходные ордера
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/issues',
			[MaterialIssueController::class, 'index']
	)->name('material-issues.index');

	Route::get(
			'/issues/create',
			[MaterialIssueController::class, 'create']
	)->name('material-issues.create');

	Route::post(
			'/issues',
			[MaterialIssueController::class, 'store']
	)->name('material-issues.store');

	Route::get(
			'/issues/{issue}',
			[MaterialIssueController::class, 'show']
	)->name('material-issues.show');

	/*
	|--------------------------------------------------------------------------
	| Физические рулоны
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/rolls',
			[MaterialRollController::class, 'index']
	)->name('material-rolls.index');

	Route::get(
			'/rolls/{roll}',
			[MaterialRollController::class, 'show']
	)->name('material-rolls.show');

	/*
	|--------------------------------------------------------------------------
	| Справочники
	|--------------------------------------------------------------------------
	*/

	/*
	|--------------------------------------------------------------------------
	| Справочник материалов
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/materials',
			[MaterialController::class, 'index']
	)->name('materials.index');

	Route::get(
			'/materials/create',
			[MaterialController::class, 'create']
	)->name('materials.create');

	Route::post(
			'/materials',
			[MaterialController::class, 'store']
	)->name('materials.store');

	Route::get(
			'/materials/{material}/edit',
			[MaterialController::class, 'edit']
	)->name('materials.edit');

	Route::get(
			'/materials/{material}/delete',
			[MaterialController::class, 'delete']
	)->name('materials.delete');

	Route::get(
			'/materials/{material}',
			[MaterialController::class, 'show']
	)->name('materials.show');

	Route::put(
			'/materials/{material}',
			[MaterialController::class, 'update']
	)->name('materials.update');

	Route::delete(
			'/materials/{material}',
			[MaterialController::class, 'destroy']
	)->name('materials.destroy');

	/*
	|--------------------------------------------------------------------------
	| API для получения рулонов по материалу
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/api/rolls',
			[MaterialRollController::class, 'getRollsByMaterial']
	)->name('api.rolls.by-material');