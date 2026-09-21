<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'attempt'])->name('login.attempt');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/pos', [\App\Http\Controllers\PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/products', [\App\Http\Controllers\PosController::class, 'products'])->name('pos.products');
    Route::post('/pos/checkout', [\App\Http\Controllers\PosController::class, 'checkout'])->name('pos.checkout');
    Route::get('/pos/drafts', [\App\Http\Controllers\DraftController::class, 'index'])->name('pos.drafts.index');
    Route::post('/pos/drafts', [\App\Http\Controllers\DraftController::class, 'store'])->name('pos.drafts.store');
    Route::put('/pos/drafts/{sale}', [\App\Http\Controllers\DraftController::class, 'update'])->name('pos.drafts.update');
    Route::post('/pos/drafts/{sale}/resume', [\App\Http\Controllers\DraftController::class, 'resume'])->name('pos.drafts.resume');
    Route::post('/pos/drafts/{sale}/release', [\App\Http\Controllers\DraftController::class, 'release'])->name('pos.drafts.release');
    Route::delete('/pos/drafts/{sale}', [\App\Http\Controllers\DraftController::class, 'destroy'])->name('pos.drafts.destroy');
    Route::get('/transactions/{sale}/receipt', [\App\Http\Controllers\TransactionController::class, 'receipt'])->name('transactions.receipt')->can('view', 'sale');
    Route::get('/transactions', [\App\Http\Controllers\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{sale}', [\App\Http\Controllers\TransactionController::class, 'show'])->name('transactions.show')->can('view', 'sale');
});

Route::middleware(['auth', 'role:admin'])->group(function (): void {
    Route::get('/stock', [\App\Http\Controllers\StockController::class, 'index'])->name('stock.index');
    Route::post('/stock/receive', [\App\Http\Controllers\StockController::class, 'receive'])->name('stock.receive');
    Route::post('/stock/adjust', [\App\Http\Controllers\StockController::class, 'adjust'])->name('stock.adjust');
    Route::get('/stock/movements', [\App\Http\Controllers\StockController::class, 'movements'])->name('stock.movements');
    Route::resource('users', \App\Http\Controllers\UserController::class)->except(['show', 'create', 'edit']);
    Route::resource('categories', \App\Http\Controllers\CategoryController::class)->except(['show', 'create', 'edit']);
    Route::resource('units', \App\Http\Controllers\UnitController::class)->except(['show', 'create', 'edit']);
    Route::resource('products', \App\Http\Controllers\ProductController::class)->except(['show', 'create', 'edit']);
    Route::get('/settings', [\App\Http\Controllers\StoreSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [\App\Http\Controllers\StoreSettingController::class, 'update'])->name('settings.update');
    Route::post('/transactions/{sale}/void', [\App\Http\Controllers\TransactionController::class, 'void'])->name('transactions.void');
    Route::delete('/transactions/{sale}/discard', [\App\Http\Controllers\TransactionController::class, 'discard'])->name('transactions.discard');
    Route::get('/reports/sales', [\App\Http\Controllers\ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/products', [\App\Http\Controllers\ReportController::class, 'products'])->name('reports.products');
    Route::get('/reports/stock', [\App\Http\Controllers\ReportController::class, 'stock'])->name('reports.stock');
    Route::get('/reports/drafts', [\App\Http\Controllers\ReportController::class, 'drafts'])->name('reports.drafts');
    Route::get('/system-logs', [\App\Http\Controllers\SystemLogController::class, 'index'])->name('system-logs.index');
});
