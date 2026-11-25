<?php

namespace Database\Seeders;

use App\Models\Monitoring;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class MonitoringSeeder extends Seeder
{
    public function run(): void
    {
        // Daftar STO yang udah ada di StoSeeder
        $stoCodes = [
            'PKL', 'BTG', 'BDY', 'SBA',
            'KDW', 'KJE', 'CMA', 'PML', 'RDD',
            'TEG', 'MGN', 'SLW', 'BLU', 'ADW',
            'BMU', 'BRB', 'BKA', 'KTM', 'TTL',
        ];

        // Status order
        $statuses = ['STARTWORK', 'CONTWORK', 'COMPWORK', 'WORKFAIL', 'CANCLWORK'];

        // Nama teknisi dummy (buat chief_name)
        $teknisiNames = [
            'Ahmad Fauzi', 'Budi Santoso', 'Cahyo Nugroho', 'Dedi Irawan',
            'Eko Prasetyo', 'Fajar Setiawan', 'Gunawan Wibowo', 'Hadi Susilo',
            'Irfan Maulana', 'Joko Widodo',
        ];

        // Kode teknisi dummy (buat chief_code)
        $teknisiCodes = ['T01', 'T02', 'T03', 'T04', 'T05', 'T06', 'T07', 'T08', 'T09', 'T10'];

        // Error codes buat order yang WORKFAIL
        $errors = ['E001', 'E002', 'E003', 'E004', 'E005'];
        $subErrors = ['SUB1', 'SUB2', 'SUB3'];

        // Hapus data lama biar gak duplikat
        Monitoring::truncate();

        // Generate 120 order dummy
        for ($i = 0; $i < 120; $i++) {
            $status    = $statuses[array_rand($statuses)];
            $createdAt = Carbon::now()->subDays(rand(0, 29))->setTime(rand(7, 17), rand(0, 59));

            // 30% order belum di-assign (buat test fitur Assign)
            $belumAssign = ($i % 10) < 3;

            // Tentukan error code berdasarkan status
            $errorAkhir    = ($status === 'WORKFAIL') ? $errors[array_rand($errors)] : null;
            $subErrorAkhir = ($status === 'WORKFAIL') ? $subErrors[array_rand($subErrors)] : null;

            // Tentukan durasi
            $durasiInstalasi = rand(30, 300) + (rand(0, 99) / 100);
            $durasiTti       = rand(10, 120) + (rand(0, 99) / 100);

            Monitoring::create([
                'wonum'              => 'WO' . str_pad($i + 1, 6, '0', STR_PAD_LEFT),
                'crmordertype'       => 'PS',
                'sto'                => $stoCodes[array_rand($stoCodes)],
                'datel'              => 'DATEL-' . rand(1000, 9999),
                'package_name'       => 'Paket Home ' . rand(10, 100) . ' Mbps',
                'servicenum'         => 'SVC' . rand(100000, 999999),
                'customer_name'      => 'Pelanggan ' . ($i + 1),
                'customer_address'   => 'Jl. Contoh No. ' . rand(1, 100) . ', RT ' . rand(1, 10) . '/RW ' . rand(1, 5),
                'no_hp'              => '08' . rand(1000000000, 9999999999),
                'latitude_ins'       => '-6.' . rand(8000000, 9999999),
                'longitude_ins'      => '109.' . rand(6000000, 9999999),
                'datecreated'        => $createdAt,
                'tgl_manja'          => $createdAt->copy()->addDays(2),
                'status_survey'      => 'DONE',
                'amcrew'             => 'CREW-' . rand(1, 5),
                'chief_code'         => $belumAssign ? null : $teknisiCodes[array_rand($teknisiCodes)],
                'chief_name'         => $belumAssign ? null : $teknisiNames[array_rand($teknisiNames)],
                'status'             => $status,
                'statusdate'         => $createdAt->copy()->addHours(rand(1, 48)),
                'errorcode_awal'     => ($status === 'WORKFAIL') ? $errors[array_rand($errors)] : null,
                'suberrorcode_awal'  => ($status === 'WORKFAIL') ? $subErrors[array_rand($subErrors)] : null,
                'engineermemo'       => 'Catatan teknisi untuk order ' . ($i + 1),
                'odp_name'           => 'ODP-' . rand(1, 50),
                'product'            => 'INTERNET',
                'errorcode_akhir'    => $errorAkhir,
                'suberrorcode_akhir' => $subErrorAkhir,
                'durasi_instalasi'   => $durasiInstalasi,
                'durasi_tti'         => $durasiTti,
            ]);
        }

        $this->command->info('✅ Berhasil generate 120 order dummy!');
        $this->command->info('   - 30% order belum di-assign (buat test fitur Assign)');
        $this->command->info('   - 5 status berbeda: STARTWORK, CONTWORK, COMPWORK, WORKFAIL, CANCLWORK');
        $this->command->info('   - Error code cuma muncul di order WORKFAIL');
    }
}