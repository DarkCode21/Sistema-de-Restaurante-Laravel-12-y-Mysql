<?php

use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/printer/{id}', [OrderController::class, 'print'])
    ->name('orders.kitchen-print')
    ->middleware('signed');
Route::get('/printer-local/{id}', [SaleController::class, 'receipt'])
    ->name('sales.print-local')
    ->middleware('signed');

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('can:empresa.tablero');

    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware('can:usuarios.ver');
    Route::get('/roles', [UserController::class, 'role'])->name('users.role')->middleware('can:roles.ver');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index')->middleware('can:empresa.editar');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update')->middleware('can:empresa.editar');

    Route::get('/payment-methods', [CrudController::class, 'paymentMethod'])->name('payment-methods.index')->middleware('can:payment_methods.ver');
    Route::get('/categories', [CrudController::class, 'category'])->name('categories.index')->middleware('can:categorias.ver');
    Route::get('/products', [CrudController::class, 'product'])->name('products.index')->middleware('can:productos.ver');
    Route::get('/preparation-stations', [CrudController::class, 'preparationStation'])->name('preparation-stations.index')->middleware('can:empresa.editar');

    Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index')->middleware('can:gastos.ver');
    Route::get('/expenses/export/pdf', [ExpenseController::class, 'pdf'])->name('expenses.export.pdf')->middleware('can:gastos.reportes');
    Route::get('/expenses/export/excel', [ExpenseController::class, 'excel'])->name('expenses.export.excel')->middleware('can:gastos.reportes');

    Route::get('/boxes', [CashRegisterController::class, 'index'])->name('boxes.index')->middleware('can:cajas.ver');
    Route::get('/boxes/{id}', [CashRegisterController::class, 'movements'])->name('boxes.movements')->middleware('can:cajas.movimientos');
    Route::post('/boxes/{id}/close', [CashRegisterController::class, 'close'])->name('boxes.close')->middleware('can:cajas.cerrar');

    Route::get('/tables', [CrudController::class, 'table'])->name('tables.index')->middleware('can:mesas.ver');
    Route::get('/ingredients', [CrudController::class, 'ingredient'])->name('ingredients.index')->middleware('can:productos.ver');
    Route::get('/promotions', [CrudController::class, 'promotion'])->name('promotions.index')->middleware('can:productos.ver');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index')->middleware('can:ordenes.ver');

    Route::get('/orders/chef', [OrderController::class, 'chef'])->name('orders.chef');
    Route::get('/orders/cashier', [OrderController::class, 'cashier'])->name('orders.cashier')->middleware('can:ordenes.cobrar');

    Route::get('/orders/new/{type}', [OrderController::class, 'createDirect'])->name('orders.new')->middleware('can:ordenes.crear');
    Route::get('/orders/manage/{order}', [OrderController::class, 'manage'])->name('orders.manage')->middleware('can:ordenes.crear');
    Route::get('/orders/create/{id}', [OrderController::class, 'create'])->name('orders.create')->middleware('can:ordenes.crear');
    Route::get('/orders/ticket/{id}', [OrderController::class, 'ticket'])->name('orders.ticket')->middleware('can:ordenes.ver');

    Route::get('/sales', [SaleController::class, 'index'])->name('sales.index')->middleware('can:ventas.ver');
    Route::get('/sales/{id}/receipt', [SaleController::class, 'receipt'])->name('sales.receipt')->middleware('can:ventas.ver');

    Route::get('/reports/sales/pdf', [SaleController::class, 'salesPdf'])->name('sales.report.pdf')->middleware('can:ventas.reportes');
    Route::get('/reports/sales/excel', [SaleController::class, 'salesExcel'])->name('sales.report.excel')->middleware('can:ventas.reportes');

    Route::get('/reports/promotions', [ReportController::class, 'promotions'])->name('reports.promotions')->middleware('can:ventas.reportes');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
