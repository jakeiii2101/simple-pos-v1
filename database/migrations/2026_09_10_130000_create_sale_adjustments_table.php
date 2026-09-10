<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('authorized_by')->constrained('users')->restrictOnDelete();
            $table->string('type', 20);
            $table->decimal('amount', 12, 2);
            $table->string('reason', 500);
            $table->boolean('inventory_restocked')->default(true);
            $table->timestamp('processed_at');
            $table->timestamps();

            $table->index(['type', 'processed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_adjustments');
    }
};
