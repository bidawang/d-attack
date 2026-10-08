<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PenagihController extends Controller
{
    /** Hanya mengambil user ber-role penagih, jadi admin tidak pernah tersentuh. */
    private function cari(string $id): User
    {
        return User::where('role', 'penagih')->findOrFail($id);
    }

    private function aturan(?int $id = null): array
    {
        return [
            'name'  => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($id)],
            'no_hp' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function index(Request $r)
    {
        $q = trim((string) $r->query('q', ''));

        $items = User::where('role', 'penagih')
            ->withCount(['tagihan as tagihan_aktif' => fn ($x) => $x->where('status', '!=', 'lunas')])
            ->when($q !== '', fn ($x) => $x->where(fn ($w) => $w
                ->where('name', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->orWhere('no_hp', 'like', "%{$q}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('penagih.index', compact('items', 'q'));
    }

    public function create()
    {
        return view('penagih.form', ['item' => new User(['is_aktif' => true])]);
    }

    public function store(Request $r)
    {
        $d = $r->validate($this->aturan() + ['password' => ['required', 'string', 'min:8']]);

        User::create($d + ['role' => 'penagih', 'is_aktif' => $r->boolean('is_aktif')]);

        return redirect()->route('penagih.index')->with('ok', 'Penagih ditambahkan.');
    }

    public function edit(string $penagih)
    {
        return view('penagih.form', ['item' => $this->cari($penagih)]);
    }

    public function update(Request $r, string $penagih)
    {
        $u = $this->cari($penagih);

        $d = $r->validate($this->aturan($u->id) + ['password' => ['nullable', 'string', 'min:8']]);

        if (blank($d['password'] ?? null)) {
            unset($d['password']);
        }

        $u->update($d + ['is_aktif' => $r->boolean('is_aktif')]);

        return redirect()->route('penagih.index')->with('ok', 'Data penagih disimpan.');
    }

    public function aktif(string $penagih)
    {
        $u = $this->cari($penagih);
        $u->update(['is_aktif' => ! $u->is_aktif]);

        return back()->with('ok', $u->is_aktif ? 'Penagih diaktifkan.' : 'Penagih dinonaktifkan.');
    }

    public function destroy(string $penagih)
    {
        $u = $this->cari($penagih);

        if ($u->tagihan()->where('status', '!=', 'lunas')->exists()) {
            return back()->with('err', 'Penagih masih memegang tagihan belum lunas. Pindahkan dulu tagihannya.');
        }

        $u->delete(); // soft delete: riwayat pembayaran tetap utuh

        return redirect()->route('penagih.index')->with('ok', 'Penagih dihapus.');
    }
}