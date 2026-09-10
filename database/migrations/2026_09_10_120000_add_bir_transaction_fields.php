<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('tax_type', 20)->default('vatable');
            $table->boolean('is_senior_pwd_discount_eligible')->default(false);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('buyer_name', 200)->nullable();
            $table->string('buyer_tin', 30)->nullable();
            $table->text('buyer_address')->nullable();
            $table->string('buyer_business_style', 200)->nullable();
            $table->string('discount_beneficiary_name', 200)->nullable();
            $table->string('discount_id_number', 100)->nullable();
            $table->decimal('vat_exemption_amount', 12, 2)->default(0);
        });

        Schema::table('sale_items', function (Blueprint $table) {
            $table->string('tax_type', 20)->default('vatable');
            $table->boolean('is_senior_pwd_discount_eligible')->default(false);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('vat_amount', 12, 2)->default(0);
            $table->decimal('net_total', 12, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn([
                'tax_type',
                'is_senior_pwd_discount_eligible',
                'discount_amount',
                'vat_amount',
                'net_total',
            ]);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'buyer_name',
                'buyer_tin',
                'buyer_address',
                'buyer_business_style',
                'discount_beneficiary_name',
                'discount_id_number',
                'vat_exemption_amount',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['tax_type', 'is_senior_pwd_discount_eligible']);
        });
    }
};
