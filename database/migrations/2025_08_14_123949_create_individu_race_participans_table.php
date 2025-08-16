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
        Schema::create('individu_race_participans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('individu_race_id')->constrained('individu_races')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->restrictOnDelete();
            $table->unsignedTinyInteger('point1')->default(0);
            $table->unsignedTinyInteger('point2')->default(0);
            $table->unsignedTinyInteger('point3')->default(0);
            $table->unsignedTinyInteger('point4')->default(0);
            $table->unsignedTinyInteger('point5')->default(0);
            $table->timestamps();
            $table->unique(['individu_race_id', 'student_id']);
            $table->index('individu_race_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('individu_race_participans');
    }
};
