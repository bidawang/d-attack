<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use App\Services\RingkasanPeriode;
use Illuminate\Http\Request;

class NasabahController extends Controller
{
    private function aturan(): array
    {
        return [
            'nama'    => ['required', 'string', 'max:100'],
            'no_hp'   => ['nullable', 'string', 'max:20'],
            'alamat'  => ['nullable', 'string', 'max:255'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function index(Request $r)
{
    $q = trim((string) $r->query('q', ''));

    $items = Nasabah::query()
        ->withCount(['tagihan as tagihan_aktif' => fn ($x) => $x->where('status', '!=', 'lunas')])
        ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
            ->where('nama', 'like', "%{$q}%")
            ->orWhere('no_hp', 'like', "%{$q}%")
            ->orWhere('alamat', 'like', "%{$q}%")))
        ->orderBy('nama')
        ->paginate(15) // Bisa disesuaikan jumlah per halamannya
        ->withQueryString();

    return view('nasabah.index', compact('items', 'q'));
}

    public function show(Request $request, Nasabah $nasabah)
{
    $status = $request->query('status', 'semua');

    $queryTagihan = $nasabah->tagihan()
        ->with(['penagih', 'tagihanAwal:id,kode', 'siklus', 'pembayaran.penagih']);

    $counts = [
        'semua'       => (clone $queryTagihan)->count(),
        'bon_gantung' => (clone $queryTagihan)->whereIn('status', ['bon_gantung', 'sambungan'])->count(),
        'berbunga'    => (clone $queryTagihan)->where('status', 'berbunga')->count(),
        'lunas'       => (clone $queryTagihan)->where('status', 'lunas')->count(),
    ];

    match ($status) {
        'bon_gantung' => $queryTagihan->whereIn('status', ['bon_gantung', 'sambungan']),
        'berbunga', 'lunas' => $queryTagihan->where('status', $status),
        default => null,
    };

    $tagihan = $queryTagihan->orderByDesc('tanggal_hutang')
        ->paginate(10)
        ->withQueryString();

    return view('nasabah.show', [
        'nasabah' => $nasabah,
        'tagihan' => $tagihan,
        'status'  => $status,
        'counts'  => $counts,
        'ringkas' => app(RingkasanPeriode::class),
    ]);
}

    public function create()
    {
        return view('nasabah.form', ['item' => new Nasabah(['is_aktif' => true])]);
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->aturan());
        $n = Nasabah::create($d + ['is_aktif' => $r->boolean('is_aktif')]);

        return redirect()->route('nasabah.show', $n)->with('ok', 'Nasabah ditambahkan.');
    }

    public function edit(Nasabah $nasabah)
    {
        return view('nasabah.form', ['item' => $nasabah]);
    }

    public function update(Request $r, Nasabah $nasabah)
    {
        $d = $r->validate($this->aturan());
        $nasabah->update($d + ['is_aktif' => $r->boolean('is_aktif')]);

        return redirect()->route('nasabah.show', $nasabah)->with('ok', 'Data nasabah disimpan.');
    }

    public function destroy(Nasabah $nasabah)
    {
        if ($nasabah->tagihan()->where('status', '!=', 'lunas')->exists()) {
            return back()->with('err', 'Nasabah masih punya tagihan belum lunas, tidak bisa dihapus.');
        }

        $nasabah->delete();

        return redirect()->route('nasabah.index')->with('ok', 'Nasabah dihapus.');
    }
}