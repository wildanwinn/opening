<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sto;

class StoSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['PKL', 'Pekalongan',    'SA Pekalongan'],
            ['BTG', 'Batang',        'SA Pekalongan'],
            ['BDY', 'Bandar',        'SA Pekalongan'],
            ['SBA', 'Subah',         'SA Pekalongan'],

            ['KDW', 'Kedungwuni',    'Pemalang'],
            ['KJE', 'Kajen',         'Pemalang'],
            ['CMA', 'Comal',         'Pemalang'],
            ['PML', 'Pemalang',      'Pemalang'],
            ['RDD', 'Randudongkal',  'Pemalang'],

            ['TEG', 'Tegal',         'Tegal'],
            ['MGN', 'Margasari',     'Tegal'],
            ['SLW', 'Slawi',         'Tegal'],
            ['BLU', 'Balapulang',    'Tegal'],
            ['ADW', 'Adiwerna',      'Tegal'],

            ['BMU', 'Bumiayu',       'Brebes'],
            ['BRB', 'Brebes',        'Brebes'],
            ['BKA', 'Bantarkawung',  'Brebes'],
            ['KTM', 'Ketanggungan',  'Brebes'],
            ['TTL', 'Tanjung',       'Brebes'],
        ];

        foreach ($data as $d) {
            Sto::updateOrCreate(
                ['kode_sto' => $d[0]],
                ['nama_sto' => $d[1], 'wilayah' => $d[2]]
            );
        }
    }
}