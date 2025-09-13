<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('guru_ekskul')->index();
            }
            if (!Schema::hasColumn('users', 'ekskul_id')) {
                $table->unsignedBigInteger('ekskul_id')->nullable()->index();
            }
        });

        // Tambah FK TERPISAH agar aman di beberapa driver DB
        if (Schema::hasTable('ekskuls')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreign('ekskul_id')->references('id')->on('ekskuls')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        // drop FK dulu baru kolomnya
        if (Schema::hasColumn('users', 'ekskul_id')) {
            Schema::table('users', function (Blueprint $table) {
                try { $table->dropForeign(['ekskul_id']); } catch (\Throwable $e) {}
                $table->dropColumn('ekskul_id');
            });
        }
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};

