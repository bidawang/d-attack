<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Models\User;
use App\Services\RingkasanPeriode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DaftarTagihanController extends Controller
{
    public const STATUS = [
        ''            => 'Semua status',
        'belum_lunas' => 'Belum lunas',
        'lunas'       => 'Lunas',
        'bersambung'  => 'Bersambung',
        'sambungan'   => 'Sambungan',
        'bon_gantung' => 'Bon gantung',
    ];

    public function index(Request $request, RingkasanPeriode $svc)
    {
        $q      = trim((string) $request->query('q', ''));
        $status = (string) $request->query('status', '');
        $dari   = $this->tanggalAman($request->query('dari_tanggal'));
        $sampai = $this->tanggalAman($request->query('sampai_tanggal'));

        if (! array_key_exists($status, self::STATUS)) {
            $status = '';
        }

        $query = Tagihan::query()
            ->when($q !== '', function ($x) use ($q) {
                $x->where(function ($w) use ($q) {
                    $w->where('kode', 'like', "%{$q}%")
                      ->orWhereHas('nasabah', fn ($n) => $n->where('nama', 'like', "%{$q}%"));
                });
            })
            ->when($status === 'belum_lunas', fn ($x) => $x->whereNotIn('status', ['lunas', 'bersambung']))
            ->when($status !== '' && $status !== 'belum_lunas', fn ($x) => $x->where('status', $status))
            ->when($dari, fn ($x) => $x->whereDate('tanggal_hutang', '>=', $dari))
            ->when($sampai, fn ($x) => $x->whereDate('tanggal_hutang', '<=', $sampai));

        // Ringkasan dari seluruh hasil filter (bukan hanya halaman ini)
        $ringkas = [
            'jumlah'  => (clone $query)->count(),
            'hutang'  => (float) (clone $query)->sum('jumlah_hutang'),
            'dibayar' => (float) (clone $query)->sum('jumlah_dibayar'),
        ];

        $tagihan = $query
            ->with(['nasabah:id,nama', 'siklus', 'pembayaran'])
            ->orderByDesc('tanggal_hutang')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $penagih = User::query()
            ->whereIn('id', $tagihan->pluck('penagih_id')->filter()->unique())
            ->pluck('name', 'id');

        return view('tagihan.index', [
            'admin'          => $request->user()->isAdmin(),
            'tagihan'        => $tagihan,
            'penagih'        => $penagih,
            'ringkas'        => $ringkas,
            'svc'            => $svc,
            'ambang'         => $svc->ambangFee,
            'q'              => $q,
            'status'         => $status,
            'statusOpsi'     => self::STATUS,
            'dari_tanggal'   => $dari,
            'sampai_tanggal' => $sampai,
        ]);
    }

    private function tanggalAman(mixed $nilai): ?string
    {
        if (! $nilai) {
            return null;
        }

        try {
            return Carbon::parse((string) $nilai)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }
}