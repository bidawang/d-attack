<?php
// app/Models/SiklusBunga.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiklusBunga extends Model
{
    protected $table = 'siklus_bunga';
    protected $fillable = ['tagihan_id','siklus_ke','bunga'];

    public function tagihan()    { return $this->belongsTo(Tagihan::class); }
    public function pembayaran() { return $this->hasMany(Pembayaran::class); }  // bayar SESUDAH bunga ini
    public function fee()        { return $this->hasOne(Fee::class); }
}