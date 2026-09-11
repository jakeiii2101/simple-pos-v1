<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('vat_exemption_amount', 12, 2)->default(0);
        });

        DB::table('sale_items')
            ->where('is_senior_pwd_discount_eligible', true)
            ->where('net_total', '>', 0)
            ->orderBy('id')
            ->eachById(function ($item): void {
                DB::table('sale_items')->where('id', $item->id)->update([
                    'vat_exemption_amount' => round(max(0, (float) $item->line_total - (float) $item->discount_amount - (float) $item->net_total), 2),
                ]);
            });

        Schema::create('sale_refunds', function (Blueprint $table) {
            $table->id();
            $table->string('refund_number', 29)->unique();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->foreignId('authorized_by')->constrained('users')->restrictOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('vat_exemption_amount', 12, 2)->default(0);
            $table->decimal('vatable_sales', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('vat_exempt_sales', 12, 2)->default(0);
            $table->decimal('zero_rated_sales', 12, 2)->default(0);
            $table->decimal('non_vat_sales', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2);
            $table->string('reason', 500);
            $table->boolean('inventory_restocked')->default(true);
            $table->timestamp('processed_at');
            $table->timestamps();
            $table->index(['sale_id', 'processed_at']);
            $table->index('processed_at');
        });

        Schema::create('sale_refund_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_refund_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('vat_exemption_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('refund_amount', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_refund_items');
        Schema::dropIfExists('sale_refunds');
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn('vat_exemption_amount');
        });
    }
};
