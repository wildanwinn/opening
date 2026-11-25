<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PekerjaanFoto extends Model
{
    protected $fillable = ['monitoring_id', 'user_id', 'jenis', 'path'];
}
