<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master ODP. Kuncinya nama_odp (unik), dikelompokkan per STO,
 * dan menyimpan koordinat ODP. Order (monitorings) dipasangkan lewat monitorings.odp_name = odps.nama_odp.
 */
class Odp extends Model
{
    /** Judul kolom file ODP = header tabel master (urutan sama dengan file sumber). */
    public const HEADER = [
        'REGIONAL', 'WITEL', 'DATEL', 'STO', 'STO_DESC', 'NOSS_ID', 'ODP_INDEX', 'NAMA_ODP', 'LATITUDE', 'LONGITUDE',
        'CLUSNAME', 'CLUSTERSATATUS', 'AVAI', 'USED', 'RSV', 'RSK', 'IS_TOTAL', 'ODP_INFO', 'UPDATE_DATE', 'PERIODE', 'STATUS',
    ];

    /** kolom database => judul Excel yang diterima selain nama kolomnya sendiri (format slug) */
    public const ALIAS = [
        'nama_odp'      => ['nama_odp'],
        'latitude'      => ['lat', 'latitude_odp'],
        'longitude'     => ['long', 'lng', 'longitude_odp'],
        'clusterstatus' => ['clusterstatus', 'clustersatatus'], // judul di file sumber salah ketik
    ];

    public const ANGKA = ['avai', 'used', 'rsv', 'rsk', 'is_total'];

    protected $casts = ['update_date' => 'datetime'];

    protected $fillable = [
        'regional', 'witel', 'datel', 'sto', 'sto_desc', 'noss_id', 'odp_index', 'nama_odp', 'latitude', 'longitude',
        'clusname', 'clusterstatus', 'avai', 'used', 'rsv', 'rsk', 'is_total', 'odp_info', 'update_date', 'periode', 'status',
    ];

    /** Nama kolom database dari judul kolom Excel (format slug), atau null kalau tidak dikenal. */
    public static function kolomDari(string $slug): ?string
    {
        foreach (self::ALIAS as $kolom => $daftar) {
            if (in_array($slug, $daftar, true)) {
                return $kolom;
            }
        }

        return in_array($slug, (new self)->fillable, true) ? $slug : null;
    }

    public function monitorings()
    {
        return $this->hasMany(Monitoring::class, 'odp_name', 'nama_odp');
    }

    /**
     * Lengkapi data ODP order lewat tiga langkah (aman dijalankan berulang):
     *  1) isiDariKpro()    : nama ODP kosong di order diisi dari KPRO (WONUM sama).
     *  2) isiDariScOrder() : nama ODP kosong diisi dari WO lain di SC Order yang sama (hanya kalau ODP-nya tunggal/tidak ambigu).
     *  3) hubungkanOdpId() : isi monitorings.odp_id dari kecocokan nama ODP.
     * Mengembalikan jumlah order yang nama ODP-nya terisi di langkah 1 + 2.
     * Nama ODP yang sudah terisi dari Master tidak pernah ditimpa.
     */
    public static function sinkronkanMonitoring(): int
    {
        $isi = self::isiDariKpro() + self::isiDariScOrder();
        self::hubungkanOdpId();

        return $isi;
    }

    public static function isiDariKpro(): int
    {
        $jumlah = 0;

        if (! Schema::hasTable('kpro_orders')) {
            return 0;
        }

        // Pemetaan kolom monitorings => kolom kpro_orders untuk melengkapi data order yang masih kosong.
        $peta = [
            'odp_name'           => 'odp_name',
            'chief_code'         => 'chief_code',
            'chief_name'         => 'chief_name',
            'sto'                => 'sto',
            'scorderno'          => 'scorderno',
            'servicenum'         => 'servicenum',
            'customer_name'      => 'customer_name',
            'customer_address'   => 'customer_address',
            'no_hp'              => 'no_hp',
            'package_name'       => 'package_name',
            'crmordertype'       => 'crmordertype',
            'latitude_ins'       => 'latitude_ins',
            'longitude_ins'      => 'longitude_ins',
            'datecreated'        => 'datecreated',
            'tgl_manja'          => 'tgl_manja',
            'statusdate'         => 'statusdate',
            'engineermemo'       => 'engineermemo',
            'catatan_teknisi'    => 'catatan_teknisi',
            'errorcode_akhir'    => 'errorcode_akhir',
            'suberrorcode_akhir' => 'suberrorcode_akhir',
            'homepass_id'        => 'homepassid',
            'long_inst_ut'       => 'long_inst_ut',
            'lat_inst_ut'        => 'lat_inst_ut',
            'durasi_instalasi'   => 'durasi_instalasi',
            'durasi_tti'         => 'durasi_tti',
        ];

        $kolomKpro = array_unique(array_merge(['wonum'], array_values($peta)));

        DB::table('kpro_orders')
            ->join('monitorings', 'monitorings.wonum', '=', 'kpro_orders.wonum')
            ->orderBy('kpro_orders.wonum')
            ->select(array_map(fn ($k) => "kpro_orders.{$k}", $kolomKpro))
            ->chunk(500, function ($baris) use (&$jumlah, $peta) {
                $wonums = $baris->pluck('wonum')->all();
                $orders = DB::table('monitorings')->whereIn('wonum', $wonums)->get()->keyBy('wonum');

                foreach ($baris as $k) {
                    $m = $orders->get($k->wonum);
                    if (! $m) {
                        continue;
                    }

                    $update = [];
                    foreach ($peta as $kolM => $kolK) {
                        if (! blank($m->{$kolM}) || blank($k->{$kolK})) {
                            continue;
                        }

                        $nilai = $k->{$kolK};
                        if ($kolM === 'odp_name') {
                            $nilai = \App\Services\MonitoringRowMapper::namaOdp($nilai);
                        } elseif ($kolM === 'scorderno') {
                            $nilai = \App\Services\MonitoringRowMapper::kodeScorderno($nilai);
                        } elseif ($kolM === 'sto') {
                            $nilai = mb_strtoupper(mb_substr(trim((string) $nilai), 0, 5));
                        }

                        if (! blank($nilai)) {
                            $update[$kolM] = $nilai;
                        }
                    }

                    if ($update) {
                        if (isset($update['odp_name'])) {
                            $jumlah++;
                        }
                        DB::table('monitorings')->where('id', $m->id)->update($update);
                    }
                }
            });

        return $jumlah;
    }

    public static function isiDariScOrder(): int
    {
        $jumlah = 0;

        $kandidat = DB::table('monitorings as a')
            ->join('monitorings as b', 'b.scorderno', '=', 'a.scorderno')
            ->where(fn ($q) => $q->whereNull('a.odp_name')->orWhere('a.odp_name', ''))
            ->whereNotNull('a.scorderno')->where('a.scorderno', '!=', '')
            ->whereNotNull('b.odp_name')->where('b.odp_name', '!=', '')
            ->groupBy('a.id')
            ->havingRaw('COUNT(DISTINCT b.odp_name) = 1')
            ->select('a.id', DB::raw('MIN(b.odp_name) as nama'))
            ->get();

        foreach ($kandidat as $k) {
            $jumlah += DB::table('monitorings')->where('id', $k->id)->update(['odp_name' => $k->nama]);
        }

        return $jumlah;
    }

    public static function hubungkanOdpId(): void
    {
        if (Schema::hasColumn('monitorings', 'odp_id')) {
            DB::update('UPDATE monitorings SET odp_id = (SELECT odps.id FROM odps WHERE odps.nama_odp = monitorings.odp_name LIMIT 1) WHERE odp_name IS NOT NULL');
        }
    }

    public function stoRelasi()
    {
        return $this->belongsTo(Sto::class, 'sto', 'kode_sto');
    }

    public function getKoordinatAttribute(): ?string
    {
        return ($this->latitude !== null && $this->longitude !== null)
            ? rtrim(rtrim((string) $this->latitude, '0'), '.') . ', ' . rtrim(rtrim((string) $this->longitude, '0'), '.')
            : null;
    }
}
