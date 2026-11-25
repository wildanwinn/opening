<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Sto;
use App\Models\Monitoring;
use Illuminate\Http\Request;

class TeknisiController extends Controller
{
    public function index(Request $request)
    {
        $query = User::where('role', 'teknisi')->with('sto');

        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('nama', 'like', "%{$cari}%")
                  ->orWhere('nik', 'like', "%{$cari}%")
                  ->orWhere('kode_teknisi', 'like', "%{$cari}%");
            });
        }

        if ($request->filled('sto_id')) {
            $query->where('sto_id', $request->sto_id);
        }

        $teknisi = $query->orderBy('nama')->get();

        // Siapa yang aktif hari ini
        $kodeAktif = Monitoring::whereDate('datecreated', today())
            ->whereIn('status', ['STARTWORK', 'CONTWORK'])
            ->whereNotNull('chief_code')
            ->pluck('chief_code')
            ->map(fn ($k) => strtolower(trim($k)))
            ->unique();

        // Statistik semua teknisi dihitung sekali (grup), bukan 3 query per teknisi
        $perKode = $this->statistikGrup('chief_code');
        $perNama = $this->statistikGrup('chief_name');

        $teknisi->each(function ($t) use ($kodeAktif, $perKode, $perNama) {
            $t->sedang_aktif = $t->kode_teknisi
                ? $kodeAktif->contains(strtolower(trim($t->kode_teknisi)))
                : false;

            $stat = $t->kode_teknisi
                ? $perKode->get(strtolower(trim($t->kode_teknisi)))
                : $perNama->get(strtolower(trim($t->nama)));

            $t->total_order   = (int) ($stat->total   ?? 0);
            $t->order_selesai = (int) ($stat->selesai ?? 0);
            $t->order_gagal   = (int) ($stat->gagal   ?? 0);
        });

        return view('teknisi.index', [
            'daftarTeknisi' => $teknisi,
            'daftarSto'     => Sto::orderBy('kode_sto')->get(),
        ]);
    }

    public function show(User $teknisi)
    {
        if ($teknisi->role !== 'teknisi') {
            abort(404);
        }

        $teknisi->load('sto');

        $filterTeknisi = function ($q) use ($teknisi) {
            if ($teknisi->kode_teknisi) {
                $q->where('chief_code', $teknisi->kode_teknisi);
            } else {
                $q->where('chief_name', $teknisi->nama);
            }
        };

        $orders = Monitoring::where($filterTeknisi)->latest('datecreated')->paginate(20);

        $stats = [
            'total'    => Monitoring::where($filterTeknisi)->count(),
            'comply'   => Monitoring::where('status', 'COMPWORK')->where($filterTeknisi)->count(),
            'ov'       => Monitoring::where('status', 'WORKFAIL')->where($filterTeknisi)->count(),
            'progress' => Monitoring::where('status', 'CONTWORK')->where($filterTeknisi)->count(),
        ];

        return view('teknisi.show', compact('teknisi', 'orders', 'stats'));
    }

    /**
     * Total / selesai / gagal per nilai kolom (chief_code atau chief_name),
     * di-key dengan huruf kecil supaya cocok dengan pencarian per teknisi.
     */
    private function statistikGrup(string $kolom)
    {
        return Monitoring::whereNotNull($kolom)
            ->select($kolom)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'COMPWORK' THEN 1 ELSE 0 END) as selesai")
            ->selectRaw("SUM(CASE WHEN status = 'WORKFAIL' THEN 1 ELSE 0 END) as gagal")
            ->groupBy($kolom)
            ->get()
            ->keyBy(fn ($r) => strtolower(trim($r->{$kolom})));
    }
}
