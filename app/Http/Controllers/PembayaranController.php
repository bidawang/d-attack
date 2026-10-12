<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Pembayaran;
use App\Models\SiklusBunga;
use App\Models\Tagihan;
use App\Services\RingkasanPeriode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PembayaranController extends Controller
{
    private const PERSEN_BUNGA = 25;

    public function store(Request $request, RingkasanPeriode $svc)
    {
        $data = $request->validate([
            'tagihan_id'    => [
                'required',
                'integer',
                Rule::exists('tagihan', 'id')->whereNull('deleted_at'),
            ],
            'jumlah_bayar'  => ['required', 'numeric', 'gt:0'],
            'tanggal_bayar' => ['nullable', 'date'],
            'metode'        => ['required', Rule::in(['tunai', 'transfer'])],
            'catatan'       => ['nullable', 'string', 'max:255'],
        ]);

        $pesan = DB::transaction(function () use ($data, $request, $svc) {

            // 1. Ambil tagihan dan kunci barisnya
            $t = Tagihan::with(['siklus', 'pembayaran'])
                ->lockForUpdate()
                ->findOrFail($data['tagihan_id']);

            // 2. Pastikan tagihan masih bisa dibayar
            if ($t->status === 'lunas') {
                throw ValidationException::withMessages([
                    'tagihan_id' => 'Tagihan '.$t->kode.' sudah lunas.',
                ]);
            }

            if ($t->status === 'bersambung') {
                $lanjutan = $t->sambungan_terakhir;
                throw ValidationException::withMessages([
                    'tagihan_id' => 'Tagihan '.$t->kode.' sudah bersambung. Silakan bayar tagihan lanjutan '
                        .($lanjutan ? $lanjutan->kode : '').'.',
                ]);
            }

            $sisaSebelumBayar = round((float) $svc->sisaTagihan($t), 2);
            $jumlahBayar      = round((float) $data['jumlah_bayar'], 2);

            if ($sisaSebelumBayar <= 0) {
                throw ValidationException::withMessages([
                    'tagihan_id' => 'Tagihan '.$t->kode.' sudah lunas.',
                ]);
            }

            // 3. Cek keterlambatan & hitung total kewajiban
            $penagihIdLogin = $request->user()->id;

            $tanggalBayar = ! empty($data['tanggal_bayar'])
                ? Carbon::parse($data['tanggal_bayar'])
                : now();

            $tenggat   = Carbon::parse($t->tenggat_waktu)->endOfDay();
            $terlambat = $tanggalBayar->gt($tenggat);

            $bungaOtomatis = $terlambat
                ? round($sisaSebelumBayar * (self::PERSEN_BUNGA / 100), 2)
                : 0.0;

            $totalKewajiban = round($sisaSebelumBayar + $bungaOtomatis, 2);

            if ($jumlahBayar > $totalKewajiban + 0.005) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Jumlah melebihi sisa tagihan (Rp '
                        .number_format($totalKewajiban, 0, ',', '.').').',
                ]);
            }

            // 4. Jika terlambat, catat bunga ke siklus_bunga LEBIH DULU
            //    supaya pembayaran (termasuk porsi bunga) tidak jadi data fiktif.
            $siklus = $t->siklus->last();

            if ($terlambat) {
                $siklus = SiklusBunga::create([
                    'tagihan_id'            => $t->id,
                    'penagih_id'            => $penagihIdLogin,
                    'siklus_ke'             => $siklus ? ((int) $siklus->siklus_ke + 1) : 1,
                    'tenggat_waktu'         => $tenggat->toDateString(),
                    'sisa_kena_bunga'       => $sisaSebelumBayar,
                    'bayar_sebelum_tenggat' => 0, // sesuaikan bila ada pembayaran sebelum tenggat pada siklus ini
                    'bunga'                 => $bungaOtomatis,
                ]);
            }

            // 5. Simpan pembayaran
            Pembayaran::create([
                'tagihan_id'      => $t->id,
                'siklus_bunga_id' => $siklus?->id,
                'penagih_id'      => $penagihIdLogin,
                'jumlah_bayar'    => $jumlahBayar,
                'tanggal_bayar'   => $tanggalBayar,
                'tahap'           => $siklus ? 'sesudah_bunga' : 'sebelum_bunga',
                'metode'          => $data['metode'],
                'catatan'         => $data['catatan'] ?? null,
            ]);

            // 5b. Hitung ulang jumlah_dibayar dari seluruh pembayaran
            $totalDibayar = round(
                (float) Pembayaran::where('tagihan_id', $t->id)->sum('jumlah_bayar'),
                2
            );

            $t->update(['jumlah_dibayar' => $totalDibayar]);

            // 5c. Catat Fee jika total dibayar >= 60% dari jumlah_hutang
            $batasFee = round((float) $t->jumlah_hutang * 0.60, 2);

            if ($totalDibayar + 0.005 >= $batasFee) {
                $this->catatFee($t->id, $totalDibayar);
            }

            // 6. Tentukan hasil akhir
            $sisaSetelahBayar = round($totalKewajiban - $jumlahBayar, 2);

            // 6a. Lunas (tepat waktu maupun terlambat)
            if ($sisaSetelahBayar <= 0.005) {
                $t->update(['status' => 'lunas']);

                return 'Pembayaran Rp '.number_format($jumlahBayar, 0, ',', '.')
                    .' untuk '.$t->kode
                    .($terlambat
                        ? ' tersimpan (termasuk bunga keterlambatan Rp '
                            .number_format($bungaOtomatis, 0, ',', '.').') dan tagihan dinyatakan lunas.'
                        : ' tersimpan dan tagihan sudah lunas.');
            }

            // 6b. Belum lunas, masih tepat waktu: cukup tampilkan sisa
            if (! $terlambat) {
                return 'Pembayaran Rp '.number_format($jumlahBayar, 0, ',', '.')
                    .' untuk '.$t->kode
                    .' tersimpan. Sisa tagihan Rp '
                    .number_format($sisaSetelahBayar, 0, ',', '.').'.';
            }

            // 6c. Belum lunas dan terlambat: buat tagihan sambungan
            //     sebesar total sisa kewajiban (sisa pokok + bunga)
            $t->update(['status' => 'bersambung']);

            $tagihanBaru = Tagihan::create([
                'kode'            => $this->kodeBaru(),
                'nasabah_id'      => $t->nasabah_id,
                'id_tagihan_awal' => $t->id_tagihan_awal ?? $t->id,
                'penagih_id'      => $penagihIdLogin,
                'jumlah_hutang'   => $sisaSetelahBayar,
                'jumlah_dibayar'  => 0,
                'tanggal_hutang'  => $tanggalBayar->toDateString(),
                'tenggat_waktu'   => $tanggalBayar->copy()->addMonth()->toDateString(),
                'status'          => 'sambungan',
                'keterangan'      => 'Tagihan lanjutan dari '.$t->kode
                    .'. Sisa kewajiban Rp '.number_format($sisaSetelahBayar, 0, ',', '.')
                    .' (termasuk bunga '.self::PERSEN_BUNGA.'% sebesar Rp '
                    .number_format($bungaOtomatis, 0, ',', '.').').',
            ]);

            return 'Pembayaran Rp '.number_format($jumlahBayar, 0, ',', '.')
                .' untuk '.$t->kode
                .' tersimpan. Karena melewati tenggat, sisa Rp '
                .number_format($sisaSetelahBayar, 0, ',', '.')
                .' (termasuk bunga '.self::PERSEN_BUNGA.'%) diteruskan ke tagihan baru '
                .$tagihanBaru->kode.'.';
        });

        return back()->with('ok', $pesan);
    }

    /**
     * Ambil 20% dari total dibayar sebagai fee (disimpan di kolom `fee`),
     * lalu fee tersebut dibagi: akom 50%, player 40%, support 5%, asset 5%.
     * updateOrCreate: 1 tagihan = 1 baris fee, ter-update saat ada cicilan lanjutan.
     */
    private function catatFee(int $tagihanId, float $totalDibayar): void
    {
        $fee     = round($totalDibayar * 0.20, 2);

        $support = round($fee * 0.05, 2);
        $asset   = round($fee * 0.05, 2);
        $player  = round($fee * 0.40, 2);
        // Akom mengambil sisa agar total keempatnya persis = fee
        $akom    = round($fee - $player - $support - $asset, 2);

        Fee::updateOrCreate(
            ['tagihan_id' => $tagihanId],
            compact('fee', 'akom', 'player', 'support', 'asset')
        );
    }

    private function kodeBaru(): string
    {
        $terakhir = Tagihan::withTrashed()->latest('id')->first();
        $nomor    = $terakhir ? ((int) $terakhir->id + 1) : 1;

        return 'TGH-'.str_pad($nomor, 6, '0', STR_PAD_LEFT);
    }
}