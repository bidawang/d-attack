<?php

namespace App\Services;

use App\Models\FeeKomponen;
use App\Models\Pengaturan;
use App\Models\Tagihan;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * Menghitung angka satu tagihan pada satu tenggat (satu baris di beranda).
 *
 * Istilah di tabel:
 *  - bayar_seb : total bayar sebelum tenggat
 *  - tagihan   : sisa hutang yang kena bunga = hutang (awal periode) - bayar_seb
 *  - bunga     : tagihan x persen_bunga          ("final")
 *  - bayar     : total bayar nasabah sesudah pembungaan
 *  - total     : tagihan - bayar + bunga
 *  - capai     : bayar / tagihan (acuan ambang fee 60%)
 */
class RingkasanPeriode
{
    public float $persenBunga;
    public float $ambangFee;
    public float $persenFee;
    public float $pembulatan;
    public int $tanggalTenggat;
    public Collection $komponen;

    public function __construct()
    {
        $p = Pengaturan::query()->pluck('nilai', 'kunci');

        $this->persenBunga    = (float) ($p['persen_bunga'] ?? 25);
        $this->ambangFee      = (float) ($p['ambang_fee'] ?? 60);
        $this->persenFee      = (float) ($p['persen_fee'] ?? 20);
        $this->pembulatan     = (float) ($p['pembulatan'] ?? 1000);
        $this->tanggalTenggat = (int) ($p['tanggal_tenggat'] ?? 8);

        $this->komponen = FeeKomponen::query()->where('is_aktif', true)->orderBy('urutan')->get();
    }

    /** Tanggal tenggat pada bulan tertentu (dipotong ke hari terakhir bila bulan lebih pendek). */
    public function tanggalTenggatPada(CarbonInterface $bulan): CarbonInterface
    {
        $awal = $bulan->copy()->startOfMonth();

        return $awal->copy()->day(min($this->tanggalTenggat, $awal->daysInMonth));
    }

    /** Sisa yang masih harus dibayar: hutang + bunga yang sudah diproses - semua pembayaran. */
    public function sisaTagihan(Tagihan $t): float
    {
        return round(
            (float) $t->jumlah_hutang
            + (float) $t->siklus->sum('bunga')
            - (float) $t->pembayaran->sum('jumlah_bayar'),
            2
        );
    }

    public function baris(Tagihan $t, CarbonInterface $tenggat): array
    {
        $sikluses  = $t->siklus; // sudah terurut siklus_ke
        $terakhir  = $sikluses->last();
        $perSiklus = $t->pembayaran
            ->groupBy(fn ($p) => (int) ($p->siklus_bunga_id ?? 0))
            ->map(fn ($g) => (float) $g->sum('jumlah_bayar'));

        $s = $sikluses->first(fn ($x) => $x->tenggat_waktu->isSameDay($tenggat));

        if ($s) {
            // Tenggat ini sudah dibungakan: pakai angka yang tersimpan di siklus_bunga.
            $tagihan    = (float) $s->sisa_kena_bunga;
            $bayarSeb   = (float) $s->bayar_sebelum_tenggat;
            $bunga      = (float) $s->bunga;
            $bayar      = (float) $perSiklus->get($s->id, 0.0);
            $diproses   = true;
            $bisaBayar  = $terakhir && $terakhir->id === $s->id;
        } else {
            // Belum dibungakan: bunga hanya perkiraan dari sisa saat ini.
            $awal      = $terakhir
                ? (float) $terakhir->sisa_kena_bunga + (float) $terakhir->bunga
                : (float) $t->jumlah_hutang;
            $bayarSeb  = (float) $perSiklus->get($terakhir?->id ?? 0, 0.0);
            $tagihan   = max(0.0, $awal - $bayarSeb);
            $bunga     = round($tagihan * $this->persenBunga / 100, 2);
            $bayar     = 0.0;
            $diproses  = false;
            $bisaBayar = true;
        }

        $fee  = 0.0;
        $bagi = [];
        if ($diproses && $tagihan > 0 && $bayar > 0 && $bayar + 0.005 >= $this->ambangFee / 100 * $tagihan) {
            $r    = max(1.0, $this->pembulatan);
            $fee  = round($this->persenFee / 100 * $bayar / $r) * $r;
            $bagi = $this->pecahFee($fee);
        }

        $lunas = $t->status === 'lunas';

        return [
            'id'         => $t->id,
            'kode'       => $t->kode,
            'nama'       => $t->nasabah->nama ?? '(nasabah dihapus)',
            'nasabah_id' => $t->nasabah_id,
            'diproses'   => $diproses,
            'lunas'      => $lunas,
            'bisa_bayar' => (bool) $bisaBayar && ! $lunas,
            'sisa_bayar' => max(0.0, $this->sisaTagihan($t)),
            'bayar_seb'  => $bayarSeb,
            'tagihan'    => $tagihan,
            'bunga'      => $bunga,
            'bayar'      => $bayar,
            'total'      => round($tagihan - $bayar + $bunga, 2),
            'capai'      => $tagihan > 0 ? $bayar / $tagihan * 100 : 100.0,
            'fee'        => $fee,
            'bagi'       => $bagi,
        ];
    }

    /** Bagi fee ke komponen; tiap bagian dibulatkan, selisih pembulatan masuk ke akomodasi. */
    public function pecahFee(float $fee): array
    {
        if ($this->komponen->isEmpty()) {
            return [];
        }

        $r         = max(1.0, $this->pembulatan);
        $penampung = $this->komponen->firstWhere('kode', 'akomodasi')?->kode ?? $this->komponen->first()->kode;
        $bagi      = [];
        $lain      = 0.0;

        foreach ($this->komponen as $k) {
            if ($k->kode === $penampung) {
                continue;
            }
            $bagi[$k->kode] = round($fee * (float) $k->persen / 100 / $r) * $r;
            $lain += $bagi[$k->kode];
        }
        $bagi[$penampung] = max(0.0, $fee - $lain);

        // urutkan sesuai urutan komponen
        return $this->komponen->mapWithKeys(fn ($k) => [$k->kode => $bagi[$k->kode]])->all();
    }
}
