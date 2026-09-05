<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('sku', 100)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('name', 150);
            $table->decimal('cost_price', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_level')->default(5);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['category_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
