<?php

namespace App\Services;

use App\Models\Tagihan;

class StatusTagihan
{
    /** Samakan status tagihan dengan sisa hutangnya (dipakai setelah koreksi pembayaran). */
    public static function sinkron(Tagihan $tagihan): void
    {
        $t = Tagihan::findOrFail($tagihan->id);

        $sisa = round(
            (float) $t->jumlah_hutang
            + (float) $t->siklus()->sum('bunga')
            - (float) $t->pembayaran()->sum('jumlah_bayar'),
            2
        );

        if ($sisa <= 0.005) {
            $baru = 'lunas';
        } elseif ($t->status === 'lunas') {
            $baru = $t->siklus()->exists() ? 'berbunga' : 'bon_gantung';
        } else {
            $baru = $t->status;
        }

        if ($baru !== $t->status) {
            $t->update(['status' => $baru]);
        }
    }
}