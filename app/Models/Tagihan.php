<?php
// app/Models/Tagihan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tagihan extends Model
{
    use SoftDeletes;

    protected $table = 'tagihan';
    protected $fillable = ['kode','nasabah_id','id_tagihan_awal','penagih_id','jumlah_hutang','tanggal_hutang','tenggat_waktu','status','keterangan'];

    protected $casts = [
        'jumlah_hutang' => 'decimal:2',
        'tanggal_hutang' => 'date',
        'tenggat_waktu' => 'date',
    ];

    public function nasabah()    { return $this->belongsTo(Nasabah::class); }
    public function penagih()    { return $this->belongsTo(User::class, 'penagih_id'); }
    public function siklus()     { return $this->hasMany(SiklusBunga::class)->orderBy('siklus_ke'); }
    public function pembayaran() { return $this->hasMany(Pembayaran::class); }
    public function fees()       { return $this->hasMany(Fee::class); }

    public function tagihanAwal() { return $this->belongsTo(Tagihan::class, 'id_tagihan_awal'); }
    public function sambungan()   { return $this->hasMany(Tagihan::class, 'id_tagihan_awal'); }

    public function scopeSambungan($q) { return $q->where('status', 'sambungan'); }
    public function scopeBelumBunga($q) { return $q->whereIn('status', ['bon_gantung', 'sambungan']); }

    public function scopeBonGantung($q) { return $q->where('status', 'bon_gantung'); }
    public function scopeBerbunga($q)   { return $q->where('status', 'berbunga'); }
    public function scopeJatuhTempo($q) { return $q->where('status', '!=', 'lunas')->whereDate('tenggat_waktu', '<', now()); }

    public function getTotalBungaAttribute()   { return (float) $this->siklus()->sum('bunga'); }
    public function getTotalDibayarAttribute() { return (float) $this->pembayaran()->sum('jumlah_bayar'); }
    public function getSisaAttribute()
    {
        return round($this->jumlah_hutang + $this->total_bunga - $this->total_dibayar, 2);
    }
}