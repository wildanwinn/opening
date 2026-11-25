<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['nik', 'kode_teknisi', 'nama', 'password', 'role', 'sto_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function sto()
    {
        return $this->belongsTo(Sto::class, 'sto_id');
    }

    public function isAdmin()   { return $this->role === 'admin'; }
    public function isHd()      { return $this->role === 'hd'; }
    public function isTeknisi() { return $this->role === 'teknisi'; }
}