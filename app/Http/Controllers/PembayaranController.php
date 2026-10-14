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

            // Hitung bunga mentah lalu bulatkan ke atas (ceiling) ke kelipatan 1000 terdekat jika terlambat
            $bungaOtomatis = 0.0;
            if ($terlambat) {
                $bungaMentah = $sisaSebelumBayar * (self::PERSEN_BUNGA / 100);
                $bungaOtomatis = ceil($bungaMentah / 1000) * 1000;
            }

            $totalKewajiban = round($sisaSebelumBayar + $bungaOtomatis, 2);

            if ($terlambat) {
                $batasMaksimal = ceil($totalKewajiban / 1000) * 1000;
            } else {
                $batasMaksimal = ceil($sisaSebelumBayar / 1000) * 1000;
            }

            if ($jumlahBayar > $batasMaksimal + 0.005) {
                $labelMax = $terlambat ? 'total kewajiban (sisa tagihan + bunga)' : 'sisa tagihan';
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Jumlah melebihi '.$labelMax.' (Rp '
                        .number_format($batasMaksimal, 0, ',', '.').').',
                ]);
            }

            // 4. Jika terlambat, catat bunga ke siklus_bunga LEBIH DULU
            $siklus = $t->siklus->last();

            if ($terlambat) {
                $siklus = SiklusBunga::create([
                    'tagihan_id'            => $t->id,
                    'penagih_id'            => $penagihIdLogin,
                    'siklus_ke'             => $siklus ? ((int) $siklus->siklus_ke + 1) : 1,
                    'tenggat_waktu'         => $tenggat->toDateString(),
                    'sisa_kena_bunga'       => $sisaSebelumBayar,
                    'bayar_sebelum_tenggat' => 0, 
                    'bunga'                 => $bungaOtomatis,
                ]);

                // Hitung total seluruh bunga dari database untuk tagihan ini secara akurat
                $totalBungaTagihan = (float) SiklusBunga::where('tagihan_id', $t->id)->sum('bunga');
                $this->catatFee($t->id, $totalBungaTagihan);
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

            // 6. Tentukan hasil akhir (Sisa setelah pembayaran dikurangi dari total kewajiban)
            $sisaSetelahBayar = round($totalKewajiban - $jumlahBayar, 2);

            // 6a. Lunas (tepat waktu maupun terlambat dan dibayar lunas beserta bunganya)
            if ($sisaSetelahBayar <= 0.005) {
                $t->update(['status' => 'lunas']);

                return 'Pembayaran Rp '.number_format($jumlahBayar, 0, ',', '.')
                    .' untuk '.$t->kode
                    .($terlambat
                        ? ' tersimpan (termasuk pembayaran bunga keterlambatan Rp '
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

            // 6c. Belum lunas dan terlambat (bayar sebagian saat telat): buat tagihan sambungan
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
     * Ambil 20% dari total bunga sebagai fee,
     * lalu bagi fee tersebut: akom 50%, player 40%, support 5%, asset 5%.
     */
    private function catatFee(int $tagihanId, float $totalBunga): void
    {
        $fee     = round($totalBunga * 0.20, 2);

        $support = round($fee * 0.05, 2);
        $asset   = round($fee * 0.05, 2);
        $player  = round($fee * 0.40, 2);
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