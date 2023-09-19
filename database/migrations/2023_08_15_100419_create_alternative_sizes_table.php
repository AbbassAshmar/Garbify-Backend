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
        Schema::create('alternative_sizes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string("size");
            $table->string("unit");
            $table->foreignId("size_id")->constrained()->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alternative_sizes');
    }
};
