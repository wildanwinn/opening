<?php

namespace App\Console\Commands;

use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Console\Command;

/** Bagi beberapa order ke seorang teknisi supaya tampilan mobile bisa dites. */
class TugasDemo extends Command
{
    protected $signature = 'tugas:demo {teknisi : NIK atau nama teknisi} {--jumlah=4 : banyak order}';
    protected $description = 'Beri tugas contoh ke teknisi (untuk tes tampilan mobile)';

    public function handle(): int
    {
        $cari = $this->argument('teknisi');
        $user = User::where('role', 'teknisi')
            ->where(fn ($q) => $q->where('nik', $cari)->orWhere('nama', $cari))->first();

        if (! $user) {
            $this->error("Teknisi '$cari' tidak ditemukan.");
            return self::FAILURE;
        }

        $q = Monitoring::whereNotNull('odp_name')->where(fn ($w) => $w->whereNull('status')->orWhere('status', '!=', 'COMPWORK'));
        if ($user->sto) {
            $q->where('sto', $user->sto->kode_sto);
        }
        $daftar = $q->orderByRaw('chief_name IS NULL DESC')->orderByDesc('id')->limit((int) $this->option('jumlah'))->get();

        if ($daftar->isEmpty()) {
            $this->error('Tidak ada order (dengan ODP) yang bisa diberikan. Upload data monitoring dulu.');
            return self::FAILURE;
        }

        foreach ($daftar as $m) {
            $m->update([
                'chief_code' => filled($user->kode_teknisi) ? $user->kode_teknisi : $m->chief_code,
                'chief_name' => $user->nama,
                'tahap'      => null,
            ]);
        }

        $this->info("{$daftar->count()} order diberikan ke {$user->nama}: " . $daftar->pluck('wonum')->implode(', '));
        return self::SUCCESS;
    }
}
