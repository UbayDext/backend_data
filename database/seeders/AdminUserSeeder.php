<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan kolom role & ekskul_id sudah ada di tabel users (migrasi sudah dijalankan)
        // php artisan migrate  (kalau belum)

        // Gunakan env agar mudah diubah tanpa ngedit kode
        $email = env('SEED_ADMIN_EMAIL', 'super@admin.com');
        $name  = env('SEED_ADMIN_NAME',  'Admin');
        $pass  = env('SEED_ADMIN_PASS',  'password'); // Model User kamu sudah casts 'password' => 'hashed'

        // Jika user dg email tsb belum ada → dibuat; kalau sudah ada → diupdate jadi admin
        User::updateOrCreate(
            ['email' => $email],
            [
                'name'      => $name,
                'password'  => $pass,   // akan di-hash otomatis oleh casts
                'role'      => 'admin',
                'ekskul_id' => null,
            ]
        );

        // (Opsional) contoh membuat 1 guru ekskul tertentu:
        // User::updateOrCreate(
        //     ['email' => 'guru@bola.com'],
        //     ['name' => 'Guru Bola', 'password' => 'password', 'role' => 'guru_ekskul', 'ekskul_id' => 7]
        // );
    }
}
