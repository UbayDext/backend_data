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
        Schema::create('team_races', function (Blueprint $table) {
            $table->id();
            $table->string('name_group');
            $table->string('name_team1');
            $table->string('name_team2');
            $table->string('name_team3');
            $table->string('name_team4');
            $table->string('name_team5');
            $table->foreignId('lombad_id')->constrained('lombads')->cascadeOnDelete();
            $table->string('champion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_races');
    }
};
