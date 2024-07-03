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
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("category");
            $table->string("display_name");
            $table->text("description")->nullable();
            $table->string("image_url")->nullable();
        }); 

        Schema::table("categories", function(Blueprint $table){
            $table->foreignId("parent_id")->nullable()->constrained("categories")->onDelete("set null");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
