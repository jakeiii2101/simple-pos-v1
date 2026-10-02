<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('paper_width_mm')->default(80);
            $table->unsignedTinyInteger('content_padding_mm')->default(4);
            $table->unsignedTinyInteger('font_size_px')->default(12);
            $table->boolean('show_logo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_settings');
    }
};
