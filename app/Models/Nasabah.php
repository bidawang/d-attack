<?php
// app/Models/Nasabah.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nasabah extends Model
{
    use SoftDeletes;

    protected $table = 'nasabah';
    protected $fillable = ['nama','no_hp','alamat','catatan','is_aktif'];
    protected $casts = ['is_aktif' => 'boolean'];

    public function tagihan() { return $this->hasMany(Tagihan::class); }
}
