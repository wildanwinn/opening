<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Dashboard Harian: papan status teknisi + log aktivitas langsung.
 * Halaman memanggil data() berulang (polling) supaya tampil seperti realtime.
 */
class DashboardHarianController extends Controller
{
    public function index()
    {
        return view('dashboard_harian');
    }

    public function data(Request $request)
    {
        $user = Auth::user();

        // Order terbaru yang sudah punya teknisi dan tanggal status
        $orders = Monitoring::untukUser($user)
            ->whereNotNull('statusdate')
            ->whereNotNull('chief_name')
            ->orderByDesc('statusdate')
            ->limit(500)
            ->get(['id', 'wonum', 'sto', 'odp_name', 'chief_code', 'chief_name', 'status', 'statusdate']);

        $daftar = User::where('role', 'teknisi')->with('sto');
        if ($user->isTeknisi()) {
            $user->sto_id ? $daftar->where('sto_id', $user->sto_id) : $daftar->where('id', $user->id);
        }
        $daftar = $daftar->orderBy('nama')->get();

        $kolom = ['IDLE' => [], 'STARTWORK' => [], 'CONTWORK' => [], 'COMPWORK' => []];

        foreach ($daftar as $t) {
            $kode = strtolower(trim((string) $t->kode_teknisi));
            $nama = strtolower(trim((string) $t->nama));

            $terakhir = $orders->first(function ($o) use ($kode, $nama) {
                if ($kode !== '' && strtolower(trim((string) $o->chief_code)) === $kode) {
                    return true;
                }
                return strtolower(trim((string) $o->chief_name)) === $nama;
            });

            $status = 'IDLE';
            if ($terakhir) {
                if (in_array($terakhir->status, ['STARTWORK', 'CONTWORK'], true)) {
                    $status = $terakhir->status;
                } elseif ($terakhir->status === 'COMPWORK' && $terakhir->statusdate->isToday()) {
                    $status = 'COMPWORK';
                }
            }

            $kolom[$status][] = [
                'id'     => $t->id,
                'nama'   => $t->nama,
                'sto'    => $t->sto?->kode_sto,
                'wonum'  => $terakhir?->wonum,
                'status' => $status,
                'ts'     => $terakhir?->statusdate?->timestamp,
            ];
        }

        $log = $orders->take(20)->map(fn ($o) => [
            'key'    => $o->id . '-' . $o->status . '-' . $o->statusdate->timestamp,
            'nama'   => $o->chief_name,
            'status' => $o->status,
            'wonum'  => $o->wonum,
            'sto'    => $o->sto,
            'odp'    => $o->odp_name,
            'ts'     => $o->statusdate->timestamp,
        ])->values();

        return response()->json([
            'now'     => now()->timestamp,
            'kolom'   => $kolom,
            'log'     => $log,
            'ringkas' => [
                'teknisi' => $daftar->count(),
                'bekerja' => count($kolom['STARTWORK']) + count($kolom['CONTWORK']),
                'idle'    => count($kolom['IDLE']),
                'selesai' => Monitoring::untukUser($user)->where('status', 'COMPWORK')
                                ->whereDate('statusdate', today())->count(),
            ],
        ]);
    }
}