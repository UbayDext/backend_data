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
        Schema::create('individu_races', function (Blueprint $table) {
            $table->id();
            $table->string('name_lomba');
            $table->date('start_date');
            $table->date('end_date')->index();
            $table->foreignId('ekskul_id')->constrained('ekskuls')->cascadeOnDelete();
            $table->enum('status', ['Berlangsung', 'Selesai'])->default('Berlangsung')->index();
            $table->foreignId('lombad_id')->nullable()->constrained('lombads')->onDelete('set null');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('individu_races');
    }
};
