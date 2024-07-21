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
            
            $table->dateTime("canceled_at")->nullable();
            $table->foreignId("shipping_address_id")->nullable()->constrained()->onDelete("set Null");
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("set Null");
            $table->foreignId("shipping_method_id")->nullable()->constrained()->onDelete("set Null");
            $table->foreignId("status_id")->constrained("orders_statuses")->onDelete("cascade");

            $table->integer("amount_total")->default(0);
            $table->integer("amount_tax")->default(0);
            $table->integer("amount_subtotal")->default(0);
            $table->decimal("percentage_tax",5,2,true)->default(0.0);
            $table->text("payment_intent_id");
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
