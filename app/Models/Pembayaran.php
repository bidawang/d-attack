<?php
// app/Models/Pembayaran.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembayaran extends Model
{
    use SoftDeletes;

    protected $table = 'pembayaran';
    protected $fillable = ['tagihan_id','siklus_bunga_id','penagih_id','jumlah_bayar','tanggal_bayar','tahap','metode','bukti','catatan'];
    protected $casts = ['tanggal_bayar' => 'datetime', 'jumlah_bayar' => 'decimal:2'];

    public function tagihan() { return $this->belongsTo(Tagihan::class); }
    public function siklus()  { return $this->belongsTo(SiklusBunga::class, 'siklus_bunga_id'); }
    public function penagih() { return $this->belongsTo(User::class, 'penagih_id'); }
}