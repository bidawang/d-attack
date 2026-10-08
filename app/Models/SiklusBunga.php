<?php
// app/Models/SiklusBunga.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiklusBunga extends Model
{
    protected $table = 'siklus_bunga';
    protected $fillable = ['tagihan_id','siklus_ke','tenggat_waktu','saldo_awal','bayar_sebelum_tenggat',
        'sisa_kena_bunga','persen_bunga','bunga','tenggat_berikutnya','diproses_pada'];
    protected $casts = [
        'tenggat_waktu' => 'date', 'tenggat_berikutnya' => 'date', 'diproses_pada' => 'datetime',
        'saldo_awal' => 'decimal:2', 'bayar_sebelum_tenggat' => 'decimal:2',
        'sisa_kena_bunga' => 'decimal:2', 'persen_bunga' => 'decimal:2', 'bunga' => 'decimal:2',
    ];

    public function tagihan()    { return $this->belongsTo(Tagihan::class); }
    public function pembayaran() { return $this->hasMany(Pembayaran::class); }  // bayar SESUDAH bunga ini
    public function fee()        { return $this->hasOne(Fee::class); }
}