<?php
// app/Models/Pengaturan.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengaturan extends Model
{
    protected $table = 'pengaturan';
    protected $primaryKey = 'kunci';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['kunci','nilai','keterangan'];

    public static function nilai(string $kunci): float
    {
        return (float) static::findOrFail($kunci)->nilai;
    }
}