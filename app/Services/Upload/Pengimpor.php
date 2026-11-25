<?php

namespace App\Services\Upload;

use App\Models\Odp;
use App\Services\MonitoringRowMapper;
use App\Services\OdpFileReader;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Memetakan baris file ke kolom tabel dan menyimpannya per potongan untuk jenis kpro & bima.
 * (Jenis master memakai MonitoringRowMapper, jenis odp memakai OdpController.)
 */
class Pengimpor
{
    /** Batas panjang kolom string bima_orders (sisanya 150). Kolom teks panjang tanpa batas. */
    private const BATAS_BIMA = [
        'workorder' => 20, 'status' => 20, 'workzone' => 10, 'crm_order_type' => 30, 'owner_group' => 100,
        'region_site_id' => 20, 'measurement_result' => 30, 'wo_class' => 30, 'product_type' => 30,
        'oss_order_id' => 60, 'service_no' => 60, 'contact_number' => 60, 'booking_id' => 60,
        'area_tif' => 40, 'district_tif' => 40, 'regional_tif' => 40,
        'address' => null, 'measurement' => null, 'no_kontrak' => null,
    ];

    /** Kunci baris untuk jenis ini, atau null kalau kosong. */
    public static function kunciBaris(string $jenis, array $baris): ?string
    {
        $v = $baris[Skema::definisi($jenis)['kunci']] ?? null;
        $v = $v === null ? '' : trim((string) $v);

        return $v === '' ? null : $v;
    }

    // ------------------------------------------------------------------ ODP

    /** Satu baris file ODP -> kolom tabel odps (baris tanpa nama ODP = null). */
    public function petakanOdp(array $baris): ?array
    {
        $fillable  = (new Odp)->getFillable();
        $kolomTeks = array_diff($fillable, Odp::ANGKA, ['latitude', 'longitude', 'update_date']);
        $d = array_fill_keys($fillable, null);

        foreach ($baris as $slug => $nilai) {
            if (($kolom = Odp::kolomDari((string) $slug)) && $nilai !== null && trim((string) $nilai) !== '') {
                $d[$kolom] = trim((string) $nilai);
            }
        }

        if (blank($d['nama_odp'])) {
            return null;
        }

        foreach ($kolomTeks as $k) {
            if ($d[$k] !== null) {
                $d[$k] = mb_substr($d[$k], 0, $k === 'odp_info' ? 65000 : 100);
            }
        }

        $d['sto'] = $d['sto'] === null ? null : mb_strtoupper(mb_substr($d['sto'], 0, 5));

        foreach (['latitude' => 90, 'longitude' => 180] as $k => $batas) {
            $nilai = $d[$k] !== null ? str_replace(',', '.', $d[$k]) : null;
            $d[$k] = ($nilai !== null && is_numeric($nilai) && abs((float) $nilai) <= $batas) ? $nilai : null;
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

        return $d;
    }

    // ------------------------------------------------------------------ BIMA

    /** @return array{b: array, tsel: ?array} */
    public function petakanBima(array $row): array
    {
        $b = [];
        foreach (Skema::KOLOM_BIMA as $label => $kolom) {
            $v = $row[OdpFileReader::slug($label)] ?? null;
            $b[$kolom] = in_array($kolom, Skema::TANGGAL_BIMA, true)
                ? $this->tanggal($v)
                : $this->teks($v, array_key_exists($kolom, self::BATAS_BIMA) ? self::BATAS_BIMA[$kolom] : 150);
        }

        // SC Order No: yang disimpan hanya kodenya (AOi..., MOk..., 801Mg..., 1-xxxx), bukan string panjang.
        $b['sc_order_no'] = $this->teks(MonitoringRowMapper::kodeScorderno($row[OdpFileReader::slug('SC Order No/Track ID/CSRM No')] ?? null), 60);

        $order = $this->teks($row['order_id_tsel'] ?? null, 60);

        return [
            'b'    => $b,
            'tsel' => $order === null ? null : ['order_id_tsel' => $order, 'channel_id_tsel' => $this->teks($row['channel_id_tsel'] ?? null, 60)],
        ];
    }

    /**
     * Simpan satu potongan baris BIMA (hasil petakanBima).
     * Efek: bima_orders di-upsert, tsel_orders hanya untuk WO ber-Order ID TSEL,
     * dan WO yang belum ada di monitorings ditambahkan supaya semua WO BIMA muncul di Data Monitoring.
     * @return array{0:int,1:int} [baru, update]
     */
    public function simpanBima(array $potong, int $batchId): array
    {
        $now   = now()->format('Y-m-d H:i:s');
        $kunci = array_map(fn ($p) => $p['b']['workorder'], $potong);
        $ada   = DB::table('bima_orders')->whereIn('workorder', $kunci)->pluck('workorder')->flip();

        $isi = array_map(fn ($p) => $p['b'] + ['upload_batch_id' => $batchId, 'created_at' => $now, 'updated_at' => $now], $potong);
        $ubah = array_merge(array_diff(array_keys($isi[0]), ['workorder', 'created_at']));
        DB::table('bima_orders')->upsert($isi, ['workorder'], $ubah);

        $tsel = [];
        $nonTsel = [];
        foreach ($potong as $p) {
            if ($p['tsel']) {
                $tsel[] = ['workorder' => $p['b']['workorder']] + $p['tsel'] + ['created_at' => $now, 'updated_at' => $now];
            } else {
                $nonTsel[] = $p['b']['workorder'];
            }
        }
        if ($tsel) {
            DB::table('tsel_orders')->upsert($tsel, ['workorder'], ['order_id_tsel', 'channel_id_tsel', 'updated_at']);
        }
        if ($nonTsel) {
            DB::table('tsel_orders')->whereIn('workorder', $nonTsel)->delete();
        }

        $this->tambahkanKeMonitoring($potong, $batchId, $now);

        $baru = count(array_filter($kunci, fn ($k) => ! isset($ada[$k])));

        return [$baru, count($kunci) - $baru];
    }

    private function tambahkanKeMonitoring(array $potong, int $batchId, string $now): void
    {
        $kunci = array_map(fn ($p) => $p['b']['workorder'], $potong);
        $sudah = DB::table('monitorings')->whereIn('wonum', $kunci)->pluck('wonum')->flip();
        $baru  = [];

        foreach ($potong as $p) {
            $b = $p['b'];
            if (isset($sudah[$b['workorder']])) {
                continue;
            }
            $baru[] = [
                'wonum'            => $b['workorder'],
                'scorderno'        => $this->teks(MonitoringRowMapper::kodeScorderno($b['sc_order_no']), 50),
                'crmordertype'     => $this->teks($b['crm_order_type'], 20),
                'sto'              => $this->teks($b['workzone'], 5),
                'package_name'     => $this->teks($b['product_name'], 150),
                'servicenum'       => $this->teks($b['service_no'], 30),
                'customer_name'    => $this->teks($b['customer_name'], 150),
                'customer_address' => $b['address'],
                'no_hp'            => $this->teks($b['contact_number'], 20),
                'datecreated'      => $b['date_created'],
                'status'           => $b['status'],
                'statusdate'       => $b['status_date'],
                'upload_batch_id'  => $batchId,
                'created_at'       => $now,
                'updated_at'       => $now,
            ];
        }

        if ($baru) {
            DB::table('monitorings')->insertOrIgnore($baru);
        }

        // WO yang dulu dibuat dari BIMA (atau scorderno-nya masih kosong): samakan dengan kode terbaru.
        $kode = [];
        foreach ($potong as $p) {
            $kode[$p['b']['workorder']] = $this->teks(MonitoringRowMapper::kodeScorderno($p['b']['sc_order_no']), 50);
        }
        $idBatchBima = DB::table('upload_batches')->where('jenis', 'bima')->pluck('id')->flip();
        $ada = DB::table('monitorings')->whereIn('wonum', array_keys($kode))->get(['id', 'wonum', 'scorderno', 'upload_batch_id']);
        foreach ($ada as $m) {
            $baruKode = $kode[$m->wonum] ?? null;
            $boleh = $m->scorderno === null || isset($idBatchBima[$m->upload_batch_id]);
            if ($boleh && $baruKode !== null && $baruKode !== $m->scorderno) {
                DB::table('monitorings')->where('id', $m->id)->update(['scorderno' => $baruKode, 'updated_at' => $now]);
            }
        }
    }

    // ------------------------------------------------------------------ KPRO

    public function petakanKpro(array $row): array
    {
        $d = [];
        foreach ($row as $slug => $v) {
            if ($slug === 'no' || $slug === '') {
                continue;
            }
            if (in_array($slug, Skema::KPRO_TANGGAL, true)) {
                $d[$slug] = $this->tanggal($v);
            } elseif (in_array($slug, Skema::KPRO_ANGKA, true)) {
                $v = $v === null ? null : str_replace(',', '.', (string) $v);
                $d[$slug] = is_numeric($v) ? (float) $v : null;
            } elseif (in_array($slug, Skema::KPRO_TEKS, true)) {
                $d[$slug] = $this->teks($v, null);
            } else {
                $d[$slug] = $this->teks($v, $slug === 'wonum' ? 20 : 150);
            }
        }

        return $d;
    }

    /** @return array{0:int,1:int} [baru, update] */
    public function simpanKpro(array $potong, int $batchId): array
    {
        $now   = now()->format('Y-m-d H:i:s');
        $kunci = array_column($potong, 'wonum');
        $ada   = DB::table('kpro_orders')->whereIn('wonum', $kunci)->pluck('wonum')->flip();

        $isi  = array_map(fn ($d) => $d + ['upload_batch_id' => $batchId, 'created_at' => $now, 'updated_at' => $now], $potong);
        $ubah = array_diff(array_keys($isi[0]), ['wonum', 'created_at']);
        DB::table('kpro_orders')->upsert($isi, ['wonum'], array_values($ubah));

        $baru = count(array_filter($kunci, fn ($k) => ! isset($ada[$k])));

        return [$baru, count($kunci) - $baru];
    }

    // ------------------------------------------------------------------ util

    private function teks($v, ?int $maks): ?string
    {
        if ($v === null) {
            return null;
        }
        $v = trim((string) $v);
        if ($v === '' ) {
            return null;
        }

        return $maks ? mb_substr($v, 0, $maks) : $v;
    }

    private function tanggal($v): ?string
    {
        $v = $this->teks($v, null);
        if ($v === null) {
            return null;
        }
        try {
            return Carbon::parse($v)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}
