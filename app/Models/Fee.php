<?php
// app/Models/Fee.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{
    use HasFactory;
    protected $table = 'fees';
    protected $fillable = ['tagihan_id','fee','akom','player','support','asset'];

    public function tagihan() { return $this->belongsTo(Tagihan::class); }
}