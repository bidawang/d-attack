<?php

namespace App\Http\Controllers;

use App\Models\Nasabah;
use App\Models\Tagihan;
use App\Models\User;
use App\Services\RingkasanPeriode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TagihanController extends Controller
{
    private function penagihAktif()
    {
        return User::where('role', 'penagih')->where('is_aktif', true)->orderBy('name')->get();
    }

    private function cekTenggat(string $tgl): void
{
    $svc = app(RingkasanPeriode::class);
    $d   = Carbon::parse($tgl);

    if (! $svc->tanggalTenggatPada($d)->isSameDay($d)) {
        throw ValidationException::withMessages([
            'tenggat_waktu' => 'Tenggat harus tanggal ' . $svc->tanggalTenggat . ' (sesuai pengaturan).',
        ]);
    }
}

    private function aturanPenagih(bool $hanyaAktif)
    {
        $rule = Rule::exists('users', 'id')->where('role', 'penagih')->whereNull('deleted_at');

        return $hanyaAktif ? $rule->where('is_aktif', 1) : $rule;
    }

    private function terkunci(Tagihan $t): bool
    {
        return $t->pembayaran()->exists() || $t->siklus()->exists();
    }

    private function kodeBaru(): string
    {
        $n = (int) Tagihan::withTrashed()->max('id') + 1;

        do {
            $kode = 'TG' . str_pad((string) $n++, 5, '0', STR_PAD_LEFT);
        } while (Tagihan::withTrashed()->where('kode', $kode)->exists());

        return $kode;
    }

    public function create(Request $r)
    {
        $ringkas = app(RingkasanPeriode::class);
        $tenggat = $ringkas->tanggalTenggatPada(now());
        if ($tenggat->isPast()) {
            $tenggat = $ringkas->tanggalTenggatPada(now()->addMonthNoOverflow());
        }

        return view('nasabah.tagihan.form', [
            'item'          => new Tagihan(['nasabah_id' => $r->query('nasabah_id'), 'tanggal_hutang' => now()]),
            'nasabahList'   => Nasabah::where('is_aktif', true)->orderBy('nama')->get(),
            'penagihList'   => $this->penagihAktif(),
            'terkunci'      => false,
            'tenggatDefault' => $tenggat->format('Y-m-d'),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'nasabah_id'     => ['required', Rule::exists('nasabah', 'id')->whereNull('deleted_at')->where('is_aktif', 1)],
            'jumlah_hutang'  => ['required', 'numeric', 'min:1', 'max:999999999999'],
            'tanggal_hutang' => ['required', 'date'],
            'tenggat_waktu'  => ['required', 'date', 'after_or_equal:tanggal_hutang'],
            'penagih_id'     => ['required', $this->aturanPenagih(true)],
            'keterangan'     => ['nullable', 'string', 'max:500'],
        ]);
$this->cekTenggat($d['tenggat_waktu']);
        $t = Tagihan::create($d + ['kode' => $this->kodeBaru(), 'status' => 'bon_gantung']);

        return redirect()->route('nasabah.show', $t->nasabah_id)->with('ok', "Tagihan {$t->kode} dibuat.");
    }

    public function edit(Tagihan $tagihan)
    {
        return view('nasabah.tagihan.form', [
            'item'          => $tagihan->load('nasabah'),
            'nasabahList'   => collect(),
            'penagihList'   => $this->penagihAktif(),
            'terkunci'      => $this->terkunci($tagihan),
            'tenggatDefault' => null,
        ]);
    }

    public function update(Request $r, Tagihan $tagihan)
    {
        $aturan = [
            'penagih_id' => ['required', $this->aturanPenagih(false)],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ];

        // Angka dan tanggal hanya boleh diubah selama belum ada pembayaran / bunga.
        if (! $this->terkunci($tagihan)) {
            $aturan += [
                'jumlah_hutang'  => ['required', 'numeric', 'min:1', 'max:999999999999'],
                'tanggal_hutang' => ['required', 'date'],
                'tenggat_waktu'  => ['required', 'date', 'after_or_equal:tanggal_hutang'],
            ];
        }

        $tagihan->update($r->validate($aturan));
$d = $r->validate($aturan);
if (isset($d['tenggat_waktu'])) {
    $this->cekTenggat($d['tenggat_waktu']);
}
$tagihan->update($d);
        return redirect()->route('nasabah.show', $tagihan->nasabah_id)->with('ok', 'Tagihan disimpan.');
    }

    public function destroy(Tagihan $tagihan)
    {
        if ($this->terkunci($tagihan)) {
            return back()->with('err', 'Tagihan sudah punya pembayaran atau bunga, tidak bisa dihapus.');
        }

        $nasabahId = $tagihan->nasabah_id;
        $tagihan->delete();

        return redirect()->route('nasabah.show', $nasabahId)->with('ok', 'Tagihan dihapus.');
    }
}