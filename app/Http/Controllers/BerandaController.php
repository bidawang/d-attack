<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Services\RingkasanPeriode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class BerandaController extends Controller
{
    public function index(Request $request, RingkasanPeriode $svc)
    {
        $user = $request->user();

        // Tamu melihat form login di alamat yang sama.
        if (! $user) {
            return view('auth.login');
        }

        $bulan = $this->bulanDipilih($request);
        $tenggat = $svc->tanggalTenggatPada($bulan);

        // Tagihan yang tenggat aktifnya jatuh pada tanggal ini, atau yang sudah dibungakan pada tenggat ini.
        $tagihan = Tagihan::query()
            ->with(['nasabah:id,nama', 'siklus', 'pembayaran'])
            ->where(function ($q) use ($tenggat) {
                $q->whereDate('tenggat_waktu', $tenggat->toDateString())
                  ->orWhereHas('siklus', fn ($s) => $s->whereDate('tenggat_waktu', $tenggat->toDateString()));
            })
            ->get()
            ->sortBy(fn ($t) => mb_strtolower($t->nasabah->nama ?? '').'|'.$t->kode)
            ->values();

        // Bon gantung = sisa tagihan nasabah yang sama dengan tenggat setelah tanggal ini.
        $bon = Tagihan::query()
            ->with(['siklus', 'pembayaran'])
            ->whereIn('nasabah_id', $tagihan->pluck('nasabah_id')->unique())
            ->where('status', '!=', 'lunas')
            ->whereDate('tenggat_waktu', '>', $tenggat->toDateString())
            ->get()
            ->groupBy('nasabah_id')
            ->map(fn ($g) => (float) $g->sum(fn ($t) => max(0.0, $svc->sisaTagihan($t))));

        $sudahDipakai = [];
        $rows = $tagihan->map(function ($t) use ($svc, $tenggat, $bon, &$sudahDipakai) {
            $r = $svc->baris($t, $tenggat);

            // Bon gantung per nasabah, ditampilkan sekali agar total tidak dobel.
            $r['bon'] = isset($sudahDipakai[$t->nasabah_id]) ? 0.0 : (float) ($bon[$t->nasabah_id] ?? 0.0);
            $sudahDipakai[$t->nasabah_id] = true;

            $r['total_semua'] = round($r['tagihan'] + $r['bunga'] + $r['bon'], 2);
            $r['sisa']        = round($r['total_semua'] - $r['bayar'], 2);
            $r['finish']      = $r['sisa'] <= 0.004;

            return $r;
        });

        $sum = [
            'tagihan' => $rows->sum('tagihan'),
            'bayar'   => $rows->sum('bayar'),
            'total'   => $rows->sum('total'),
            'fee'     => $rows->sum('fee'),
        ];

        return view('beranda', [
            'user'     => $user,
            'admin'    => $user->isAdmin(),
            'bulan'    => $bulan->format('Y-m'),
            'tenggat'  => $tenggat,
            'tanggal'  => $svc->tanggalTenggat,
            'rows'     => $rows,
            'sum'      => $sum,
            'komponen' => $svc->komponen,
            'ambang'   => $svc->ambangFee,
        ]);
    }

    private function bulanDipilih(Request $request): Carbon
    {
        $param = (string) $request->query('bulan', '');

        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $param)) {
            return Carbon::createFromFormat('!Y-m', $param)->startOfMonth();
        }

        return now()->startOfMonth();
    }
}
