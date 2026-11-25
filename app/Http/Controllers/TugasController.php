<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\Monitoring;
use App\Models\PekerjaanFoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Tampilan mobile teknisi. Alur: Mulai -> Pre (material + foto) -> Jalan -> Selesai.
 * Tiap tahap memperbarui status & statusdate order, jadi Dashboard Harian ikut bergerak.
 */
class TugasController extends Controller
{
    /** Minimal foto bukti material pada tahap Pre. */
    public const MIN_FOTO_PRE = 2;

    private function ambil(int|string $id): Monitoring
    {
        return Monitoring::untukUser(Auth::user())->with('odp')->findOrFail($id);
    }

    private function catat(Monitoring $m, string $status, string $tahap): void
    {
        $m->update(['status' => $status, 'tahap' => $tahap, 'statusdate' => now()]);
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q'));

        $tugas = Monitoring::untukUser(Auth::user())
            ->where(fn ($w) => $w->whereNull('tahap')->orWhere('tahap', '!=', 'selesai'))
            ->where(fn ($w) => $w->whereNull('status')->orWhere('status', '!=', 'COMPWORK'))
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x
                ->where('odp_name', 'like', "%$q%")->orWhere('wonum', 'like', "%$q%")
                ->orWhere('customer_name', 'like', "%$q%")))
            ->orderByRaw("CASE tahap WHEN 'jalan' THEN 0 WHEN 'pre' THEN 1 ELSE 2 END")
            ->orderByDesc('datecreated')
            ->limit(30)
            ->get();

        $aktif = $tugas->first(fn ($t) => in_array($t->tahap, ['pre', 'jalan'], true));

        $dasar = Monitoring::untukUser(Auth::user());
        $stat  = [
            'belum'   => (clone $dasar)->whereNull('tahap')->where(fn ($w) => $w->whereNull('status')->orWhere('status', '!=', 'COMPWORK'))->count(),
            'jalan'   => (clone $dasar)->whereIn('tahap', ['pre', 'jalan'])->count(),
            'selesai' => (clone $dasar)->where('status', 'COMPWORK')->whereDate('statusdate', today())->count(),
        ];

        return view('tugas.index', compact('tugas', 'aktif', 'q', 'stat'));
    }

    public function mulai(Monitoring $monitoring)
    {
        $m = $this->ambil($monitoring->id);
        if ($m->tahap === null) {
            $this->catat($m, 'STARTWORK', 'pre');
        }

        return redirect()->route('tugas.pre', $m);
    }

    public function pre(Monitoring $monitoring)
    {
        $m = $this->ambil($monitoring->id);
        if ($m->tahap === null) {
            return redirect()->route('tugas.index')->with('error', 'Tekan "Mulai" dulu pada tugas ini.');
        }

        $materials = Material::orderBy('id')->get();
        $qty       = $m->pekerjaanMaterials()->pluck('qty', 'material_id');
        $fotos     = $m->fotos()->where('jenis', 'material')->get();

        return view('tugas.pre', compact('m', 'materials', 'qty', 'fotos') + ['minFoto' => self::MIN_FOTO_PRE]);
    }

    public function simpanPre(Request $request, Monitoring $monitoring)
    {
        $m = $this->ambil($monitoring->id);

        $request->validate([
            'qty'      => 'nullable|array',
            'qty.*'    => 'nullable|integer|min:0|max:100000',
            'foto'     => 'nullable|array|max:10',
            'foto.*'   => 'image|max:8192',
        ], [
            'foto.*.image' => 'File foto harus berupa gambar (jpg/png).',
            'foto.*.max'   => 'Ukuran tiap foto maksimal 8 MB.',
        ]);

        foreach ((array) $request->input('qty', []) as $materialId => $jumlah) {
            $jumlah = (int) $jumlah;
            if (! Material::whereKey($materialId)->exists()) {
                continue;
            }
            if ($jumlah > 0) {
                $m->pekerjaanMaterials()->updateOrCreate(['material_id' => $materialId], ['qty' => $jumlah]);
            } else {
                $m->pekerjaanMaterials()->where('material_id', $materialId)->delete();
            }
        }

        foreach ((array) $request->file('foto', []) as $file) {
            $m->fotos()->create([
                'user_id' => Auth::id(),
                'jenis'   => 'material',
                'path'    => $file->store("pekerjaan/{$m->id}"),
            ]);
        }

        if ($m->fotos()->where('jenis', 'material')->count() < self::MIN_FOTO_PRE) {
            return back()->withInput()->with('error', 'Upload minimal ' . self::MIN_FOTO_PRE . ' foto bukti material dulu.');
        }

        $this->catat($m, 'CONTWORK', 'jalan');

        return redirect()->route('tugas.lokasi', $m)->with('sukses', 'Material tersimpan. Selamat jalan!');
    }

    public function lokasi(Monitoring $monitoring)
    {
        $m = $this->ambil($monitoring->id);

        return view('tugas.lokasi', ['m' => $m, 'odp' => $m->odp]);
    }

    public function selesai(Monitoring $monitoring)
    {
        $m = $this->ambil($monitoring->id);
        if ($m->tahap !== 'jalan') {
            return redirect()->route($m->tahap === 'pre' ? 'tugas.pre' : 'tugas.index', $m->tahap === 'pre' ? [$m] : [])
                ->with('error', 'Selesaikan tahap sebelumnya dulu.');
        }

        $ringkas = $m->pekerjaanMaterials()->with('material')->get();
        $fotos   = $m->fotos()->where('jenis', 'sesudah')->get();

        return view('tugas.selesai', compact('m', 'ringkas', 'fotos'));
    }

    public function simpanSelesai(Request $request, Monitoring $monitoring)
    {
        $m = $this->ambil($monitoring->id);

        $request->validate([
            'catatan'  => 'nullable|string|max:1000',
            'foto'     => 'nullable|array|max:10',
            'foto.*'   => 'image|max:8192',
        ], [
            'foto.*.image' => 'File foto harus berupa gambar (jpg/png).',
            'foto.*.max'   => 'Ukuran tiap foto maksimal 8 MB.',
        ]);

        foreach ((array) $request->file('foto', []) as $file) {
            $m->fotos()->create([
                'user_id' => Auth::id(),
                'jenis'   => 'sesudah',
                'path'    => $file->store("pekerjaan/{$m->id}"),
            ]);
        }

        if (! $m->fotos()->where('jenis', 'sesudah')->exists()) {
            return back()->withInput()->with('error', 'Upload foto ODP sesudah dulu.');
        }

        $m->catatan_teknisi = $request->input('catatan');
        $this->catat($m, 'COMPWORK', 'selesai');

        return redirect()->route('tugas.index')->with('sukses', "Tugas {$m->wonum} selesai. Kerja bagus!");
    }

    /** Tampilkan foto (disimpan privat, jadi lewat route ber-login). */
    public function foto(PekerjaanFoto $foto)
    {
        Monitoring::untukUser(Auth::user())->findOrFail($foto->monitoring_id);

        abort_unless(Storage::exists($foto->path), 404);

        return Storage::response($foto->path);
    }
}
