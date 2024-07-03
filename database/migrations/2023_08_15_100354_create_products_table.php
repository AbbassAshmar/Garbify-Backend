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
        
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string("name")->unique();
            $table->integer("quantity",false , true);

            // constrained : creates the connection based on "category_id" name
            $table->foreignId("category_id")->onDelete("cascade")->constrained();

            $table->decimal("original_pice",8,2,true)->default(0,0);
            $table->decimal("selling_price",8,2,true)->default(0,0);
            
            $table->string("status",64)->default("in stock");
            $table->text("description");
            $table->string("type")->default("General");
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
