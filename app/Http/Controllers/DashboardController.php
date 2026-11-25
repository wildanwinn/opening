<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\Sto;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user  = Auth::user();

        // Teknisi langsung diarahkan ke tampilan mobile tugas
        if ($user->isTeknisi()) {
            return redirect()->route('tugas.index');
        }

        $query = Monitoring::query();

        // === Filter by role: teknisi cuma lihat STO-nya sendiri ===
        $query->untukUser($user);

        // === Filter periode ===
        $periode = $request->input('periode', '30hari');
        $dari = match ($periode) {
            'hariini'  => today(),
            '7hari'    => now()->subDays(6)->startOfDay(),
            '30hari'   => now()->subDays(29)->startOfDay(),
            'bulanini' => now()->startOfMonth(),
            default    => null,
        };

        if ($dari) {
            $query->where('datecreated', '>=', $dari);
        }

        // Basis query yang udah ke-filter role & periode
        $basis = (clone $query);

        // === Data untuk kartu-kartu dashboard ===
        $data = [
            // Kartu baris 1 — Teknisi
            'totalTeknisi'    => User::where('role', 'teknisi')->count(),

            'teknisiHariIni'  => (clone $basis)
                                    ->whereDate('datecreated', today())
                                    ->whereNotNull('chief_name')
                                    ->distinct('chief_name')
                                    ->count('chief_name'),

            'teknisiTerorder' => (clone $basis)
                                    ->whereIn('status', ['STARTWORK', 'CONTWORK'])
                                    ->whereNotNull('chief_name')
                                    ->distinct('chief_name')
                                    ->count('chief_name'),

            'teknisiIdle'     => max(
                                    User::where('role', 'teknisi')->count()
                                    - (clone $basis)
                                        ->whereIn('status', ['STARTWORK', 'CONTWORK'])
                                        ->whereNotNull('chief_name')
                                        ->distinct('chief_name')
                                        ->count('chief_name'),
                                    0
                                ),

            // Kartu baris 2 — Order
            'totalOrder'      => (clone $basis)->count(),
            'ov'              => (clone $basis)->whereNotNull('errorcode_akhir')->count(),
            'comply'          => (clone $basis)->where('status', 'COMPWORK')->count(),

            // Kartu baris 3 — Milestone penugasan
            'unassigned'      => (clone $basis)->whereNull('chief_name')->count(),
            'assigned'        => (clone $basis)->whereNotNull('chief_name')->where('status', 'STARTWORK')->count(),
            'progress'        => (clone $basis)->where('status', 'CONTWORK')->count(),
            'done'            => (clone $basis)->where('status', 'COMPWORK')->count(),

            // Grafik TTR — harian dalam 30 hari terakhir (gak kena filter periode)
            'ttrHarian'       => (clone $query)
                                    ->whereDate('datecreated', '>=', now()->subDays(29)->startOfDay())
                                    ->selectRaw('DATE(datecreated) as tanggal')
                                    ->selectRaw("SUM(CASE WHEN status = 'COMPWORK' THEN 1 ELSE 0 END) as comply")
                                    ->selectRaw("SUM(CASE WHEN errorcode_akhir IS NOT NULL THEN 1 ELSE 0 END) as overtime")
                                    ->selectRaw('ROUND(AVG(durasi_tti), 2) as avg_ttr')
                                    ->groupBy('tanggal')
                                    ->orderBy('tanggal')
                                    ->get(),

            // Rekap per wilayah (filter by role juga)
            'rekapWilayah'    => $this->rekapPerWilayah($user, $periode),

            // Order yang belum di-assign (buat modal Assign)
            'orderUnassigned' => (clone $basis)
                                    ->whereNull('chief_name')
                                    ->latest('datecreated')
                                    ->limit(50)
                                    ->get(),
        ];

        // === Daftar teknisi + status aktif hari ini ===
        $orderAktifHariIni = (clone $basis)
            ->whereDate('datecreated', today())
            ->whereIn('status', ['STARTWORK', 'CONTWORK'])
            ->get(['chief_code', 'chief_name']);

        $kodeAktif = $orderAktifHariIni
            ->pluck('chief_code')
            ->filter()
            ->map(fn ($k) => strtolower(trim($k)))
            ->unique();

        $namaAktif = $orderAktifHariIni
            ->pluck('chief_name')
            ->filter()
            ->map(fn ($n) => strtolower(trim($n)))
            ->unique();

        $data['daftarTeknisi'] = User::where('role', 'teknisi')
            ->with('sto')
            ->orderBy('nama')
            ->get()
            ->map(function ($t) use ($kodeAktif, $namaAktif) {
                if (!blank($t->kode_teknisi)) {
                    $t->sedang_aktif = $kodeAktif->contains(strtolower(trim($t->kode_teknisi)));
                } else {
                    $t->sedang_aktif = $namaAktif->contains(strtolower(trim($t->nama)));
                }
                return $t;
            });

        // =============================================================
        // Data mentah untuk modal (biar card dashboard bisa diklik)
        // =============================================================
        $data['modalData'] = [
            'teknisi'    => $data['daftarTeknisi'],
            'order'      => (clone $basis)->latest('datecreated')->limit(100)->get(),
            'ov'         => (clone $basis)->whereNotNull('errorcode_akhir')->latest('datecreated')->limit(100)->get(),
            'comply'     => (clone $basis)->where('status', 'COMPWORK')->latest('datecreated')->limit(100)->get(),
            'unassigned' => (clone $basis)->whereNull('chief_name')->latest('datecreated')->limit(100)->get(),
            'assigned'   => (clone $basis)->whereNotNull('chief_name')->where('status', 'STARTWORK')->latest('datecreated')->limit(100)->get(),
            'progress'   => (clone $basis)->where('status', 'CONTWORK')->latest('datecreated')->limit(100)->get(),
            'done'       => (clone $basis)->where('status', 'COMPWORK')->latest('datecreated')->limit(100)->get(),
        ];

        return view('dashboard', $data);
    }

    private function rekapPerWilayah($user, string $periode = '30hari')
    {
        $dari = match ($periode) {
            'hariini'  => today(),
            '7hari'    => now()->subDays(6)->startOfDay(),
            '30hari'   => now()->subDays(29)->startOfDay(),
            'bulanini' => now()->startOfMonth(),
            default    => null,
        };

        $stoQuery = Sto::query();
        if ($user->isTeknisi()) {
            if ($user->sto) {
                $stoQuery->where('kode_sto', $user->sto->kode_sto);
            } else {
                $stoQuery->whereRaw('1 = 0'); // teknisi tanpa STO: tidak ada rekap wilayah
            }
        }

        $filterPeriode = fn ($q) => $dari ? $q->where('datecreated', '>=', $dari) : $q;

        $perSto = $stoQuery
            ->withCount([
                'monitorings as ov_count'         => fn ($q) => $filterPeriode($q)->whereNotNull('errorcode_akhir'),
                'monitorings as comply_count'     => fn ($q) => $filterPeriode($q)->where('status', 'COMPWORK'),
                'monitorings as unassigned_count' => fn ($q) => $filterPeriode($q)->whereNull('chief_name'),
                'monitorings as assigned_count'   => fn ($q) => $filterPeriode($q)->whereNotNull('chief_name')->where('status', 'STARTWORK'),
                'monitorings as progress_count'   => fn ($q) => $filterPeriode($q)->where('status', 'CONTWORK'),
                'monitorings as done_count'       => fn ($q) => $filterPeriode($q)->where('status', 'COMPWORK'),
            ])
            ->orderBy('wilayah')
            ->orderBy('kode_sto')
            ->get();

        return $perSto->groupBy('wilayah')->map(function ($stoDalamWilayah, $wilayah) {
            return [
                'wilayah'    => $wilayah,
                'jumlahSto'  => $stoDalamWilayah->count(),
                'sto'        => $stoDalamWilayah,
                'ov'         => $stoDalamWilayah->sum('ov_count'),
                'comply'     => $stoDalamWilayah->sum('comply_count'),
                'unassigned' => $stoDalamWilayah->sum('unassigned_count'),
                'assigned'   => $stoDalamWilayah->sum('assigned_count'),
                'progress'   => $stoDalamWilayah->sum('progress_count'),
                'done'       => $stoDalamWilayah->sum('done_count'),
            ];
        })->values();
    }
}