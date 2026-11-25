<?php

namespace App\Services;

use App\Models\Monitoring;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

/**
 * Menerjemahkan satu baris Excel ke kolom database, dan menyimpannya.
 * Dipakai oleh pratinjau upload dan proses upload per potongan.
 */
class MonitoringRowMapper
{
    /** kolom database => nama judul kolom Excel yang diterima (sudah dalam format slug) */
    public const ALIAS = [
        'wonum'             => ['wonum'],
        'scorderno'         => ['scorderno', 'sc_orderno', 'sc_order_no', 'sc_order_number'],
        'sto'               => ['sto'],
        'servicenum'        => ['servicenum', 'service_number', 'service_num', 'no_service'],
        'latitude_ins'      => ['latitude_ins', 'lattitude_ins', 'latitude', 'lat_ins'],
        'longitude_ins'     => ['longitude_ins', 'logitude_ins', 'longitude', 'long_ins'],
        'datecreated'       => ['datecreated', 'tgl', 'tanggal', 'date_created'],
        'status'            => ['status'],
        'statusdate'        => ['statusdate', 'status_date'],
        'errorcode_akhir'   => ['errorcode_akhir', 'error_code_akhir'],
        'engineermemo'      => ['engineermemo', 'engineer_memo'],
        // Diperbarui agar mendukung kolom 'nama_odp' dari Excel
        'odp_name'          => ['odp_name', 'nama_odp', 'odp'],
        'homepass_id'       => ['homepass_id', 'home_pass_id', 'flag_homepassid', 'flag_homepass_id', 'flag_home_pass_id', 'flag_homepass', 'flag_home_pass', 'homepass'],
        'long_inst_ut'      => ['long_inst_ut'],
        'lat_inst_ut'       => ['lat_inst_ut'],
        // kolom lama yang tetap dibaca
        'crmordertype'       => ['crmordertype'],
        'datel'              => ['datel_lama', 'datel'],
        'package_name'       => ['package_name'],
        'customer_name'      => ['customer_name'],
        'customer_address'   => ['customer_address'],
        'no_hp'              => ['no_hp'],
        'tgl_manja'          => ['tgl_manja'],
        'status_survey'      => ['status_survey'],
        'amcrew'             => ['amcrew'],
        'chief_code'         => ['chief_code'],
        'chief_name'         => ['chief_name'],
        'errorcode_awal'     => ['errorcode_awal'],
        'suberrorcode_awal'  => ['suberrorcode_awal'],
        'product'            => ['product'],
        'suberrorcode_akhir' => ['suberrorcode_akhir'],
        'durasi_instalasi'   => ['durasi_instalasi'],
        'durasi_tti'         => ['durasi_tti'],
    ];

    /**
     * Saat WONUM sudah ada, HANYA kolom ini yang diperbarui dari Excel.
     * Kolom lain tidak ditimpa (hanya diisi kalau masih kosong), jadi data yang
     * sudah dilengkapi/diedit di web tidak berubah. Status ikut aturan tanggal terbaru.
     */
    public const KOLOM_UPDATE_ULANG = [
        'errorcode_akhir', 'suberrorcode_akhir', 'status_survey', 'durasi_instalasi', 'durasi_tti',
    ];

    public const TANGGAL = ['datecreated', 'tgl_manja', 'statusdate'];
    public const ANGKA   = ['durasi_instalasi', 'durasi_tti'];

    /**
     * Kolom yang ditampilkan di pratinjau upload, di tabel Data Monitoring, dan di detail data:
     * persis kolom ber-header KUNING di Excel DATA MASTER (urutan sama dengan Excel).
     */
    public const KOLOM_PRATINJAU = [
        'wonum'              => 'WONUM',
        'sto'                => 'STO',
        'scorderno'          => 'SCORDERNO',
        'servicenum'         => 'SERVICENUM',
        'latitude_ins'       => 'LATITUDE_INS',
        'longitude_ins'      => 'LONGITUDE_INS',
        'datecreated'        => 'DATECREATED',
        'tgl_manja'          => 'TGL_MANJA',
        'chief_code'         => 'CHIEF_CODE',
        'chief_name'         => 'CHIEF_NAME',
        'status'             => 'STATUS',
        'statusdate'         => 'STATUSDATE',
        'engineermemo'       => 'ENGINEERMEMO',
        'odp_name'           => 'ODP_NAME',
        'errorcode_akhir'    => 'ERRORCODE_AKHIR',
        'suberrorcode_akhir' => 'SUBERRORCODE_AKHIR',
        'homepass_id'        => 'FLAG_HOMEPASSID',
        'long_inst_ut'       => 'LONG_INST_UT',
        'lat_inst_ut'        => 'LAT_INST_UT',
        'durasi_tti'         => 'DURASI_TTI',
    ];

    /** Satu baris Excel (kunci slug) => data siap simpan, atau null kalau WONUM kosong. */
    public function petakan(array $row): ?array
    {
        $hasil = [];

        foreach (self::ALIAS as $kolom => $daftar) {
            $nilai = null;
            foreach ($daftar as $nama) {
                if (array_key_exists($nama, $row) && ! blank($row[$nama])) {
                    $nilai = $row[$nama];
                    break;
                }
            }
            $hasil[$kolom] = $this->bersihkan($kolom, $nilai);
        }

        return blank($hasil['wonum']) ? null : $hasil;
    }

    /** Nama kolom pratinjau yang tidak ada judulnya di Excel. */
    public static function kolomTidakAda(array $judulExcel): array
    {
        $hilang = [];
        foreach (self::KOLOM_PRATINJAU as $kolom => $label) {
            if (! array_intersect(self::ALIAS[$kolom], $judulExcel)) {
                $hilang[] = $label;
            }
        }

        return $hilang;
    }

    /** Simpan satu baris. Mengembalikan 'baru' atau 'update'. */
    public function simpan(array $d, ?int $batchId = null): string
    {
        $ada = Monitoring::where('wonum', $d['wonum'])->first();

        if (! $ada) {
            Monitoring::create($d + ['upload_batch_id' => $batchId]);

            return 'baru';
        }

        $data = $d;
        unset($data['wonum']);

        // Kolom kosong di Excel tidak boleh menghapus data yang sudah ada / dilengkapi manual.
        $data = array_filter($data, fn ($v) => ! blank($v));

        // Status hanya diambil dari Excel kalau tanggalnya lebih baru dari yang tersimpan.
        $lebihBaru = ! blank($data['statusdate'] ?? null)
            && (! $ada->statusdate || Carbon::parse($data['statusdate'])->gt($ada->statusdate));

        $update = [];
        foreach ($data as $kolom => $nilai) {
            if (in_array($kolom, ['status', 'statusdate'], true)) {
                if ($lebihBaru) {
                    $update[$kolom] = $nilai;
                }
            } elseif (in_array($kolom, self::KOLOM_UPDATE_ULANG, true)) {
                // parameter yang memang boleh diperbarui saat upload ulang
                $update[$kolom] = $nilai;
            } elseif (blank($ada->{$kolom})) {
                // sisanya (identitas pelanggan, teknisi, memo, dll) tidak ditimpa, hanya diisi kalau masih kosong
                $update[$kolom] = $nilai;
            }
        }

        $update['upload_batch_id'] = $batchId;

        $ada->fill($update)->save();

        return 'update';
    }

    private function bersihkan(string $kolom, $nilai)
    {
        if (blank($nilai)) {
            return null;
        }

        if (in_array($kolom, self::TANGGAL, true)) {
            return $this->tanggal($nilai)?->format('Y-m-d H:i:s');
        }

        if (in_array($kolom, self::ANGKA, true)) {
            return is_numeric($nilai) ? (float) $nilai : null;
        }

        if ($kolom === 'odp_name') {
            return self::namaOdp($this->teks($nilai));
        }

        if ($kolom === 'scorderno') {
            return self::kodeScorderno($this->teks($nilai));
        }

        return $this->teks($nilai);
    }

    /**
     * SCORDERNO / "SC Order No" di Excel panjang. Yang diambil hanya kodenya (bagian sebelum "_" pertama).
     */
    public static function kodeScorderno(?string $nilai): ?string
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '') {
            return null;
        }
        if (! str_contains($nilai, '_')) {
            return $nilai;
        }

        $awal = explode('_', $nilai, 2)[0];

        if (str_contains($awal, '-') && ! preg_match('/^\d{1,3}-\d+$/', $awal)) {
            $awal = explode('-', $awal, 2)[1];
        }

        return $awal === '' ? $nilai : $awal;
    }

    public static function namaOdp(?string $nilai): ?string
    {
        $nilai = trim((string) $nilai);
        if ($nilai === '') {
            return null;
        }

        return preg_split('/\s+/', $nilai, 2)[0];
    }

    private function teks($nilai): string
    {
        if (is_int($nilai)) {
            return (string) $nilai;
        }

        if (is_float($nilai)) {
            if (fmod($nilai, 1.0) == 0.0 && abs($nilai) < 1e15) {
                return number_format($nilai, 0, '', '');
            }

            return rtrim(rtrim(number_format($nilai, 12, '.', ''), '0'), '.');
        }

        if ($nilai instanceof \DateTimeInterface) {
            return $nilai->format('Y-m-d H:i:s');
        }

        return trim((string) $nilai);
    }

    private function tanggal($nilai): ?Carbon
    {
        if ($nilai instanceof \DateTimeInterface) {
            return Carbon::instance($nilai);
        }

        if (is_numeric($nilai)) {
            return Carbon::instance(ExcelDate::excelToDateTimeObject($nilai));
        }

        try {
            return Carbon::parse($nilai);
        } catch (\Throwable $e) {
            return null;
        }
    }
}