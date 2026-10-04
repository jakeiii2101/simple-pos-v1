<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 220)->unique();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('account_requests', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 200);
            $table->string('owner_name', 150);
            $table->string('email', 255)->index();
            $table->string('password_hash')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamps();

            $table->index(['email', 'status']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('business_id')->nullable()->after('id')->constrained('businesses')->restrictOnDelete();
            $table->boolean('is_platform_owner')->default(false)->after('status')->index();
            $table->index(['business_id', 'role', 'status']);
        });

        $businessTables = [
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

        foreach ($businessTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->foreignId('business_id')->nullable()->constrained('businesses')->restrictOnDelete();
                $table->index('business_id');
            });
        }

        $businessName = DB::table('bir_settings')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->value('trade_name')
            ?? DB::table('bir_settings')
                ->orderByDesc('is_active')
                ->orderByDesc('id')
                ->value('registered_name')
            ?? 'SniperPOS Primary Business';

        $slugBase = Str::slug((string) $businessName) ?: 'sniperpos-primary-business';
        $primaryBusinessId = DB::table('businesses')->insertGetId([
            'name' => (string) $businessName,
            'slug' => $slugBase.'-primary',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->update(['business_id' => $primaryBusinessId]);

        foreach ($businessTables as $tableName) {
            DB::table($tableName)->update(['business_id' => $primaryBusinessId]);
        }

        $platformOwnerId = DB::table('users')
            ->where('role', 'admin')
            ->where('status', 'active')
            ->orderBy('id')
            ->value('id');

        if ($platformOwnerId !== null) {
            DB::table('users')
                ->where('id', $platformOwnerId)
                ->update(['is_platform_owner' => true]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_name_unique');
            $table->unique(['business_id', 'name'], 'categories_business_name_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_sku_unique');
            $table->dropUnique('products_barcode_unique');
            $table->unique(['business_id', 'sku'], 'products_business_sku_unique');
            $table->unique(['business_id', 'barcode'], 'products_business_barcode_unique');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_sale_number_unique');
            $table->dropUnique('sales_invoice_number_unique');
            $table->unique(['business_id', 'sale_number'], 'sales_business_sale_number_unique');
            $table->unique(['business_id', 'invoice_number'], 'sales_business_invoice_number_unique');
        });

        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->dropUnique('invoice_sequences_document_type_branch_code_unique');
            $table->unique(
                ['business_id', 'document_type', 'branch_code'],
                'invoice_sequences_business_document_branch_unique'
            );
        });

        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropUnique('daily_closings_business_date_unique');
            $table->dropUnique('daily_closings_reading_number_unique');
            $table->unique(['business_id', 'business_date'], 'daily_closings_business_date_tenant_unique');
            $table->unique(['business_id', 'reading_number'], 'daily_closings_business_reading_unique');
        });

        Schema::table('sale_refunds', function (Blueprint $table) {
            $table->dropUnique('sale_refunds_refund_number_unique');
            $table->unique(['business_id', 'refund_number'], 'sale_refunds_business_refund_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sale_refunds', function (Blueprint $table) {
            $table->dropUnique('sale_refunds_business_refund_number_unique');
            $table->unique('refund_number');
        });

        Schema::table('daily_closings', function (Blueprint $table) {
            $table->dropUnique('daily_closings_business_date_tenant_unique');
            $table->dropUnique('daily_closings_business_reading_unique');
            $table->unique('business_date');
            $table->unique('reading_number');
        });

        Schema::table('invoice_sequences', function (Blueprint $table) {
            $table->dropUnique('invoice_sequences_business_document_branch_unique');
            $table->unique(['document_type', 'branch_code']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_business_sale_number_unique');
            $table->dropUnique('sales_business_invoice_number_unique');
            $table->unique('sale_number');
            $table->unique('invoice_number');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_business_sku_unique');
            $table->dropUnique('products_business_barcode_unique');
            $table->unique('sku');
            $table->unique('barcode');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_business_name_unique');
            $table->unique('name');
        });

        $businessTables = [
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

        foreach ($businessTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropForeign(['business_id']);
                $table->dropIndex([$table->getTable().'_business_id_index']);
                $table->dropColumn('business_id');
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['business_id', 'role', 'status']);
            $table->dropIndex(['is_platform_owner']);
            $table->dropForeign(['business_id']);
            $table->dropColumn(['business_id', 'is_platform_owner']);
        });

        Schema::dropIfExists('account_requests');
        Schema::dropIfExists('businesses');
    }
};
