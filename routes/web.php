<?php

use App\Http\Controllers\ComplianceFilesController;
use App\Http\Controllers\ReportsExportController;
use App\Livewire\Audit\AuditLogList;
use App\Livewire\Categories\CategoryList;
use App\Livewire\Dashboard\DashboardOverview;
use App\Livewire\Inventory\InventoryList;
use App\Livewire\Platform\AccountApprovals;
use App\Livewire\Pos\SaleTerminal;
use App\Livewire\Products\ProductList;
use App\Livewire\Reports\DailyReadings;
use App\Livewire\Reports\ReportsDashboard;
use App\Livewire\Sales\SalesHistory;
use App\Livewire\Settings\BirSettings;
use App\Livewire\Settings\PrinterSettings;
use App\Livewire\Settings\SystemReadiness;
use App\Livewire\Users\UserManagement;
use App\Models\DailyClosing;
use App\Models\PrinterSetting;
use App\Models\Sale;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', DashboardOverview::class)
    ->middleware(['auth', 'active', 'account.active', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'active', 'account.active'])
    ->name('profile');

Route::middleware(['auth', 'active', 'account.active', 'role:admin,cashier'])->group(function () {
    Route::get('pos', SaleTerminal::class)->name('pos');

    Route::get('sales/{sale}/invoice', function (Sale $sale) {
        $sale->load(['items', 'user', 'payment', 'adjustment.authorizedBy', 'refunds.items']);

        $printerSetting = PrinterSetting::current();

        return view('sales.receipt', compact('sale', 'printerSetting'));
    })->name('sales.invoice');

    Route::get('sales/{sale}/receipt', fn (Sale $sale) => redirect()->route('sales.invoice', $sale));
});

Route::middleware(['auth', 'active', 'account.active', 'role:admin'])->group(function () {
    Route::get('categories', CategoryList::class)->name('categories');
    Route::get('products', ProductList::class)->name('products');
    Route::get('inventory', InventoryList::class)->name('inventory');
    Route::get('sales', SalesHistory::class)->name('sales');

    Route::get('sales/{sale}', function (Sale $sale) {
        $sale->load(['items', 'user', 'payment', 'adjustment.authorizedBy']);

        return view('sales.show', compact('sale'));
    })->name('sales.show');

    Route::get('reports', ReportsDashboard::class)->name('reports');
    Route::get('daily-readings', DailyReadings::class)->name('daily-readings');
    Route::get('daily-readings/{dailyClosing}/print', function (DailyClosing $dailyClosing) {
        $dailyClosing->load('closedBy');

        $printerSetting = PrinterSetting::current();

        return view('reports.daily-closing', compact('dailyClosing', 'printerSetting'));
    })->name('daily-readings.print');
    Route::get('reports/export/bir-sales', [ReportsExportController::class, 'sales'])->name('reports.export.sales');
    Route::get('reports/export/reversals', [ReportsExportController::class, 'reversals'])->name('reports.export.reversals');
    Route::get('users', UserManagement::class)->name('users');
    Route::get('audit-logs', AuditLogList::class)->name('audit-logs');
    Route::get('settings/bir', BirSettings::class)->name('settings.bir');
    Route::get('settings/printer', PrinterSettings::class)->name('settings.printer');
    Route::get('settings/readiness', SystemReadiness::class)->name('settings.readiness');
    Route::get('compliance/audit-export', [ComplianceFilesController::class, 'audit'])->name('compliance.audit');
});

Route::middleware(['auth', 'active', 'platform_owner'])->group(function () {
    Route::get('platform/accounts', AccountApprovals::class)->name('platform.accounts');
    Route::get('compliance/backups/{filename}', [ComplianceFilesController::class, 'backup'])->name('compliance.backup');
});

require __DIR__.'/auth.php';
