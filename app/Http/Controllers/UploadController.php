<?php

namespace App\Http\Controllers;

use App\Models\Monitoring;
use App\Models\Odp;
use App\Models\Sto;
use App\Models\UploadBatch;
use App\Services\MonitoringRowMapper;
use App\Services\Upload\PembacaBerkas;
use App\Services\Upload\PemeriksaHeader;
use App\Services\Upload\Pengimpor;
use App\Services\Upload\Skema;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Menu "Upload Data": 4 jenis upload yang dipisah (master, odp, kpro, bima).
 * Master, KPRO, dan BIMA memakai alur 3 langkah: baca + validasi header -> pratinjau -> simpan per potongan.
 * ODP diupload lewat OdpController::import (boleh banyak file / zip) dengan validasi header yang sama.
 */
class UploadController extends Controller
{
    private const UKURAN_POTONG = ['master' => 250, 'odp' => 500, 'kpro' => 500, 'bima' => 1000];

    /** Kolom pratinjau per jenis: kolom => judul. */
    private const PRATINJAU = [
        'odp' => [
            'nama_odp' => 'NAMA_ODP', 'sto' => 'STO', 'sto_desc' => 'STO_DESC', 'datel' => 'DATEL',
            'latitude' => 'LATITUDE', 'longitude' => 'LONGITUDE', 'avai' => 'AVAI', 'used' => 'USED', 'status' => 'STATUS',
        ],
        'kpro' => [
            'wonum' => 'WONUM', 'sto' => 'STO', 'crmordertype' => 'CRMORDERTYPE', 'customer_name' => 'CUSTOMER_NAME',
            'package_name' => 'PACKAGE_NAME', 'status' => 'STATUS', 'statusdate' => 'STATUSDATE',
            'odp_name' => 'ODP_NAME', 'chief_name' => 'CHIEF_NAME',
        ],
        'bima' => [
            'workorder' => 'Workorder', 'status' => 'Status', 'status_date' => 'Status Date', 'workzone' => 'Workzone',
            'crm_order_type' => 'CRM Order Type', 'customer_name' => 'Customer Name', 'owner_group' => 'Owner Group',
            'order_id_tsel' => 'Order ID TSEL', 'jenis_tsel' => 'Jenis',
        ],
    ];

    public function index(Request $request)
    {
        $jenis = $request->query('jenis', 'master');
        abort_unless(in_array($jenis, Skema::JENIS, true), 404);

        return view('monitoring.upload', [
            'jenis'   => $jenis,
            'definisi' => Skema::definisi($jenis),
            'semua'   => collect(Skema::JENIS)->mapWithKeys(fn ($j) => [$j => Skema::definisi($j)])->all(),
            'riwayat' => UploadBatch::with('user')->latest()->take(10)->get(),
        ]);
    }

    /** Langkah 1: baca file, validasi header ketat, simpan baris valid ke file sementara, kirim pratinjau. */
    public function preview(Request $request, string $jenis)
    {
        abort_unless(in_array($jenis, ['master', 'odp', 'kpro', 'bima'], true), 404);
        $def = Skema::definisi($jenis);

        if ($jenis === 'odp') {
            return $this->previewOdp($request, $def);
        }

        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls,csv', 'max:51200'],
        ], [
            'file.required'   => 'Pilih file dulu.',
            'file.extensions' => 'File harus berformat .xlsx, .xls, atau .csv.',
            'file.max'        => 'Ukuran file maksimal 50 MB.',
        ]);

        set_time_limit(300);
        $file = $request->file('file');

        try {
            [$judul, $baris] = (new PembacaBerkas)->baca($file->getRealPath(), $file->getClientOriginalName());
        } catch (\Throwable $e) {
            return response()->json(['message' => 'File tidak bisa dibaca: ' . Str::limit($e->getMessage(), 150)], 422);
        }

        $salah = PemeriksaHeader::periksa($jenis, $judul);
        if ($salah) {
            return response()->json([
                'message' => 'File ditolak, bukan format ' . $def['label'] . '.',
                'errors_header' => $salah,
            ], 422);
        }

        $mapper   = $jenis === 'master' ? new MonitoringRowMapper : null;
        $pengimpor = new Pengimpor;
        $path     = 'upload-' . $jenis . '/' . Str::uuid() . '.jsonl';
        Storage::disk('local')->makeDirectory(dirname($path));
        $fh = fopen(Storage::disk('local')->path($path), 'w');

        $sudahDilihat = [];
        $tanpaKunci = $dobel = $total = 0;
        $contoh = [];

        foreach ($baris as $b) {
            $total++;
            $kunci = Pengimpor::kunciBaris($jenis, $b);

            if ($kunci === null) {
                $tanpaKunci++;
                continue;
            }
            if (isset($sudahDilihat[$kunci])) {
                $dobel++; // kunci sama di dalam file: baris pertama yang dipakai
                continue;
            }
            $sudahDilihat[$kunci] = true;

            $d = match ($jenis) {
                'master' => $mapper->petakan($b),
                'kpro'   => $pengimpor->petakanKpro($b),
                'bima'   => $pengimpor->petakanBima($b),
            };
            if (! $d) {
                $tanpaKunci++;
                continue;
            }

            fwrite($fh, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

            if (count($contoh) < 15) {
                $contoh[] = $this->barisPratinjau($jenis, $d);
            }
        }
        fclose($fh);

        $valid = count($sudahDilihat);
        if ($valid === 0) {
            Storage::disk('local')->delete($path);

            return response()->json(['message' => 'Tidak ada baris yang punya ' . strtoupper($def['kunci']) . '. Cek isi file.'], 422);
        }

        $sudahAda = 0;
        $tabel = $jenis === 'master' ? 'monitorings' : $def['tabel'];
        foreach (array_chunk(array_map('strval', array_keys($sudahDilihat)), 1000) as $potong) {
            $sudahAda += DB::table($tabel)->whereIn($def['kunci'] === 'nama_odp' ? 'nama_odp' : $this->kolomKunci($jenis), $potong)->count();
        }

        $batch = UploadBatch::create([
            'nama_file'   => $file->getClientOriginalName(),
            'jenis'       => $jenis,
            'user_id'     => Auth::id(),
            'file_path'   => $path,
            'total_baris' => $valid,
            'status'      => 'pratinjau',
        ]);

        $kolom = $jenis === 'master' ? MonitoringRowMapper::KOLOM_PRATINJAU : self::PRATINJAU[$jenis];

        return response()->json([
            'batch_id'     => $batch->id,
            'jenis'        => $jenis,
            'label'        => $def['label'],
            'kunci'        => strtoupper($def['kunci']),
            'nama_file'    => $batch->nama_file,
            'baris_excel'  => $total,
            'valid'        => $valid,
            'tanpa_kunci'  => $tanpaKunci,
            'dobel'        => $dobel,
            'baru'         => $valid - $sudahAda,
            'update'       => $sudahAda,
            'kolom'        => $kolom,
            'kolom_hilang' => array_values(array_map('strtoupper', array_diff(Skema::slugs($jenis), $judul))),
            'contoh'       => $contoh,
            'url_proses'   => route('upload.proses', $batch),
            'url_batal'    => route('upload.batal', $batch),
        ]);
    }

    /** Langkah 3: simpan satu potongan; dipanggil berulang oleh browser sampai selesai. */
    public function proses(UploadBatch $batch)
    {
        if ($batch->status === 'selesai') {
            return response()->json($this->ringkas($batch));
        }

        if (! $batch->file_path || ! Storage::disk('local')->exists($batch->file_path)) {
            return response()->json(['message' => 'File sementara tidak ditemukan. Ulangi upload dari awal.'], 410);
        }

        set_time_limit(180);

        $jenis  = $batch->jenis;
        $ukuran = self::UKURAN_POTONG[$jenis] ?? 250;
        $berkas = new \SplFileObject(Storage::disk('local')->path($batch->file_path));
        $berkas->seek($batch->baris_diproses);

        $n = 0;
        $rows = [];
        while ($n < $ukuran && ($batch->baris_diproses + $n) < $batch->total_baris && $berkas->valid()) {
            $baris = trim((string) $berkas->current());
            $berkas->next();
            $n++;
            if ($baris !== '') {
                $rows[] = json_decode($baris, true);
            }
        }

        [$baru, $update, $gagal, $catatan] = $this->simpanPotong($jenis, $rows, $batch->id);

        $batch->baris_diproses += $n;
        $batch->baris_baru     += $baru;
        $batch->baris_update   += $update;
        $batch->baris_gagal    += $gagal;

        if ($catatan) {
            $lama = $batch->catatan ? explode("\n", $batch->catatan) : [];
            $batch->catatan = implode("\n", array_slice(array_merge($lama, $catatan), 0, 20));
        }

        if ($batch->baris_diproses >= $batch->total_baris) {
            $batch->status = 'selesai';
            Storage::disk('local')->delete($batch->file_path);
            $batch->file_path = null;

            if (in_array($jenis, ['master', 'odp', 'kpro', 'bima'], true)) {
                Odp::sinkronkanMonitoring(); // hubungkan order ke KPRO & ODP lewat odp_id
            }
        } else {
            $batch->status = 'proses';
        }
        $batch->save();

        return response()->json($this->ringkas($batch));
    }

    public function batal(UploadBatch $batch)
    {
        if ($batch->status === 'pratinjau') {
            if ($batch->file_path) {
                Storage::disk('local')->delete($batch->file_path);
            }
            $batch->delete();
        }

        return response()->json(['ok' => true]);
    }

    // ---------------------------------------------------------------------

    /** @return array{0:int,1:int,2:int,3:array} [baru, update, gagal, catatan] */
    private function simpanPotong(string $jenis, array $rows, int $batchId): array
    {
        if (! $rows) {
            return [0, 0, 0, []];
        }

        if ($jenis === 'master') {
            $mapper = new MonitoringRowMapper;
            $baru = $update = $gagal = 0;
            $catatan = [];
            foreach ($rows as $d) {
                try {
                    $mapper->simpan($d, $batchId) === 'baru' ? $baru++ : $update++;
                } catch (\Throwable $e) {
                    $gagal++;
                    $catatan[] = ($d['wonum'] ?? '?') . ': ' . Str::limit($e->getMessage(), 120);
                }
            }

            return [$baru, $update, $gagal, $catatan];
        }

        if ($jenis === 'odp') {
            return $this->simpanOdp($rows);
        }

        $pengimpor = new Pengimpor;

        try {
            [$baru, $update] = DB::transaction(fn () => $jenis === 'bima'
                ? $pengimpor->simpanBima($rows, $batchId)
                : $pengimpor->simpanKpro($rows, $batchId));

            return [$baru, $update, 0, []];
        } catch (\Throwable $e) {
            return [0, 0, count($rows), [Str::limit($e->getMessage(), 200)]];
        }
    }

    /** Langkah 1 khusus ODP: boleh banyak file / zip; tiap file divalidasi header-nya sendiri-sendiri. */
    private function previewOdp(Request $request, array $def)
    {
        $request->validate([
            'file'   => ['required', 'array', 'max:60'],
            'file.*' => ['file', 'extensions:xlsx,xls,csv,zip', 'max:51200'],
        ], [
            'file.required'     => 'Pilih file dulu.',
            'file.*.extensions' => 'File harus berformat .xlsx, .xls, .csv, atau .zip.',
            'file.*.max'        => 'Ukuran tiap file maksimal 50 MB.',
        ]);

        set_time_limit(300);

        $sumber = [];
        $sementara = [];
        $path = null;

        try {
            foreach ($request->file('file') as $f) {
                if (strtolower($f->getClientOriginalExtension()) !== 'zip') {
                    $sumber[] = [$f->getRealPath(), $f->getClientOriginalName()];
                    continue;
                }

                $zip = new \ZipArchive;
                if ($zip->open($f->getRealPath()) !== true) {
                    return response()->json(['message' => 'ZIP ' . $f->getClientOriginalName() . ' tidak bisa dibuka.'], 422);
                }
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $nama = $zip->getNameIndex($i);
                    if (str_ends_with($nama, '/') || str_contains($nama, '__MACOSX') || ! preg_match('/\.(xlsx|xls|csv)$/i', $nama)) {
                        continue;
                    }
                    $tmp = tempnam(sys_get_temp_dir(), 'odp');
                    file_put_contents($tmp, $zip->getFromIndex($i));
                    $sementara[] = $tmp;
                    $sumber[] = [$tmp, basename($nama)];
                }
                $zip->close();
            }

            if (! $sumber) {
                return response()->json(['message' => 'Tidak ada file Excel di dalam ZIP.'], 422);
            }

            $pembaca   = new PembacaBerkas;
            $pengimpor = new Pengimpor;
            $kodeSto   = Sto::pluck('kode_sto')->map(fn ($k) => mb_strtoupper($k))->flip();

            $path = 'upload-odp/' . Str::uuid() . '.jsonl';
            Storage::disk('local')->makeDirectory(dirname($path));
            $fh = fopen(Storage::disk('local')->path($path), 'w');

            $sudahDilihat = [];
            $ditolak = [];
            $stoBaru = [];
            $judulOk = [];
            $total = $tanpaKunci = $dobel = 0;
            $contoh = [];

            foreach ($sumber as [$berkas, $namaFile]) {
                try {
                    [$judul, $baris] = $pembaca->baca($berkas, $namaFile);
                } catch (\Throwable $e) {
                    $ditolak[] = $namaFile . ' — file tidak bisa dibaca.';
                    continue;
                }

                $salah = PemeriksaHeader::periksa('odp', $judul);
                if ($salah) {
                    $ditolak[] = $namaFile . ' — ' . implode(' ', $salah);
                    continue;
                }

                $pakaiOdpName = in_array('odp_name', $judul, true) && ! in_array('nama_odp', $judul, true);
                $judulOk = array_merge($judulOk, array_map(fn ($j) => ($pakaiOdpName && $j === 'odp_name') ? 'nama_odp' : $j, $judul));

                $barisFile = 0;
                foreach ($baris as $b) {
                    $barisFile++;
                    $total++;

                    if ($pakaiOdpName && array_key_exists('odp_name', $b)) {
                        $b['nama_odp'] = $b['odp_name']; // ODP_NAME di file sumber = NAMA_ODP
                        unset($b['odp_name']);
                    }

                    $kunci = Pengimpor::kunciBaris('odp', $b);
                    if ($kunci === null) {
                        $tanpaKunci++;
                        continue;
                    }

                    $kecil = mb_strtolower($kunci);
                    if (isset($sudahDilihat[$kecil])) {
                        $dobel++;
                        continue;
                    }

                    $d = $pengimpor->petakanOdp($b);
                    if (! $d) {
                        $tanpaKunci++;
                        continue;
                    }
                    $sudahDilihat[$kecil] = $d['nama_odp'];

                    if ($d['sto'] !== null && ! isset($kodeSto[$d['sto']]) && count($stoBaru) < 40) {
                        $stoBaru[$d['sto']] = true;
                    }

                    fwrite($fh, json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");

                    if (count($contoh) < 15) {
                        $contoh[] = $this->barisPratinjau('odp', $d);
                    }
                }

                if ($barisFile === 0) {
                    $ditolak[] = $namaFile . ' — file tidak punya baris data (hanya judul kolom atau kosong).';
                }
            }
            fclose($fh);

            $valid = count($sudahDilihat);
            if ($valid === 0) {
                Storage::disk('local')->delete($path);
                $path = null;

                return response()->json([
                    'message'       => $ditolak ? 'Semua file ditolak, bukan format ' . $def['label'] . '.' : 'Tidak ada baris yang punya NAMA_ODP. Cek isi file.',
                    'errors_header' => array_slice($ditolak, 0, 8),
                ], 422);
            }

            $sudahAda = 0;
            foreach (array_chunk(array_values($sudahDilihat), 1000) as $potong) {
                $sudahAda += DB::table('odps')->whereIn('nama_odp', $potong)->count();
            }

            $tambahan = array_map(fn ($t) => 'File ditolak: ' . Str::limit($t, 220), array_slice($ditolak, 0, 6));
            if ($stoBaru) {
                $tambahan[] = 'STO belum terdaftar di Area & STO: ' . implode(', ', array_keys($stoBaru)) . ' (ODP tetap disimpan).';
            }

            $batch = UploadBatch::create([
                'nama_file'   => Str::limit(count($sumber) === 1 ? $sumber[0][1] : count($sumber) . ' file ODP', 250, ''),
                'jenis'       => 'odp',
                'user_id'     => Auth::id(),
                'file_path'   => $path,
                'total_baris' => $valid,
                'status'      => 'pratinjau',
            ]);

            return response()->json([
                'batch_id'     => $batch->id,
                'jenis'        => 'odp',
                'label'        => $def['label'],
                'kunci'        => 'NAMA_ODP',
                'nama_file'    => $batch->nama_file,
                'baris_excel'  => $total,
                'valid'        => $valid,
                'tanpa_kunci'  => $tanpaKunci,
                'dobel'        => $dobel,
                'baru'         => $valid - $sudahAda,
                'update'       => $sudahAda,
                'kolom'        => self::PRATINJAU['odp'],
                'kolom_hilang' => array_values(array_map('strtoupper', array_diff(Skema::slugs('odp'), array_unique($judulOk)))),
                'contoh'       => $contoh,
                'tambahan'     => $tambahan,
                'url_proses'   => route('upload.proses', $batch),
                'url_batal'    => route('upload.batal', $batch),
            ]);
        } finally {
            foreach ($sementara as $tmp) {
                @unlink($tmp);
            }
        }
    }

    /** @return array{0:int,1:int,2:int,3:array} [baru, update, gagal, catatan] */
    private function simpanOdp(array $rows): array
    {
        try {
            $stamp = now()->format('Y-m-d H:i:s');
            $nama  = array_column($rows, 'nama_odp');
            $ada   = Odp::whereIn('nama_odp', $nama)->count();
            $isi   = array_map(fn ($d) => $d + ['created_at' => $stamp, 'updated_at' => $stamp], $rows);
            $ubah  = array_merge(array_diff((new Odp)->getFillable(), ['nama_odp']), ['updated_at']);

            DB::transaction(fn () => Odp::upsert($isi, ['nama_odp'], $ubah));

            return [count($rows) - $ada, $ada, 0, []];
        } catch (\Throwable $e) {
            return [0, 0, count($rows), [Str::limit($e->getMessage(), 200)]];
        }
    }

    private function kolomKunci(string $jenis): string
    {
        return match ($jenis) {
            'bima' => 'workorder',
            default => 'wonum',
        };
    }

    private function barisPratinjau(string $jenis, array $d): array
    {
        if ($jenis === 'bima') {
            $r = $d['b'] + ['order_id_tsel' => $d['tsel']['order_id_tsel'] ?? null, 'jenis_tsel' => $d['tsel'] ? 'TSEL' : 'Non TSEL'];
        } else {
            $r = $d;
        }

        $hasil = [];
        $kolom = $jenis === 'master' ? MonitoringRowMapper::KOLOM_PRATINJAU : self::PRATINJAU[$jenis];
        foreach (array_keys($kolom) as $k) {
            $v = $r[$k] ?? null;
            if ($v && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', (string) $v)) {
                $v = Carbon::parse($v)->format('d/m/Y H:i');
            }
            $hasil[$k] = $v === null ? null : Str::limit((string) $v, 80);
        }

        return $hasil;
    }

    private function ringkas(UploadBatch $batch): array
    {
        return [
            'diproses' => $batch->baris_diproses,
            'total'    => $batch->total_baris,
            'baru'     => $batch->baris_baru,
            'update'   => $batch->baris_update,
            'gagal'    => $batch->baris_gagal,
            'selesai'  => $batch->status === 'selesai',
            'catatan'  => $batch->catatan ? explode("\n", $batch->catatan) : [],
        ];
    }
}
