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
        Schema::create('lombads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['Individu', 'Team']);
            $table->unsignedBigInteger('ekskul_id');
            $table->foreign('ekskul_id')->references('id')->on('ekskuls')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lombads');
    }
};
