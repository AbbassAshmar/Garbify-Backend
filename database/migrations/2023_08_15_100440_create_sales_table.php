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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer("quantity",false,true)->nullable();
            $table->decimal("sale_percentage",5,2,true)->default(0.0);
            $table->foreignId("product_id")->nullable()->onDelete("set null")->constrained();
            $table->datetime("ends_at")->nullable();
            $table->datetime("starts_at");

        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
