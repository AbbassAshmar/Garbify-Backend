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
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId("user_id")->nullable()->constrained()->onDelete("set null");
            $table->text("country");
            $table->text("city");
            $table->text("state")->nullable();
            $table->text("address_line_1");
            $table->text("address_line_2")->nullable();
            $table->text("postal_code")->nullable();
            $table->text("email");
            $table->text("phone_number");
            $table->text("recipient_name");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_addresses');
    }
};
