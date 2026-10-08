<?php
// app/Models/FeeKomponen.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeKomponen extends Model
{
    protected $table = 'fee_komponen';
    public $timestamps = false;
    protected $fillable = ['kode','nama','persen','urutan','is_aktif'];
    protected $casts = ['is_aktif' => 'boolean', 'persen' => 'decimal:2'];

    public function rincian() { return $this->hasMany(FeeRincian::class); }
}