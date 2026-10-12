<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Daftar fee per tagihan (route ini hanya untuk admin, lihat routes/web.php).
 */
class FeeController extends Controller
{
    public function index(Request $request)
    {
        $q      = trim((string) $request->query('q', ''));
        $dari   = $this->tanggalAman($request->query('dari_tanggal'));
        $sampai = $this->tanggalAman($request->query('sampai_tanggal'));

        $query = Fee::query()
            ->when($q !== '', function ($x) use ($q) {
                $x->whereHas('tagihan', function ($t) use ($q) {
                    $t->withTrashed()->where(function ($w) use ($q) {
                        $w->where('kode', 'like', "%{$q}%")
                          ->orWhereHas('nasabah', fn ($n) => $n->withTrashed()->where('nama', 'like', "%{$q}%"));
                    });
                });
            })
            ->when($dari, fn ($x) => $x->whereDate('updated_at', '>=', $dari))
            ->when($sampai, fn ($x) => $x->whereDate('updated_at', '<=', $sampai));

        // Total per komponen dari seluruh hasil filter (bukan hanya halaman aktif)
        $total = (clone $query)
            ->selectRaw('COUNT(*) as jumlah,
                COALESCE(SUM(akom), 0)    as akom,
                COALESCE(SUM(player), 0)  as player,
                COALESCE(SUM(support), 0) as support,
                COALESCE(SUM(asset), 0)   as asset')
            ->first();

        $fees = $query
            ->with(['tagihan' => fn ($t) => $t->withTrashed()->with([
                'nasabah' => fn ($n) => $n->withTrashed()->select('id', 'nama'),
            ])])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $ringkas = [
            'jumlah'  => (int) $total->jumlah,
            'akom'    => (float) $total->akom,
            'player'  => (float) $total->player,
            'support' => (float) $total->support,
            'asset'   => (float) $total->asset,
        ];
        $ringkas['fee'] = $ringkas['akom'] + $ringkas['player'] + $ringkas['support'] + $ringkas['asset'];

        return view('fee.index', [
            'fees'           => $fees,
            'ringkas'        => $ringkas,
            'q'              => $q,
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