<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Tagihan;
use App\Services\RingkasanPeriode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    public function store(Request $request, RingkasanPeriode $svc)
    {
        $data = $request->validate([
            'tagihan_id'    => ['required', 'integer', Rule::exists('tagihan', 'id')->whereNull('deleted_at')],
            'jumlah_bayar'  => ['required', 'numeric', 'gt:0'],
            'tanggal_bayar' => ['nullable', 'date'],
            'metode'        => ['required', Rule::in(['tunai', 'transfer'])],
            'catatan'       => ['nullable', 'string', 'max:255'],
        ]);

        $pesan = DB::transaction(function () use ($data, $request, $svc) {
            // Kunci baris tagihan supaya dua input bersamaan tidak saling menimpa.
            $t = Tagihan::with(['siklus', 'pembayaran'])->lockForUpdate()->findOrFail($data['tagihan_id']);

            $sisa   = $svc->sisaTagihan($t);
            $jumlah = round((float) $data['jumlah_bayar'], 2);

            if ($t->status === 'lunas' || $sisa <= 0) {
                throw ValidationException::withMessages(['tagihan_id' => 'Tagihan '.$t->kode.' sudah lunas.']);
            }
            if ($jumlah > $sisa + 0.005) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Jumlah melebihi sisa tagihan (Rp '.number_format($sisa, 0, ',', '.').').',
                ]);
            }

            // Belum pernah dibungakan -> bayar sebelum bunga (bon gantung).
            // Sudah ada siklus bunga   -> bayar sesudah bunga siklus terakhir.
            $terakhir = $t->siklus->last();

            Pembayaran::create([
                'tagihan_id'      => $t->id,
                'siklus_bunga_id' => $terakhir?->id,
                'penagih_id'      => $request->user()->id,
                'jumlah_bayar'    => $jumlah,
                'tanggal_bayar'   => $data['tanggal_bayar'] ?? now(),
                'tahap'           => $terakhir ? 'sesudah_bunga' : 'sebelum_bunga',
                'metode'          => $data['metode'],
                'catatan'         => $data['catatan'] ?? null,
            ]);

            if ($sisa - $jumlah <= 0.005) {
                $t->update(['status' => 'lunas']);
            }

            return 'Pembayaran Rp '.number_format($jumlah, 0, ',', '.').' untuk '.$t->kode.' tersimpan.';
        });

        return back()->with('ok', $pesan);
    }
}
