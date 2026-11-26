<?php

namespace App\Console\Commands;

use App\Models\Odp;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SinkronOdp extends Command
{
    protected $signature = 'odp:sinkron';

    protected $description = 'Isi nama ODP order yang kosong dari KPRO, hubungkan order ke master ODP (odp_id), lalu tampilkan ringkasan diagnosa';

    public function handle(): int
    {
        $dariKpro = Odp::isiDariKpro();
        $dariSc   = Odp::isiDariScOrder();
        $dariSvc  = Odp::isiDariServicenum();
        Odp::hubungkanOdpId();

        $ada    = fn ($kol) => DB::table($kol[0])->whereNotNull($kol[1])->where($kol[1], '!=', '');

        $totalOrder   = DB::table('monitorings')->count();
        $orderAdaOdp  = $ada(['monitorings', 'odp_name'])->count();
        $kproTotal    = DB::table('kpro_orders')->count();
        $kproAdaOdp   = $ada(['kpro_orders', 'odp_name'])->count();
        $kproDiOrder  = DB::table('kpro_orders')->join('monitorings', 'monitorings.wonum', '=', 'kpro_orders.wonum')->count();
        $odpTotal     = DB::table('odps')->count();
        $odpBerKoord  = DB::table('odps')->whereNotNull('latitude')->whereNotNull('longitude')->count();
        $terhubung    = DB::table('monitorings')->join('odps', 'odps.nama_odp', '=', 'monitorings.odp_name')->count();

        $this->info("{$dariKpro} order diisi nama ODP-nya dari KPRO (WONUM sama).");
        $this->info("{$dariSc} order diisi nama ODP-nya dari WO lain di SC Order yang sama.");
        $this->info("{$dariSvc} order diisi nama ODP-nya dari WO lain di Service Num yang sama.");
        $this->newLine();
        $this->table(['Hitungan', 'Jumlah'], [
            ['Order di Data Monitoring', $totalOrder],
            ['  - yang punya SC Order', DB::table('monitorings')->whereNotNull('scorderno')->where('scorderno', '!=', '')->count()],
            ['  - yang nama ODP-nya terisi', $orderAdaOdp],
            ['  - yang terhubung ke master ODP', $terhubung],
            ['Baris KPRO', $kproTotal],
            ['  - yang ODP_NAME-nya terisi', $kproAdaOdp],
            ['  - yang WONUM-nya ada di Data Monitoring', $kproDiOrder],
            ['Master ODP', $odpTotal],
            ['  - yang punya koordinat', $odpBerKoord],
        ]);

        $tidakCocok = DB::table('monitorings')
            ->leftJoin('odps', 'odps.nama_odp', '=', 'monitorings.odp_name')
            ->whereNotNull('monitorings.odp_name')->where('monitorings.odp_name', '!=', '')
            ->whereNull('odps.id')
            ->limit(5)->pluck('monitorings.odp_name');

        if ($tidakCocok->isNotEmpty()) {
            $contohMaster = DB::table('odps')->limit(3)->pluck('nama_odp');
            $this->warn('Nama ODP di order yang TIDAK ditemukan di master ODP (contoh): ' . $tidakCocok->implode(' | '));
            $this->warn('Contoh nama di master ODP: ' . $contohMaster->implode(' | '));
        }

        return self::SUCCESS;
    }
}
