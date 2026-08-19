<?php

	use App\Http\Controllers\DashboardController;
	use App\Http\Controllers\MaterialReceiptController;
	use App\Http\Controllers\WarehouseController;
	use Illuminate\Support\Facades\Route;

	Route::get(
		'/warehouse',
		[WarehouseController::class, 'index']
	)->name('warehouse.index');

	Route::get('/', [DashboardController::class, 'index'])
		->name('dashboard');

	Route::get(
		'/receipts/create',
		[MaterialReceiptController::class, 'create']
	)->name('material-receipts.create');

	Route::post(
		'/receipts',
		[MaterialReceiptController::class, 'store']
	)->name('material-receipts.store');