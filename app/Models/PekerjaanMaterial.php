<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PekerjaanMaterial extends Model
{
    protected $fillable = ['monitoring_id', 'material_id', 'qty'];

    public function material()
    {
        return $this->belongsTo(Material::class);
    }
}
