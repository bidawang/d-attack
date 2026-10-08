<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Services\StatusTagihan;
use Illuminate\Http\Request;

class KoreksiPembayaranController extends Controller
{
    /** Hanya pembayaran di periode berjalan (setelah siklus terakhir) yang boleh dikoreksi. */
    public static function boleh(Pembayaran $p): bool
    {
        $terakhir = $p->tagihan->siklus->last();

        return (int) ($p->siklus_bunga_id ?? 0) === (int) ($terakhir?->id ?? 0);
    }

    private function jaga(Pembayaran $p): ?\Illuminate\Http\RedirectResponse
    {
        $p->loadMissing('tagihan.siklus');

        if (! self::boleh($p)) {
            return redirect()->route('nasabah.show', $p->tagihan->nasabah_id)
                ->with('err', 'Pembayaran ini sudah terkunci karena bunganya sudah diproses.');
        }

        return null;
    }

    public function edit(Pembayaran $pembayaran)
    {
        if ($tolak = $this->jaga($pembayaran)) {
            return $tolak;
        }

        $pembayaran->load('tagihan.nasabah');

        return view('pembayaran.edit', ['p' => $pembayaran]);
    }

    public function update(Request $r, Pembayaran $pembayaran)
    {
        if ($tolak = $this->jaga($pembayaran)) {
            return $tolak;
        }

        $t     = $pembayaran->tagihan;
        $maks  = round($t->sisa + (float) $pembayaran->jumlah_bayar, 2);

        $d = $r->validate([
            'jumlah_bayar'  => ['required', 'numeric', 'min:1', 'max:' . number_format($maks, 2, '.', '')],
            'tanggal_bayar' => ['required', 'date'],
            'catatan'       => ['nullable', 'string', 'max:500'],
        ]);

        $pembayaran->update($d);
        StatusTagihan::sinkron($t);

        return redirect()->route('nasabah.show', $t->nasabah_id)->with('ok', 'Pembayaran dikoreksi.');
    }

    public function destroy(Pembayaran $pembayaran)
    {
        if ($tolak = $this->jaga($pembayaran)) {
            return $tolak;
        }

        $t = $pembayaran->tagihan;
        $pembayaran->delete();
        StatusTagihan::sinkron($t);

        return redirect()->route('nasabah.show', $t->nasabah_id)->with('ok', 'Pembayaran dihapus.');
    }
}