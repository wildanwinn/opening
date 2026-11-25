<?php

namespace App\Services\Upload;

use App\Imports\BacaMentah;
use App\Services\OdpFileReader;
use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Membaca file upload tanpa memuat semuanya ke memori:
 *  - .xlsx dibaca per baris (streaming) sehingga file BIMA 34 ribu baris tetap ringan,
 *  - .xls berisi tabel HTML (KPRO, ODP) dibaca lewat DOM,
 *  - .xls asli / .csv lewat Maatwebsite Excel.
 * Hasil: [judul kolom (slug), generator baris ber-kunci slug].
 */
class PembacaBerkas
{
    /** @return array{0: array<int,string>, 1: \Generator} */
    public function baca(string $path, string $namaAsli): array
    {
        $g = $this->mentah($path, $namaAsli);
        $g->rewind();
        $judul = $g->valid() ? array_map(fn ($j) => OdpFileReader::slug((string) $j), $g->current()) : [];
        $g->next();

        return [$judul, $this->jadikanAssoc($g, $judul)];
    }

    private function jadikanAssoc(\Generator $g, array $judul): \Generator
    {
        while ($g->valid()) {
            $sel = $g->current();
            $g->next();

            if (! array_filter($sel, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }

            $baris = [];
            foreach ($judul as $i => $j) {
                if ($j !== '') {
                    $v = $sel[$i] ?? null;
                    $baris[$j] = ($v === null || $v === '') ? null : $v;
                }
            }
            yield $baris;
        }
    }

    private function mentah(string $path, string $namaAsli): \Generator
    {
        $awal = (string) file_get_contents($path, false, null, 0, 4096);

        if (stripos($awal, '<table') !== false) {
            yield from $this->html($path);
            return;
        }

        if (str_starts_with($awal, "PK") && strtolower(pathinfo($namaAsli, PATHINFO_EXTENSION)) !== 'xls') {
            yield from $this->xlsx($path);
            return;
        }

        $lembar = array_values(Excel::toArray(new BacaMentah, new UploadedFile($path, $namaAsli, null, null, true)))[0] ?? [];
        foreach ($lembar as $baris) {
            yield array_values($baris);
        }
    }

    private function html(string $path): \Generator
    {
        $dom = new \DOMDocument;
        $lama = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?>' . file_get_contents($path), LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        libxml_clear_errors();
        libxml_use_internal_errors($lama);

        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $sel = [];
            foreach ($tr->childNodes as $anak) {
                if (in_array($anak->nodeName, ['td', 'th'], true)) {
                    $sel[] = trim($anak->textContent);
                }
            }
            yield $sel;
        }
    }

    /** Pembaca .xlsx streaming: sharedStrings dimuat, sheet pertama dibaca baris demi baris. */
    private function xlsx(string $path): \Generator
    {
        $zip = new \ZipArchive;
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('File xlsx tidak bisa dibuka.');
        }

        $strings = [];
        if ($zip->locateName('xl/sharedStrings.xml') !== false) {
            $r = new \XMLReader;
            $r->open('zip://' . $path . '#xl/sharedStrings.xml', null, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE);
            $teks = null;
            while ($r->read()) {
                if ($r->nodeType === \XMLReader::ELEMENT && $r->name === 'si') {
                    $teks = '';
                    if ($r->isEmptyElement) {
                        $strings[] = '';
                        $teks = null;
                    }
                } elseif ($r->nodeType === \XMLReader::ELEMENT && $r->name === 't' && $teks !== null) {
                    $teks .= $r->readString();
                } elseif ($r->nodeType === \XMLReader::END_ELEMENT && $r->name === 'si' && $teks !== null) {
                    $strings[] = $teks;
                    $teks = null;
                }
            }
            $r->close();
        }

        $sheet = $this->sheetPertama($zip);
        $zip->close();

        $r = new \XMLReader;
        $r->open('zip://' . $path . '#' . $sheet, null, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_PARSEHUGE);

        $baris = null;
        while ($r->read()) {
            if ($r->nodeType === \XMLReader::ELEMENT && $r->name === 'row') {
                $baris = [];
                if ($r->isEmptyElement) {
                    yield [];
                    $baris = null;
                }
            } elseif ($r->nodeType === \XMLReader::ELEMENT && $r->name === 'c' && $baris !== null) {
                $ref  = $r->getAttribute('r');
                $tipe = $r->getAttribute('t');
                $kol  = $ref ? self::indeksKolom($ref) : count($baris);
                $nilai = null;

                if (! $r->isEmptyElement) {
                    $dalam = $r->depth;
                    while ($r->read() && ! ($r->nodeType === \XMLReader::END_ELEMENT && $r->depth === $dalam)) {
                        if ($r->nodeType === \XMLReader::ELEMENT && $r->name === 'v') {
                            $nilai = $r->readString();
                        } elseif ($r->nodeType === \XMLReader::ELEMENT && $r->name === 't' && $tipe === 'inlineStr') {
                            $nilai = ($nilai ?? '') . $r->readString();
                        }
                    }
                }

                if ($nilai !== null && $tipe === 's') {
                    $nilai = $strings[(int) $nilai] ?? null;
                }
                if (is_string($nilai)) {
                    $nilai = trim($nilai);
                }
                $baris[$kol] = $nilai;
            } elseif ($r->nodeType === \XMLReader::END_ELEMENT && $r->name === 'row' && $baris !== null) {
                if ($baris) {
                    $maks = max(array_keys($baris));
                    $rapi = [];
                    for ($i = 0; $i <= $maks; $i++) {
                        $rapi[] = $baris[$i] ?? null;
                    }
                    yield $rapi;
                } else {
                    yield [];
                }
                $baris = null;
            }
        }
        $r->close();
    }

    private function sheetPertama(\ZipArchive $zip): string
    {
        $wb   = (string) $zip->getFromName('xl/workbook.xml');
        $rels = (string) $zip->getFromName('xl/_rels/workbook.xml.rels');

        if (preg_match('/<sheet\b[^>]*r:id="([^"]+)"/', $wb, $m)
            && preg_match('/<Relationship\b[^>]*Id="' . preg_quote($m[1], '/') . '"[^>]*Target="([^"]+)"/', $rels, $t)) {
            $target = ltrim($t[1], '/');

            return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
        }

        return 'xl/worksheets/sheet1.xml';
    }

    private static function indeksKolom(string $ref): int
    {
        preg_match('/^[A-Z]+/', $ref, $m);
        $n = 0;
        foreach (str_split($m[0] ?? 'A') as $c) {
            $n = $n * 26 + (ord($c) - 64);
        }

        return $n - 1;
    }
}
