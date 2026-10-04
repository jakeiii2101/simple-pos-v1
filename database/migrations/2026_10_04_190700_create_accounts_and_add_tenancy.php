<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $tenantTables = [
        'categories',
        'products',
        'sales',
        'sale_items',
        'stock_movements',
        'payments',
        'audit_logs',
        'bir_settings',
        'invoice_sequences',
        'sale_adjustments',
        'daily_closings',
        'sale_refunds',
        'sale_refund_items',
        'printer_settings',
    ];

    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 180)->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();
        });

        $legacyAccountId = DB::table('accounts')->insertGetId([
            'name' => 'SniperPOS Legacy Account',
            'slug' => 'sniperpos-legacy',
            'status' => 'active',
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('id')->constrained('accounts')->restrictOnDelete();
            $table->boolean('is_platform_owner')->default(false)->after('account_id')->index();
            $table->string('phone', 40)->nullable()->after('email');
        });

        DB::table('users')->update(['account_id' => $legacyAccountId]);

        $firstAdminId = DB::table('users')
            ->where('role', 'admin')
            ->orderBy('id')
            ->value('id');

        if ($firstAdminId !== null) {
            DB::table('accounts')->where('id', $legacyAccountId)->update([
                'owner_user_id' => $firstAdminId,
            ]);
        }

        // Platform-owner permission is intentionally not auto-assigned.
        // After deployment, grant it explicitly to the trusted owner with:
        // php artisan platform:owner owner@example.com

        foreach ($this->tenantTables as $tableName) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('account_id')->nullable()->constrained('accounts')->restrictOnDelete();
            });

            DB::table($tableName)->update(['account_id' => $legacyAccountId]);
        }

        $this->replaceGlobalUniqueIndexesWithTenantIndexes();
    }

    public function down(): void
    {
        $this->restoreGlobalUniqueIndexes();

        foreach (array_reverse($this->tenantTables) as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'account_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('account_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_id');
            $table->dropColumn(['is_platform_owner', 'phone']);
        });

        Schema::dropIfExists('accounts');
    }

    private function replaceGlobalUniqueIndexesWithTenantIndexes(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['account_id', 'name'], 'categories_account_name_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique(['sku']);
            $table->dropUnique(['barcode']);
            $table->unique(['account_id', 'sku'], 'products_account_sku_unique');
            $table->unique(['account_id', 'barcode'], 'products_account_barcode_unique');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['sale_number']);
            $table->dropUnique(['invoice_number']);
            $table->unique(['account_id', 'sale_number'], 'sales_account_sale_number_unique');
            $table->unique(['account_id', 'invoice_number'], 'sales_account_invoice_number_unique');
        });

        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->dropUnique(['document_type', 'branch_code']);
            $table->unique(
                ['account_id', 'document_type', 'branch_code'],
                'invoice_sequences_account_document_branch_unique'
            );
        });

        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropUnique(['business_date']);
            $table->dropUnique(['reading_number']);
            $table->unique(['account_id', 'business_date'], 'daily_closings_account_date_unique');
            $table->unique(['account_id', 'reading_number'], 'daily_closings_account_reading_unique');
        });
    }

    private function restoreGlobalUniqueIndexes(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_account_name_unique');
            $table->unique('name');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_account_sku_unique');
            $table->dropUnique('products_account_barcode_unique');
            $table->unique('sku');
            $table->unique('barcode');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_account_sale_number_unique');
            $table->dropUnique('sales_account_invoice_number_unique');
            $table->unique('sale_number');
            $table->unique('invoice_number');
        });

        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->dropUnique('invoice_sequences_account_document_branch_unique');
            $table->unique(['document_type', 'branch_code']);
        });

        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropUnique('daily_closings_account_date_unique');
            $table->dropUnique('daily_closings_account_reading_unique');
            $table->unique('business_date');
            $table->unique('reading_number');
        });
    }
};
