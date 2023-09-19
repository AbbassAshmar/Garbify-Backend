<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('colors_products', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId("color_id")->onDelete("cascade")->constrained();
            $table->foreignId("product_id")->onDelete("cascade")->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('colors_products');
    }
};
