<?php

	use App\Http\Controllers\DashboardController;
	use App\Http\Controllers\MaterialIssueController;
	use App\Http\Controllers\MaterialMovementController;
	use App\Http\Controllers\MaterialReceiptController;
	use App\Http\Controllers\MaterialRollController;
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
	| API для получения рулонов по материалу
	|--------------------------------------------------------------------------
	*/

	Route::get(
			'/api/rolls',
			[MaterialRollController::class, 'getRollsByMaterial']
	)->name('api.rolls.by-material');