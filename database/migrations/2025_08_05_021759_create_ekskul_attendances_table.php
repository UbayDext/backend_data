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
        Schema::create('ekskul_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
            $table->foreignId('ekskul_id')->constrained('ekskuls')->onDelete('cascade');
            $table->foreignId('studi_id')->constrained('studis')->onDelete('cascade');
            $table->date('tanggal');
            $table->enum('status', ['H', 'I', 'S', 'A']);
            $table->timestamps();
            $table->unique(['student_id', 'ekskul_id', 'studi_id', 'tanggal'], 'unique_absen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ekskul_attendances');
    }
};
