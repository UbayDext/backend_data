<?php

namespace Database\Seeders;

use App\Models\Studi;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studi = ['TK', 'SD', 'SMP'];

        foreach ($studi as $nama_studi) {
            Studi::updateOrCreate(['nama_studi' => $nama_studi]);
        }
    }
}
