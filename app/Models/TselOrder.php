<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** WO BIMA yang punya Order ID TSEL. WO yang tidak ada di tabel ini = non-TSEL. */
class TselOrder extends Model
{
    protected $guarded = ['id'];

    public function bima()
    {
        return $this->belongsTo(BimaOrder::class, 'workorder', 'workorder');
    }
}
