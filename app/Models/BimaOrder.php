<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Data export BIMA. Kunci: workorder (= monitorings.wonum). */
class BimaOrder extends Model
{
    protected $primaryKey = 'workorder';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];

    protected $casts = [
        'date_created' => 'datetime', 'date_modified' => 'datetime', 'status_date' => 'datetime',
        'sched_start' => 'datetime', 'measurement_date' => 'datetime', 'booking_date' => 'datetime',
    ];

    public function tsel()
    {
        return $this->hasOne(TselOrder::class, 'workorder', 'workorder');
    }

    public function monitoring()
    {
        return $this->hasOne(Monitoring::class, 'wonum', 'workorder');
    }
}
