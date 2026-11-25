<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['nik' => '12345678', 'nama' => 'Administrator', 'password' => 'admin123',   'role' => 'admin'],
            ['nik' => '87654321', 'nama' => 'HD Tegal',      'password' => 'hd123',      'role' => 'hd'],
            ['nik' => '11223344', 'nama' => 'Teknisi Tegal', 'password' => 'teknisi123', 'role' => 'teknisi'],
        ];

        foreach ($users as $u) {
            User::updateOrCreate(['nik' => $u['nik']], $u);
        }
    }
}