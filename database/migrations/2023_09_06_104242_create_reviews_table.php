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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer("user_height",false,true)->nullable();
            $table->integer("user_weight", false ,true)->nullable();
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->foreignId("product_id")->constrained()->onDelete("cascade");
            $table->text("title");
            $table->text("text");
            $table->foreignId("size_id")->nullable()->constrained("sizes")->onDelete("set null");
            $table->foreignId("color_id")->nullable()->constrained()->onDelete("set null");
            $table->integer("helpful_count",false,true)->default(0);
            $table->double("product_rating",2,1,true)->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
