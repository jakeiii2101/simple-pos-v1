<?php

use App\Livewire\Categories\CategoryList;
use App\Livewire\Inventory\InventoryList;
use App\Livewire\Pos\SaleTerminal;
use App\Livewire\Products\ProductList;
use App\Livewire\Reports\ReportsDashboard;
use App\Livewire\Sales\SalesHistory;
use App\Livewire\Users\UserManagement;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'active', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active'])
    ->name('profile');

Route::middleware(['auth', 'active', 'role:admin,cashier'])->group(function () {
    Route::get('pos', SaleTerminal::class)->name('pos');

    Route::get('sales/{sale}/receipt', function (Sale $sale) {
        $sale->load(['items', 'user', 'payment']);

        return view('sales.receipt', compact('sale'));
    })->name('sales.receipt');
});

Route::middleware(['auth', 'active', 'role:admin'])->group(function () {
    Route::get('categories', CategoryList::class)->name('categories');
    Route::get('products', ProductList::class)->name('products');
    Route::get('inventory', InventoryList::class)->name('inventory');
    Route::get('sales', SalesHistory::class)->name('sales');

    Route::get('sales/{sale}', function (Sale $sale) {
        $sale->load(['items', 'user', 'payment']);

        return view('sales.show', compact('sale'));
    })->name('sales.show');

    Route::get('reports', ReportsDashboard::class)->name('reports');
    Route::get('users', UserManagement::class)->name('users');
});

require __DIR__.'/auth.php';
