<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, SoftDeletes, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'no_hp',
        'is_aktif',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_aktif' => 'boolean',
    ];

    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'penagih_id');
    }

    public function pembayaran()
    {
        return $this->hasMany(Pembayaran::class, 'penagih_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPenagih(): bool
    {
        return $this->role === 'penagih';
    }
}
