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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->text("status");
            $table->integer("total_cost");
            $table->integer("tax_cost")->default(0);
            $table->integer("products_cost")->default(0);
            $table->dateTime("canceled_at")->nullable();
            $table->foreignId("shipping_address_id")->nullable()->constrained()->onDelete("set Null");
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("set Null");
            $table->foreignId("shipping_method_id")->nullable()->constrained()->onDelete("set Null");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
