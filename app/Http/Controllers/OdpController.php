<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\Odp;
use App\Models\Sto;
use App\Services\OdpFileReader;
use App\Services\Upload\PemeriksaHeader;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Illuminate\Validation\Rule;

class OdpController extends Controller
{
    public function index(Request $request)
    {
        $query = Odp::query()->withCount('monitorings');

        if ($request->filled('cari')) {
            $query->where('nama_odp', 'like', '%' . $request->cari . '%');
        }

        if ($request->filled('sto')) {
            $query->where('sto', $request->sto);
        }

        return view('odp.index', [
            'daftarOdp' => $query->orderBy('nama_odp')->paginate(25)->withQueryString(),
            'daftarSto' => Sto::orderBy('kode_sto')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama_odp' => ['required', 'string', 'max:100', 'unique:odps,nama_odp'],
            'sto'      => ['nullable', 'string', 'max:5', 'exists:sto,kode_sto'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'latitude.*'        => 'Latitude harus angka antara -90 sampai 90.',
            'longitude.*'       => 'Longitude harus angka antara -180 sampai 180.',
            'nama_odp.required' => 'Nama ODP wajib diisi.',
            'nama_odp.unique'   => 'Nama ODP sudah ada.',
            'sto.exists'        => 'Kode STO tidak dikenal.',
        ]);

        $data['nama_odp'] = trim($data['nama_odp']);
        $data['sto'] = blank($data['sto'] ?? null) ? null : $data['sto'];
        Odp::create($data);

        return back()->with('sukses', "ODP {$data['nama_odp']} ditambahkan.");
    }

    public function update(Request $request, Odp $odp)
    {
        $data = $request->validate([
            'nama_odp' => ['required', 'string', 'max:100', Rule::unique('odps', 'nama_odp')->ignore($odp->id)],
            'sto'      => ['nullable', 'string', 'max:5', 'exists:sto,kode_sto'],
            'latitude'  => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ], [
            'latitude.*'        => 'Latitude harus angka antara -90 sampai 90.',
            'longitude.*'       => 'Longitude harus angka antara -180 sampai 180.',
            'nama_odp.required' => 'Nama ODP wajib diisi.',
            'nama_odp.unique'   => 'Nama ODP sudah ada.',
            'sto.exists'        => 'Kode STO tidak dikenal.',
        ]);

        $data['nama_odp'] = trim($data['nama_odp']);
        $namaLama = $odp->nama_odp;
        $odp->update($data);

        // relasi lewat nama: nama ODP di data order ikut diperbarui
        if ($namaLama !== $data['nama_odp']) {
            Monitoring::where('odp_name', $namaLama)->update(['odp_name' => $data['nama_odp']]);
        }

        return back()->with('sukses', "ODP {$data['nama_odp']} diperbarui.");
    }

    public function destroy(Odp $odp)
    {
        $nama = $odp->nama_odp;
        $odp->delete(); // kolom odp_name di data order tetap, hanya tidak punya pasangan ODP lagi

        return back()->with('sukses', "ODP {$nama} dihapus.");
    }

    /**
     * Upload file ODP (boleh banyak file sekaligus, atau satu .zip berisi file-file ODP).
     * Judul kolom mengikuti header master (lihat Odp::HEADER).
     * ODP yang namanya sudah ada diperbarui, yang belum ada ditambah,
     * order dipasangkan otomatis lewat monitorings.odp_name = odps.nama_odp.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file'   => ['required', 'array', 'max:200'],
            'file.*' => ['file', 'extensions:xlsx,xls,csv,zip', 'max:51200'],
        ], [
            'file.required'  => 'Pilih file ODP dulu.',
            'file.*.extensions' => 'File harus berformat .xls, .xlsx, .csv, atau .zip.',
            'file.*.max'     => 'Ukuran tiap file maksimal 50 MB.',
        ]);

        set_time_limit(600);

        $reader = new OdpFileReader;
        $sumber = [];   // [path, nama]
        $sementara = [];

        foreach ($request->file('file') as $f) {
            if (strtolower($f->getClientOriginalExtension()) !== 'zip') {
                $sumber[] = [$f->getRealPath(), $f->getClientOriginalName()];
                continue;
            }

            $zip = new \ZipArchive;
            if ($zip->open($f->getRealPath()) !== true) {
                return back()->with('error', "ZIP {$f->getClientOriginalName()} tidak bisa dibuka.");
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $nama = $zip->getNameIndex($i);
                if (str_ends_with($nama, '/') || str_contains($nama, '__MACOSX') || ! preg_match('/\.(xlsx|xls|csv)$/i', $nama)) {
                    continue;
                }
                $tmp = tempnam(sys_get_temp_dir(), 'odp');
                file_put_contents($tmp, $zip->getFromIndex($i)); // isi dibaca sebagai data, nama dari zip tidak dipakai sebagai path
                $sementara[] = $tmp;
                $sumber[] = [$tmp, basename($nama)];
            }
            $zip->close();
        }

        $valid = [];
        $tanpaNama = $koordinatSalah = $fileGagal = 0;
        $ditolak = [];
        $stoBaru = [];
        $kodeSto = Sto::pluck('kode_sto')->map(fn ($k) => mb_strtoupper($k))->flip();
        $kolomTeks = array_diff((new Odp)->getFillable(), Odp::ANGKA, ['latitude', 'longitude', 'update_date']);

        try {
            foreach ($sumber as [$path, $nama]) {
                try {
                    $lembar = $reader->baca($path, $nama);
                } catch (\Throwable $e) {
                    $fileGagal++;
                    continue;
                }

                $lembar = OdpFileReader::samakanHeaderOdp($lembar); // ODP_NAME di file sumber = NAMA_ODP

                if (! $lembar) {
                    $ditolak[] = $nama . ' — file tidak punya baris data (hanya judul kolom atau kosong). Cek isi file di Excel.';
                    continue;
                }

                // Validasi ketat: kunci NAMA_ODP wajib, tidak boleh ada header asing / salah jenis (BIMA, KPRO, Master).
                $salah = PemeriksaHeader::periksa('odp', $lembar ? array_keys($lembar[0]) : []);
                if ($salah) {
                    $ditolak[] = $nama . ' — ' . implode(' ', $salah);
                    continue;
                }

                foreach ($lembar as $baris) {
                    $d = array_fill_keys((new Odp)->getFillable(), null);
                    foreach ($baris as $slug => $nilai) {
                        if (($kolom = Odp::kolomDari((string) $slug)) && $nilai !== null && trim((string) $nilai) !== '') {
                            $d[$kolom] = trim((string) $nilai);
                        }
                    }

                    if (blank($d['nama_odp'])) {
                        $tanpaNama++;
                        continue;
                    }

                    foreach ($kolomTeks as $k) {
                        if ($d[$k] !== null) {
                            $d[$k] = mb_substr($d[$k], 0, $k === 'odp_info' ? 65000 : 100);
                        }
                    }

                    $d['sto'] = $d['sto'] === null ? null : mb_strtoupper(mb_substr($d['sto'], 0, 5));
                    if ($d['sto'] !== null && ! isset($kodeSto[$d['sto']])) {
                        $stoBaru[$d['sto']] = true;
                    }

                    foreach (['latitude' => 90, 'longitude' => 180] as $k => $batas) {
                        $nilai = $d[$k] !== null ? str_replace(',', '.', $d[$k]) : null;
                        if ($nilai !== null && (! is_numeric($nilai) || abs((float) $nilai) > $batas)) {
                            $koordinatSalah++;
                            $nilai = null;
                        }
                        $d[$k] = $nilai;
                    }

                    foreach (Odp::ANGKA as $k) {
                        $d[$k] = ($d[$k] !== null && is_numeric($d[$k])) ? (int) $d[$k] : null;
                    }

                    if ($d['update_date'] !== null) {
                        try {
                            $d['update_date'] = Carbon::parse($d['update_date'])->format('Y-m-d H:i:s');
                        } catch (\Throwable $e) {
                            $d['update_date'] = null;
                        }
                    }

                    $valid[mb_strtolower($d['nama_odp'])] = $d; // nama ODP sama: baris terakhir dipakai
                }
            }
        } finally {
            foreach ($sementara as $tmp) {
                @unlink($tmp);
            }
        }

        if (! $valid) {
            $pesan = $ditolak
                ? 'Semua file ditolak. ' . implode(' | ', array_slice($ditolak, 0, 3))
                : 'Tidak ada baris ODP yang valid. Pastikan ada kolom NAMA_ODP. Header yang dipakai: ' . implode(', ', Odp::HEADER) . '.';

            return back()->with('error', $pesan);
        }

        $baru = $update = 0;
        $stamp = now()->format('Y-m-d H:i:s');

        DB::transaction(function () use ($valid, &$baru, &$update, $stamp) {
            foreach (array_chunk($valid, 500) as $potong) {
                $nama = array_column($potong, 'nama_odp');
                $update += Odp::whereIn('nama_odp', $nama)->count();

                $isi = array_map(fn ($d) => $d + ['created_at' => $stamp, 'updated_at' => $stamp], $potong);
                $kolomUpdate = array_merge(array_diff((new Odp)->getFillable(), ['nama_odp']), ['updated_at']);
                Odp::upsert($isi, ['nama_odp'], $kolomUpdate);
            }
            $baru = count($valid) - $update;
        });

        $dariKpro = Odp::sinkronkanMonitoring(); // isi nama ODP kosong dari KPRO + monitorings.odp_id

        // Pairing otomatis lewat nama: order yang odp_name-nya sama dengan nama_odp.
        $terpasang = DB::table('monitorings')->join('odps', 'odps.nama_odp', '=', 'monitorings.odp_name')->count();

        $pesan = "Upload ODP selesai: {$baru} baru, {$update} diperbarui, {$terpasang} order terhubung ke ODP.";
        if ($dariKpro) {
            $pesan .= " {$dariKpro} order yang ODP-nya kosong di Master diisi dari data KPRO.";
        }
        if ($tanpaNama) {
            $pesan .= " {$tanpaNama} baris dilewati (nama ODP kosong).";
        }
        if ($fileGagal) {
            $pesan .= " {$fileGagal} file tidak bisa dibaca.";
        }
        if ($ditolak) {
            $pesan .= ' DITOLAK: ' . implode(' | ', array_slice($ditolak, 0, 3));
        }
        if ($koordinatSalah) {
            $pesan .= " {$koordinatSalah} koordinat tidak valid dikosongkan.";
        }
        if ($stoBaru) {
            $pesan .= ' STO belum terdaftar di Area & STO: ' . implode(', ', array_keys($stoBaru)) . '.';
        }

        return back()->with('sukses', $pesan);
    }

    /** Template Excel ODP: judul kolom mengikuti tabel master ODP. */
    public function template()
    {
        $sheet = new Spreadsheet;
        $ws = $sheet->getActiveSheet();
        $ws->fromArray(Odp::HEADER, null, 'A1');
        $ws->fromArray([
            'Regional 4', 'PEKALONGAN', 'BATANG', 'BTG', 'BATANG', '7883968608', 'FN/D08/104.01', 'ODP-BTG-FN/104',
            '-6.977208410069741', '109.7971839693193', null, null, 8, 0, 0, 0, 8, null, '2026-09-15 12:11:23', '20260930', 'BLACK',
        ], null, 'A2', true);
        $ws->getStyle('A1:U1')->getFont()->setBold(true);
        foreach (range('A', 'U') as $kol) {
            $ws->getColumnDimension($kol)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($sheet) {
            (new Xlsx($sheet))->save('php://output');
        }, 'template-odp.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
