<?php
// app/Models/FeeRincian.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeRincian extends Model
{
    protected $table = 'fee_rincian';
    public $timestamps = false;
    protected $fillable = ['fee_id','fee_komponen_id','persen','jumlah'];
    protected $casts = ['persen' => 'decimal:2', 'jumlah' => 'decimal:2'];

    public function fee()     { return $this->belongsTo(Fee::class); }
    public function komponen(){ return $this->belongsTo(FeeKomponen::class, 'fee_komponen_id'); }
}