<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Data KPRO (report fulfillment end state). Kunci: wonum. */
class KproOrder extends Model
{
    protected $primaryKey = 'wonum';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $guarded = [];
}
