<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bir_settings', function (Blueprint $table) {
            $table->id();
            $table->string('registered_name', 200);
            $table->string('trade_name', 200)->nullable();
            $table->string('tin', 30);
            $table->string('branch_code', 10)->default('00000');
            $table->text('registered_address');
            $table->string('rdo_code', 10)->nullable();
            $table->string('tax_type', 20)->default('non_vat');
            $table->decimal('vat_rate', 5, 2)->default(12);
            $table->string('permit_number', 100)->nullable();
            $table->date('permit_date')->nullable();
            $table->text('invoice_footer')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 30)->default('sales_invoice');
            $table->string('branch_code', 10)->default('00000');
            $table->string('prefix', 30)->default('SI-');
            $table->unsignedBigInteger('current_number')->default(0);
            $table->unsignedBigInteger('starting_number')->default(1);
            $table->unsignedBigInteger('ending_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['document_type', 'branch_code']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('invoice_number', 60)->nullable()->unique();
            $table->string('tax_type', 20)->nullable();
            $table->decimal('vatable_sales', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('vat_exempt_sales', 12, 2)->default(0);
            $table->decimal('zero_rated_sales', 12, 2)->default(0);
            $table->decimal('non_vat_sales', 12, 2)->default(0);
            $table->json('seller_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique(['invoice_number']);
            $table->dropColumn([
                'invoice_number',
                'tax_type',
                'vatable_sales',
                'vat_amount',
                'vat_exempt_sales',
                'zero_rated_sales',
                'non_vat_sales',
                'seller_snapshot',
            ]);
        });

        Schema::dropIfExists('invoice_sequences');
        Schema::dropIfExists('bir_settings');
    }
};
