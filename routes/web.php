<?php

use App\Livewire\Audit\AuditLogList;
use App\Livewire\Categories\CategoryList;
use App\Livewire\Dashboard\DashboardOverview;
use App\Livewire\Inventory\InventoryList;
use App\Livewire\Pos\SaleTerminal;
use App\Livewire\Products\ProductList;
use App\Livewire\Reports\ReportsDashboard;
use App\Livewire\Sales\SalesHistory;
use App\Livewire\Settings\BirSettings;
use App\Livewire\Users\UserManagement;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardOverview::class)
    ->middleware(['auth', 'active', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active'])
    ->name('profile');

Route::middleware(['auth', 'active', 'role:admin,cashier'])->group(function () {
    Route::get('pos', SaleTerminal::class)->name('pos');

    Route::get('sales/{sale}/invoice', function (Sale $sale) {
        $sale->load(['items', 'user', 'payment', 'adjustment.authorizedBy']);

        return view('sales.receipt', compact('sale'));
    })->name('sales.invoice');

    Route::get('sales/{sale}/receipt', fn (Sale $sale) => redirect()->route('sales.invoice', $sale));
});

Route::middleware(['auth', 'active', 'role:admin'])->group(function () {
    Route::get('categories', CategoryList::class)->name('categories');
    Route::get('products', ProductList::class)->name('products');
    Route::get('inventory', InventoryList::class)->name('inventory');
    Route::get('sales', SalesHistory::class)->name('sales');

    Route::get('sales/{sale}', function (Sale $sale) {
        $sale->load(['items', 'user', 'payment', 'adjustment.authorizedBy']);

        return view('sales.show', compact('sale'));
    })->name('sales.show');

    Route::get('reports', ReportsDashboard::class)->name('reports');
    Route::get('users', UserManagement::class)->name('users');
    Route::get('audit-logs', AuditLogList::class)->name('audit-logs');
    Route::get('settings/bir', BirSettings::class)->name('settings.bir');
});

require __DIR__.'/auth.php';
