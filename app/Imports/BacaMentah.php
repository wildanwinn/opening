<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

/** Membaca sheet pertama apa adanya (tanpa mengubah judul kolom). Baris pertama = judul. */
class BacaMentah implements Import, SkipsEmptyRows
{
}
