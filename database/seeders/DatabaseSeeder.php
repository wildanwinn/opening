<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            StoSeeder::class,
            UserSeeder::class,
            MonitoringSeeder::class,   // ← TAMBAH INI
        ]);
    }
}