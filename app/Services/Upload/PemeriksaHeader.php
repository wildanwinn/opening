<?php

namespace App\Services\Upload;

/**
 * Validasi ketat header sebelum satu baris pun disimpan:
 *  1. kolom kunci (primary key) wajib ada,
 *  2. tidak boleh ada header yang tidak dikenal (tidak bisa menambah header),
 *  3. header harus cukup cocok dengan jenis file yang dipilih,
 *  4. tidak boleh ada header kembar.
 * Kalau gagal, pesan menyebut jenis file yang paling mirip supaya mudah salah-menu diperbaiki.
 */
final class PemeriksaHeader
{
    /** @param array<int,string> $slugHeader judul kolom file dalam format slug
     *  @return array<int,string> daftar pesan kesalahan (kosong = lolos) */
    public static function periksa(string $jenis, array $slugHeader): array
    {
        $def     = Skema::definisi($jenis);
        $boleh   = Skema::slugs($jenis);
        $slugHeader = array_values(array_filter($slugHeader, fn ($h) => $h !== ''));
        if ($jenis === 'odp' && in_array('odp_name', $slugHeader, true) && ! in_array('nama_odp', $slugHeader, true)) {
            // file ODP sumber memakai ODP_NAME untuk nama ODP
            $slugHeader = array_map(fn ($h) => $h === 'odp_name' ? 'nama_odp' : $h, $slugHeader);
        }
        $salah   = [];

        if (! $slugHeader) {
            return ['Baris judul kolom tidak ditemukan. Pastikan baris pertama berisi judul kolom.'];
        }

        $kembar = array_keys(array_filter(array_count_values($slugHeader), fn ($n) => $n > 1));
        if ($kembar) {
            $salah[] = 'Ada judul kolom kembar: ' . implode(', ', array_map('strtoupper', $kembar)) . '.';
        }

        $kunci = $def['kunci'];
        if (! in_array($kunci, $slugHeader, true)) {
            $salah[] = 'Kolom kunci ' . strtoupper($kunci) . ' tidak ada. Setiap file ' . $def['label'] . ' wajib punya kolom ini sebagai primary key.';
        }

        $asing = array_values(array_diff($slugHeader, $boleh));
        if ($asing) {
            $salah[] = 'Header tidak dikenal untuk ' . $def['label'] . ': ' . implode(', ', array_map('strtoupper', array_slice($asing, 0, 8)))
                . (count($asing) > 8 ? ' (+' . (count($asing) - 8) . ' lainnya)' : '')
                . '. Menambah header baru tidak diperbolehkan.';
        }

        if ($ciri = Skema::CIRI_KHAS[$jenis] ?? null) {
            $ada = count(array_intersect($ciri['kolom'], $slugHeader));
            if ($ada < $ciri['minimal']) {
                $salah[] = 'File tidak memuat kolom ciri khas ' . $def['label'] . ' (contoh: '
                    . implode(', ', array_map('strtoupper', array_slice($ciri['kolom'], 0, 3)))
                    . '). Kemungkinan ini file jenis lain.';
            }
        }

        $cocok = count(array_intersect($boleh, $slugHeader)) / max(1, count($boleh));
        if ($cocok < $def['minimal']) {
            $salah[] = 'Isi header hanya cocok ' . round($cocok * 100) . '% dengan format ' . $def['label'] . ' (minimal ' . round($def['minimal'] * 100) . '%).';
        }

        if ($salah) {
            $mirip = self::jenisTerdekat($slugHeader, $jenis);
            if ($mirip) {
                $salah[] = 'File ini lebih mirip ' . Skema::definisi($mirip)['label'] . '. Upload lewat tab ' . Skema::definisi($mirip)['label'] . '.';
            }
        }

        return $salah;
    }

    /** Jenis lain yang header-nya paling cocok (>= 80% dan tanpa header asing), atau null. */
    public static function jenisTerdekat(array $slugHeader, ?string $kecuali = null): ?string
    {
        $terbaik = null;
        $skor    = 0.0;

        foreach (Skema::JENIS as $j) {
            if ($j === $kecuali) {
                continue;
            }
            $boleh = Skema::slugs($j);
            if (array_diff($slugHeader, $boleh)) {
                continue;
            }
            $c = count(array_intersect($boleh, $slugHeader)) / max(1, count($boleh));
            if ($c >= 0.8 && $c > $skor) {
                [$terbaik, $skor] = [$j, $c];
            }
        }

        return $terbaik;
    }
}
