<?php

namespace App\Services;

use App\Imports\BacaMonitoring;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;

/**
 * Membaca file ODP menjadi baris-baris berkunci slug (judul kolom huruf kecil, spasi -> _).
 * File ODP dari sumber berekstensi .xls tetapi isinya tabel HTML, jadi dicek dulu isinya;
 * .xlsx / .xls asli / .csv dibaca lewat Excel biasa.
 */
class OdpFileReader
{
    /** @return array<int, array<string, string|null>> */
    public function baca(string $path, string $namaAsli): array
    {
        $awal = (string) file_get_contents($path, false, null, 0, 4096);

        if (stripos($awal, '<table') !== false) {
            return $this->bacaHtml((string) file_get_contents($path));
        }

        $lembar = array_values(Excel::toArray(new BacaMonitoring, new UploadedFile($path, $namaAsli, null, null, true)))[0] ?? [];

        return $lembar;
    }

    /** @return array<int, array<string, string|null>> */
    private function bacaHtml(string $html): array
    {
        $dom = new \DOMDocument;
        $lama = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($lama);

        $judul = null;
        $hasil = [];

        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $sel = [];
            foreach ($tr->childNodes as $anak) {
                if (in_array($anak->nodeName, ['td', 'th'], true)) {
                    $sel[] = trim($anak->textContent);
                }
            }

            if ($judul === null) {
                $judul = array_map(fn ($j) => self::slug($j), $sel);
                continue;
            }

            if (! array_filter($sel, fn ($v) => $v !== '')) {
                continue;
            }

            $baris = [];
            foreach ($judul as $i => $j) {
                if ($j !== '') {
                    $baris[$j] = ($sel[$i] ?? '') === '' ? null : $sel[$i];
                }
            }
            $hasil[] = $baris;
        }

        return $hasil;
    }

    /**
     * File ODP dari sumber (Detil_BLACKSYSTEM_...) memakai judul ODP_NAME untuk nama ODP.
     * Untuk upload ODP, kolom itu diperlakukan sebagai NAMA_ODP (primary key tabel odps).
     * File Data Master / KPRO tetap ditolak karena punya puluhan header lain yang bukan header ODP.
     */
    public static function samakanHeaderOdp(array $lembar): array
    {
        foreach ($lembar as $i => $baris) {
            if (array_key_exists('odp_name', $baris) && ! array_key_exists('nama_odp', $baris)) {
                $baru = [];
                foreach ($baris as $k => $v) {
                    $baru[$k === 'odp_name' ? 'nama_odp' : $k] = $v;
                }
                $lembar[$i] = $baru;
            }
        }

        return $lembar;
    }

    public static function slug(string $judul): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '_', mb_strtolower($judul)), '_');
    }
}
