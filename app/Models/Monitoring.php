<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monitoring extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'datecreated' => 'datetime',
        'tgl_manja'   => 'datetime',
        'statusdate'  => 'datetime',
    ];

    public function stoRelasi()
    {
        return $this->belongsTo(Sto::class, 'sto', 'kode_sto');
    }

    /** Relasi lewat kunci nama: monitorings.odp_name = odps.nama_odp. */
    public function odp()
    {
        return $this->belongsTo(Odp::class, 'odp_name', 'nama_odp');
    }

    /** Data BIMA untuk WO ini (kunci: wonum = workorder). */
    public function bima()
    {
        return $this->hasOne(BimaOrder::class, 'workorder', 'wonum');
    }

    /** Ada isinya = order TSEL; tidak ada = non-TSEL. */
    public function tsel()
    {
        return $this->hasOne(TselOrder::class, 'workorder', 'wonum');
    }

    public function kpro()
    {
        return $this->hasOne(KproOrder::class, 'wonum', 'wonum');
    }

    /** Status yang ditampilkan di Data Monitoring: dari BIMA, kalau WO belum ada di BIMA pakai status sendiri. */
    public function getStatusTampilAttribute(): ?string
    {
        return $this->bima?->status ?? $this->status;
    }

    public function uploadBatch()
    {
        return $this->belongsTo(UploadBatch::class);
    }

    public function pekerjaanMaterials()
    {
        return $this->hasMany(PekerjaanMaterial::class);
    }

    public function fotos()
    {
        return $this->hasMany(PekerjaanFoto::class);
    }

    public function logs()
    {
        return $this->hasMany(OrderEditLog::class);
    }

    /**
     * Batasi data sesuai user.
     * - Selain teknisi: boleh semua.
     * - Teknisi dengan STO: hanya order di STO-nya.
     * - Teknisi tanpa STO: hanya order yang di-assign ke dia sendiri
     *   (sebelumnya teknisi tanpa STO bisa melihat semua data).
     */
    public function scopeUntukUser($query, $user)
    {
        if (! $user->isTeknisi()) {
            return $query;
        }

        if ($user->sto) {
            return $query->where('sto', $user->sto->kode_sto);
        }

        return $query->where(function ($q) use ($user) {
            if (! blank($user->kode_teknisi)) {
                $q->where('chief_code', $user->kode_teknisi);
            } else {
                $q->where('chief_name', $user->nama);
            }
        });
    }
}
