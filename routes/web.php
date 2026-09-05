<?php

use App\Livewire\Categories\CategoryList;
use App\Livewire\Inventory\InventoryList;
use App\Livewire\Products\ProductList;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'active', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active'])
    ->name('profile');

Route::middleware(['auth', 'active', 'role:admin'])->group(function () {
    Route::get('categories', CategoryList::class)->name('categories');
    Route::get('products', ProductList::class)->name('products');
    Route::get('inventory', InventoryList::class)->name('inventory');
});

require __DIR__.'/auth.php';
