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
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->timestamp('canceled_at')->nullable();
            $table->foreignId("order_id")->constrained()->onDelete("cascade");
            $table->foreignId("product_id")->nullable()->constrained()->onDelete("set null");
            $table->integer("ordered_quantity")->default(1);
            $table->foreignId('color_id')->nullable()->constrained()->onDelete("set null");
            $table->foreignId("size_id")->nullable()->constrained()->onDelete("set null");

            $table->integer("amount_total")->default(0);
            $table->integer("amount_tax")->default(0);
            $table->integer("amount_subtotal")->default(0);
            $table->integer("amount_unit")->default(0);
            $table->integer('amount_discount')->default(0);

            $table->foreignId('sale_id')->nullable()->constrained()->onDelete("set null");

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_details');
    }
};
