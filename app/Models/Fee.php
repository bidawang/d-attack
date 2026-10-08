<?php
// app/Models/Fee.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    protected $fillable = ['tagihan_id','siklus_bunga_id','basis_bayar','sisa_kena_bunga','ambang_persen','persen_fee','total_fee'];
    protected $casts = [
        'basis_bayar' => 'decimal:2', 'sisa_kena_bunga' => 'decimal:2',
        'ambang_persen' => 'decimal:2', 'persen_fee' => 'decimal:2', 'total_fee' => 'decimal:2',
    ];

    public function tagihan() { return $this->belongsTo(Tagihan::class); }
    public function siklus()  { return $this->belongsTo(SiklusBunga::class, 'siklus_bunga_id'); }
    public function rincian() { return $this->hasMany(FeeRincian::class); }
}