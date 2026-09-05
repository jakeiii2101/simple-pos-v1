<?php

use App\Livewire\Categories\CategoryList;
use App\Livewire\Inventory\InventoryList;
use App\Livewire\Pos\SaleTerminal;
use App\Livewire\Products\ProductList;
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
        $sale->load(['items', 'user']);

        return view('sales.receipt', compact('sale'));
    })->name('sales.receipt');
});

Route::middleware(['auth', 'active', 'role:admin'])->group(function () {
    Route::get('categories', CategoryList::class)->name('categories');
    Route::get('products', ProductList::class)->name('products');
    Route::get('inventory', InventoryList::class)->name('inventory');
});

require __DIR__.'/auth.php';
