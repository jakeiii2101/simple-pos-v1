<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('method', 20);
            $table->decimal('amount', 12, 2);
            $table->decimal('amount_tendered', 12, 2);
            $table->decimal('change_due', 12, 2)->default(0);
            $table->string('reference', 100)->nullable();
            $table->timestamps();

            $table->index(['method', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
