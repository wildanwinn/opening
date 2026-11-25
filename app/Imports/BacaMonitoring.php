<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Pembaca Excel sederhana: hanya mengubah sheet pertama menjadi array
 * (kunci = judul kolom dalam format slug). Dipakai lewat Excel::toArray().
 */
class BacaMonitoring implements Import, WithHeadingRow, SkipsEmptyRows
{
}
