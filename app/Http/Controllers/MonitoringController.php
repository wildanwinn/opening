<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\OrderEditLog;
use App\Models\Sto;
use App\Models\BimaOrder;
use App\Models\User;
use App\Services\MonitoringRowMapper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class MonitoringController extends Controller
{
    /**
     * Daftar status order yang dipakai di sistem.
     * Dipakai buat dropdown filter & validasi.
     */
    private const DAFTAR_STATUS = [
        'STARTWORK' => 'Start Work',
        'CONTWORK'  => 'Continue Work',
        'COMPWORK'  => 'Complete',
        'WORKFAIL'  => 'Work Fail',
        'CANCLWORK' => 'Cancel Work',
    ];

    /** Kolom yang hanya bisa diisi selama masih kosong (arahan Mas Adi: kolom kosong di Excel bisa diedit). */
    private const KOLOM_BISA_DILENGKAPI = [
        'sto', 'scorderno', 'servicenum', 'latitude_ins', 'longitude_ins',
        'datecreated', 'tgl_manja', 'statusdate', 'errorcode_akhir', 'suberrorcode_akhir', 'odp_name', 'homepass_id',
        'long_inst_ut', 'lat_inst_ut', 'durasi_tti',
    ];

    /**
     * Tampilkan daftar monitoring dengan filter STO, status, dan pencarian.
     */
    public function index(Request $request)
    {
        $user  = Auth::user();
        $query = Monitoring::query()->with(['odp', 'bima', 'tsel', 'kpro']);

        // Teknisi hanya bisa lihat data miliknya / STO-nya sendiri
        $query->untukUser($user);

        if ($request->filled('sto')) {
            $query->where('monitorings.sto', $request->sto);
        }

        // Status diambil dari data BIMA; WO yang belum ada di BIMA memakai status miliknya sendiri.
        if ($request->filled('status')) {
            $status = $request->status;
            $query->where(function ($q) use ($status) {
                $q->whereHas('bima', fn ($b) => $b->where('status', $status))
                  ->orWhere(fn ($x) => $x->whereDoesntHave('bima')->where('monitorings.status', $status));
            });
        }

        if ($request->input('tsel') === 'ya') {
            $query->whereHas('tsel');
        } elseif ($request->input('tsel') === 'tidak') {
            $query->whereDoesntHave('tsel');
        }

        if ($request->input('odp') === 'cocok') {
            $query->whereNotNull('odp_id');
        } elseif ($request->input('odp') === 'ada') {
            $query->whereNotNull('odp_name')->where('odp_name', '!=', '');
        } elseif ($request->input('odp') === 'kosong') {
            $query->where(fn ($q) => $q->whereNull('odp_name')->orWhere('odp_name', ''));
        }

        // Pencarian bebas (WONUM / nama pelanggan / service number / SCORDERNO / ODP)
        if ($request->filled('cari')) {
            $cari = $request->cari;
            $query->where(function ($q) use ($cari) {
                $q->where('wonum', 'like', "%{$cari}%")
                  ->orWhere('customer_name', 'like', "%{$cari}%")
                  ->orWhere('servicenum', 'like', "%{$cari}%")
                  ->orWhere('scorderno', 'like', "%{$cari}%")
                  ->orWhere('odp_name', 'like', "%{$cari}%");
            });
        }

        $statusBima = BimaOrder::query()->whereNotNull('status')->distinct()->orderBy('status')->pluck('status')->all();
        $daftarStatus = [];
        foreach (array_unique(array_merge($statusBima, array_keys(self::DAFTAR_STATUS))) as $k) {
            $daftarStatus[$k] = self::DAFTAR_STATUS[$k] ?? $k;
        }

        $totalCocokOdp = Monitoring::query()->untukUser($user)->whereNotNull('odp_id')->count();

        return view('monitoring.index', [
            'dataMonitoring' => $query
                ->orderByRaw("CASE WHEN odp_id IS NOT NULL THEN 0 WHEN odp_name IS NOT NULL AND odp_name != '' THEN 1 ELSE 2 END")
                ->latest('datecreated')
                ->paginate(25)
                ->withQueryString(),
            'totalCocokOdp'  => $totalCocokOdp,
            'daftarSto'      => Sto::orderBy('wilayah')->orderBy('kode_sto')->get(),
            'daftarStatus'   => $daftarStatus,
            'bisaEdit'       => $this->bisaEdit($user),
            'bisaKirim'      => in_array($user->role, ['admin', 'hd'], true),
            'daftarTeknisi'  => in_array($user->role, ['admin', 'hd'], true)
                ? User::where('role', 'teknisi')->with('sto')->orderBy('nama')->get()
                : collect(),
        ]);
    }

    /**
     * Isi modal "Edit Data" (dimuat lewat AJAX).
     */
    public function show(Request $request, Monitoring $monitoring)
    {
        $this->pastikanBolehAkses($monitoring);

        if (! $request->ajax()) {
            return redirect()->route('monitoring.index', ['cari' => $monitoring->wonum]);
        }

        $monitoring->load('odp');

        return view('monitoring._edit', [
            'monitoring'   => $monitoring,
            'daftarStatus' => self::DAFTAR_STATUS,
            'bisaEdit'     => $this->bisaEdit(Auth::user()),
            'riwayat'      => $monitoring->logs()->with('user')->latest()->take(5)->get(),
        ]);
    }

    /**
     * Simpan hasil Edit Data.
     * - Kolom yang sudah terisi dari Excel dikunci (diabaikan walau dikirim).
     * - Kolom yang masih kosong boleh dilengkapi.
     * - Status & memo selalu boleh diubah.
     * Semua perubahan dicatat di order_edit_logs.
     */
    public function update(Request $request, Monitoring $monitoring)
    {
        $this->pastikanBolehAkses($monitoring);

        $request->validate([
            'status'          => ['nullable', Rule::in(array_keys(self::DAFTAR_STATUS))],
            'engineermemo'    => ['nullable', 'string', 'max:1000'],
            'sto'             => ['nullable', 'string', 'max:5'],
            'scorderno'       => ['nullable', 'string', 'max:50'],
            'servicenum'      => ['nullable', 'string', 'max:30'],
            'latitude_ins'    => ['nullable', 'numeric', 'between:-90,90'],
            'longitude_ins'   => ['nullable', 'numeric', 'between:-180,180'],
            'datecreated'     => ['nullable', 'date'],
            'tgl_manja'       => ['nullable', 'date'],
            'statusdate'      => ['nullable', 'date'],
            'errorcode_akhir' => ['nullable', 'string', 'max:50'],
            'suberrorcode_akhir' => ['nullable', 'string', 'max:50'],
            'long_inst_ut'    => ['nullable', 'numeric', 'between:-180,180'],
            'lat_inst_ut'     => ['nullable', 'numeric', 'between:-90,90'],
            'durasi_tti'      => ['nullable', 'numeric'],
            'odp_name'        => ['nullable', 'string', 'max:100'],
            'homepass_id'     => ['nullable', 'string', 'max:50'],
        ], [
            'status.in'          => 'Status tidak valid.',
            'latitude_ins.*'     => 'Latitude harus berupa angka antara -90 sampai 90.',
            'longitude_ins.*'    => 'Longitude harus berupa angka antara -180 sampai 180.',
            'datecreated.date'   => 'Format Tgl tidak valid.',
            'statusdate.date'    => 'Format Status Date tidak valid.',
        ]);

        $perubahan = [];

        foreach (self::KOLOM_BISA_DILENGKAPI as $kolom) {
            $baru = $request->input($kolom);

            // hanya kolom yang masih kosong di database yang boleh diisi
            if (blank($baru) || ! blank($monitoring->{$kolom})) {
                continue;
            }

            if ($kolom === 'sto' && ! Sto::where('kode_sto', $baru)->exists()) {
                throw ValidationException::withMessages(['sto' => 'Kode STO tidak dikenal.']);
            }

            $perubahan[$kolom] = in_array($kolom, ['datecreated', 'tgl_manja', 'statusdate'], true)
                ? Carbon::parse($baru)
                : trim((string) $baru);

            if ($kolom === 'scorderno') {
                $perubahan[$kolom] = MonitoringRowMapper::kodeScorderno($perubahan[$kolom]);
            }
        }

        $status = $request->input('status');
        if (filled($status) && $status !== $monitoring->status) {
            $perubahan['status'] = $status;
            if (! array_key_exists('statusdate', $perubahan)) {
                $perubahan['statusdate'] = now();
            }
        }

        if ($request->has('engineermemo')) {
            $memoBaru = (string) $request->input('engineermemo', '');
            if ($memoBaru !== (string) $monitoring->engineermemo) {
                $perubahan['engineermemo'] = $memoBaru === '' ? null : $memoBaru;
            }
        }

        if (! $perubahan) {
            return response()->json(['ok' => true, 'pesan' => 'Tidak ada perubahan.']);
        }

        $jumlah = count($perubahan);

        if (isset($perubahan['odp_name'])) {
            $perubahan['odp_name'] = MonitoringRowMapper::namaOdp($perubahan['odp_name']);
            $perubahan['odp_id']   = $perubahan['odp_name']
                ? \App\Models\Odp::where('nama_odp', $perubahan['odp_name'])->value('id')
                : null;
        }

        DB::transaction(function () use ($monitoring, $perubahan) {
            foreach ($perubahan as $kolom => $nilai) {
                OrderEditLog::create([
                    'monitoring_id' => $monitoring->id,
                    'kolom'         => $kolom,
                    'nilai_lama'    => $this->teksLog($monitoring->getRawOriginal($kolom)),
                    'nilai_baru'    => $this->teksLog($nilai),
                    'user_id'       => Auth::id(),
                ]);
            }

            $monitoring->update($perubahan);
        });

        return response()->json(['ok' => true, 'pesan' => "Data tersimpan ({$jumlah} perubahan)."]);
    }

    /**
     * Tombol "Send": beri tugas (order) ke teknisi. Teknisi lalu melihatnya di aplikasi mobile /tugas.
     * Boleh dikirim ulang ke teknisi lain selama tugas belum dimulai teknisi (tahap masih kosong).
     * Kompatibel dengan modal lama di Dashboard (field "teknisi" berformat KODE|NAMA).
     */
    public function assignTeknisi(Request $request, Monitoring $monitoring)
    {
        if ($monitoring->tahap !== null) {
            return back()->with('error', "Order {$monitoring->wonum} sudah dikerjakan teknisi, tidak bisa dikirim ulang.");
        }

        if ($request->filled('teknisi_id')) {
            $t = User::where('role', 'teknisi')->find($request->teknisi_id);
            if (! $t) {
                return back()->with('error', 'Teknisi tidak ditemukan.');
            }
            $kode = filled($t->kode_teknisi) ? $t->kode_teknisi : $t->nik;
            $nama = $t->nama;
        } else {
            $request->validate(['teknisi' => ['required', 'string']], ['teknisi.required' => 'Pilih teknisi dulu.']);
            [$kode, $nama] = array_pad(explode('|', $request->teknisi, 2), 2, null);
            if (blank($kode) || blank($nama)) {
                return back()->with('error', 'Data teknisi tidak valid.');
            }
        }

        $monitoring->update(['chief_code' => $kode, 'chief_name' => $nama]);

        return back()->with('sukses', "Order {$monitoring->wonum} dikirim ke {$nama}.");
    }

    // ---------------------------------------------------------------------

    private function bisaEdit($user): bool
    {
        return in_array($user->role, ['admin', 'hd', 'teknisi'], true);
    }

    private function teksLog($nilai): ?string
    {
        if ($nilai instanceof \DateTimeInterface) {
            return $nilai->format('d/m/Y H:i');
        }

        if (is_string($nilai) && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $nilai)) {
            return Carbon::parse($nilai)->format('d/m/Y H:i');
        }

        return blank($nilai) ? null : (string) $nilai;
    }

    /**
     * Teknisi hanya boleh membuka / mengubah order yang masuk cakupannya.
     */
    private function pastikanBolehAkses(Monitoring $monitoring): void
    {
        $boleh = Monitoring::untukUser(Auth::user())
            ->whereKey($monitoring->getKey())
            ->exists();

        abort_unless($boleh, 403, 'Anda tidak punya akses ke order ini.');
    }
}
