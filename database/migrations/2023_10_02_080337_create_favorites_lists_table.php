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
        Schema::create('favorites_lists', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("name");
            $table->integer("views_count",false, true)->default(0);
            $table->integer("likes_count",false, true)->default(0);
            $table->foreignId("user_id")->constrained()->onDelete("cascade");
            $table->boolean("public")->default(true);
            $table->string("thumbnail")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites_lists');
    }
};
