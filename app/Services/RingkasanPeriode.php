<?php

namespace App\Services;

use App\Models\Pengaturan;
use App\Models\Tagihan;
use Carbon\CarbonInterface;

class RingkasanPeriode
{
    public float $persenBunga;
public float $ambangFee;
public int $tanggalTenggat;
public int $pembulatanBunga;   // BARU: 1000 = naik ke kelipatan 1000, 0 = tanpa pembulatan

public function __construct()
{
    $p = Pengaturan::query()->pluck('nilai', 'kunci');

    $this->persenBunga     = (float) ($p['persen_bunga'] ?? 25);
    $this->ambangFee       = (float) ($p['ambang_fee'] ?? 60);
    $this->tanggalTenggat  = (int) ($p['tanggal_tenggat'] ?? 8);
    $this->pembulatanBunga = (int) ($p['pembulatan_bunga'] ?? 1000);   // BARU
}

    /** Tanggal tenggat pada bulan tertentu (dipotong ke hari terakhir bila bulan lebih pendek). */
    public function tanggalTenggatPada(CarbonInterface $tanggal): CarbonInterface
    {
        $awal = $tanggal->copy()->startOfMonth();

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

    /**
 * Bunga dari dasar perhitungan: persen bunga, lalu dibulatkan KE ATAS ke kelipatan $pembulatanBunga.
 * Satu-satunya tempat rumus bunga, dipakai juga oleh proses yang menyimpan siklus bunga.
 */
public function hitungBunga(float $dasar): float
{
    $bunga = round(max(0.0, $dasar) * $this->persenBunga / 100, 2);

    if ($this->pembulatanBunga > 0) {
        $bunga = ceil($bunga / $this->pembulatanBunga) * $this->pembulatanBunga;
    }

    return (float) $bunga;
}

    /**
     * Menghitung rincian baris tagihan.
     * Menerima $tenggat (satu tanggal) ATAU array rentang tanggal [$dari, $sampai].
     */
    public function baris(Tagihan $t, CarbonInterface|array $tenggat): array
    {
        $sikluses  = $t->siklus; // sudah terurut siklus_ke
        $terakhir  = $sikluses->last();

        $perSiklus = $t->pembayaran
            ->groupBy(fn ($p) => (int) ($p->siklus_bunga_id ?? 0))
            ->map(fn ($g) => (float) $g->sum('jumlah_bayar'));

        // Cek apakah parameter berupa rentang tanggal [Carbon $dari, Carbon $sampai]
        if (is_array($tenggat) && count($tenggat) === 2) {
    [$dari, $sampai] = $tenggat;
    $s = $sikluses->first(function ($x) use ($dari, $sampai) {
        return $x->tenggat_waktu?->betweenIncluded($dari, $sampai) ?? false;
    });
} else {
    $s = $sikluses->first(
        fn ($x) => $x->tenggat_waktu?->isSameDay($tenggat) ?? false
    );
}

        if ($s) {
            // Tenggat ini/rentang ini sudah dibungakan: pakai angka yang tersimpan di siklus_bunga.
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
            $bunga     = $this->hitungBunga($tagihan);
            $bayar     = 0.0;
            $diproses  = false;
            $bisaBayar = true;
        }

        // Persentase terbayar: perbandingan jumlah_dibayar terhadap jumlah_hutang
        $hutang  = (float) $t->jumlah_hutang;
        $dibayar = (float) $t->jumlah_dibayar;
        $capai   = $hutang > 0 ? round($dibayar / $hutang * 100, 2) : 100.0;

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
            'hutang'     => $hutang,
            'dibayar'    => $dibayar,
            'capai'      => $capai,
        ];
    }
}