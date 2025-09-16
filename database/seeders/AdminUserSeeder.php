<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {

        $email = env('SEED_ADMIN_EMAIL', 'super@admin.com');
        $name  = env('SEED_ADMIN_NAME',  'Admin');
        $pass  = env('SEED_ADMIN_PASS',  'password');


        User::updateOrCreate(
            ['email' => $email],
            [
                'name'      => $name,
                'password'  => $pass,
                'role'      => 'admin',
                'ekskul_id' => null,
            ]
        );

        // User::updateOrCreate(
        //     ['email' => 'guru@bola.com'],
        //     ['name' => 'Guru Bola', 'password' => 'password', 'role' => 'guru_ekskul', 'ekskul_id' => 7]
        // );
    }
}
