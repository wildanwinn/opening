<?php

use App\Models\Odp;
use App\Services\MonitoringRowMapper;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data lama: SCORDERNO dan ODP_NAME masih berisi teks panjang dari Excel.
     * Dipotong sesuai arahan pembimbing (SCORDERNO = bagian setelah "-" sebelum "_",
     * ODP_NAME = nama ODP saja), lalu dipasangkan ulang ke master ODP.
     */
    public function up(): void
    {
        DB::table('monitorings')->select('id', 'scorderno', 'odp_name', 'odp_id')->orderBy('id')->chunkById(500, function ($baris) {
            foreach ($baris as $b) {
                $ubah = [];

                $sc = MonitoringRowMapper::kodeScorderno($b->scorderno);
                if ($sc !== $b->scorderno && $b->scorderno !== null) {
                    $ubah['scorderno'] = $sc;
                }

                $odp = MonitoringRowMapper::namaOdp($b->odp_name);
                if ($odp !== $b->odp_name && $b->odp_name !== null) {
                    $ubah['odp_name'] = $odp;
                    $ubah['odp_id'] = $odp ? Odp::where('odp_name', $odp)->value('id') : null;
                }

                if ($ubah) {
                    DB::table('monitorings')->where('id', $b->id)->update($ubah);
                }
            }
        });
    }

    public function down(): void
    {
        // data asli yang panjang tidak bisa dikembalikan
    }
};
