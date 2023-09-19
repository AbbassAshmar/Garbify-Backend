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
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId("product_id")->constrained()->onDelete("cascade");
            $table->string("image_details")->nullable();
            $table->string("image_url")->unique();
            $table->foreignId("color_id")->constrained()->onDelete("set null");
            $table->foreignId("size_id")->constrained()->onDelete("set null");
            $table->boolean("is_thumbnail")->default(false)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
