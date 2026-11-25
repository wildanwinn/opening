<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sto extends Model
{
    protected $table = 'sto';
    protected $fillable = ['kode_sto', 'nama_sto', 'wilayah'];

    public function monitorings()
    {
        return $this->hasMany(Monitoring::class, 'sto', 'kode_sto');
    }
}