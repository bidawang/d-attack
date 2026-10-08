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

        if (! $user) {
            return view('auth.login');
        }

        // Ambil rentang tanggal dari input
        [$dari, $sampai] = $this->rentangTanggalDipilih($request);

        $tenggat = $svc->tanggalTenggatPada($dari);
        $tab     = $request->query('tab', 'aktif');

        // Query tagihan berdasarkan rentang tanggal
        $tagihan = Tagihan::query()
            ->with(['nasabah:id,nama', 'siklus', 'pembayaran'])
            ->where(function ($q) use ($dari, $sampai) {
                $q->whereBetween('tenggat_waktu', [$dari->format('Y-m-d'), $sampai->format('Y-m-d')])
                  ->orWhereHas('siklus', fn ($s) => $s->whereBetween('tenggat_waktu', [$dari->format('Y-m-d'), $sampai->format('Y-m-d')]));
            })
            ->get()
            ->sortBy(fn ($t) => mb_strtolower($t->nasabah->nama ?? '').'|'.$t->kode)
            ->values();

        // Bon gantung
        $bon = Tagihan::query()
            ->with(['siklus', 'pembayaran'])
            ->whereIn('nasabah_id', $tagihan->pluck('nasabah_id')->unique())
            ->where('status', '!=', 'lunas')
            ->whereDate('tenggat_waktu', '>', $sampai->format('Y-m-d'))
            ->get()
            ->groupBy('nasabah_id')
            ->map(fn ($g) => (float) $g->sum(fn ($t) => max(0.0, $svc->sisaTagihan($t))));

        $sudahDipakai = [];
        $allRows = $tagihan->map(function ($t) use ($svc, $dari, $sampai, $bon, &$sudahDipakai) {
            $r = $svc->baris($t, [$dari, $sampai]);

            $r['bon'] = isset($sudahDipakai[$t->nasabah_id]) ? 0.0 : (float) ($bon[$t->nasabah_id] ?? 0.0);
            $sudahDipakai[$t->nasabah_id] = true;

            $r['total_semua'] = round($r['tagihan'] + $r['bunga'] + $r['bon'], 2);
            $r['sisa']        = round($r['total_semua'] - $r['bayar'], 2);
            $r['finish']      = $r['sisa'] <= 0.004;

            if ($r['lunas']) {
                $r['status_tab'] = 'lunas';
            } elseif ($r['diproses']) {
                $r['status_tab'] = 'berbunga';
            } else {
                $r['status_tab'] = 'bon_gantung';
            }

            return $r;
        });

        // Hitung jumlah item per kategori
        $counts = [
            'aktif'       => $allRows->where('status_tab', '!=', 'lunas')->count(),
            'berbunga'    => $allRows->where('status_tab', 'berbunga')->count(),
            'bon_gantung' => $allRows->where('status_tab', 'bon_gantung')->count(),
            'lunas'       => $allRows->where('status_tab', 'lunas')->count(),
            'semua'       => $allRows->count(),
        ];

        // Filter data berdasarkan tab
        $rows = $allRows->filter(function ($r) use ($tab) {
            return match ($tab) {
                'berbunga'    => $r['status_tab'] === 'berbunga',
                'bon_gantung' => $r['status_tab'] === 'bon_gantung',
                'lunas'       => $r['status_tab'] === 'lunas',
                'semua'       => true,
                default       => $r['status_tab'] !== 'lunas',
            };
        })->values();

        $sum = [
            'tagihan' => $rows->sum('tagihan'),
            'bayar'   => $rows->sum('bayar'),
            'total'   => $rows->sum('total'),
            'fee'     => $rows->sum('fee'),
        ];

        return view('beranda', [
            'user'           => $user,
            'admin'          => $user->isAdmin(),
            'bulan'          => $dari->format('Y-m'), // Disediakan agar tidak undefined jika ada view lain yang pakai
            'dari_tanggal'   => $dari->format('Y-m-d'),
            'sampai_tanggal' => $sampai->format('Y-m-d'),
            'tenggat'        => $tenggat,
            'tanggal'        => $svc->tanggalTenggat,
            'rows'           => $rows,
            'sum'            => $sum,
            'komponen'       => $svc->komponen,
            'ambang'         => $svc->ambangFee,
            'tab'            => $tab,
            'counts'         => $counts,
        ]);
    }

    private function rentangTanggalDipilih(Request $request): array
    {
        $dariInput   = (string) $request->query('dari_tanggal', '');
        $sampaiInput = (string) $request->query('sampai_tanggal', '');

        try {
            $dari   = $dariInput ? Carbon::parse($dariInput)->startOfDay() : now()->startOfMonth();
            $sampai = $sampaiInput ? Carbon::parse($sampaiInput)->endOfDay() : now()->endOfMonth();
        } catch (\Exception $e) {
            $dari   = now()->startOfMonth();
            $sampai = now()->endOfMonth();
        }

        return [$dari, $sampai];
    }
}