<?php

namespace App\Http\Controllers;

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

            /*
             * ============================================================
             * 1. Ambil tagihan dan kunci barisnya
             * ============================================================
             */
            $t = Tagihan::with([
                'siklus',
                'pembayaran',
            ])
                ->lockForUpdate()
                ->findOrFail($data['tagihan_id']);

            /*
             * ============================================================
             * 2. Hitung sisa tagihan sebelum pembayaran
             * ============================================================
             */
            $sisaSebelumBayar = round(
                (float) $svc->sisaTagihan($t),
                2
            );

            $jumlahBayar = round(
                (float) $data['jumlah_bayar'],
                2
            );

            /*
             * ============================================================
             * 3. Pastikan tagihan masih bisa dibayar
             * ============================================================
             */
            $penagihIdLogin = $request->user()->id;
            if ($t->status === 'lunas' || $sisaSebelumBayar <= 0) {
                throw ValidationException::withMessages([
                    'tagihan_id' => 'Tagihan '.$t->kode.' sudah lunas.',
                ]);
            }

            $tanggalBayar = !empty($data['tanggal_bayar'])
                ? Carbon::parse($data['tanggal_bayar'])
                : now();

            $tenggat = Carbon::parse($t->tenggat_waktu)->endOfDay();
            $terlambat = $tanggalBayar->gt($tenggat);

            // Jika terlambat, total kewajiban mencakup sisa + bunga 25% dari sisa tersebut
            $persenBunga = 25;
            $bungaOtomatis = $terlambat ? round($sisaSebelumBayar * ($persenBunga / 100), 2) : 0;
            $totalKewajibanTelat = round($sisaSebelumBayar + $bungaOtomatis, 2);

            $sisaAktifUntukValidasi = $terlambat ? $totalKewajibanTelat : $sisaSebelumBayar;

            if ($jumlahBayar > $sisaAktifUntukValidasi + 0.005) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Jumlah melebihi sisa tagihan (Rp '
                        .number_format($sisaAktifUntukValidasi, 0, ',', '.')
                        .').',
                ]);
            }

            /*
             * ============================================================
             * 4. Tentukan siklus bunga terakhir
             * ============================================================
             */
            $terakhir = $t->siklus->last();

            /*
             * ============================================================
             * 5. Simpan pembayaran aktual
             * ============================================================
             */
            Pembayaran::create([
                'tagihan_id'      => $t->id,
                'siklus_bunga_id' => $terakhir?->id,
                'penagih_id'      => $penagihIdLogin,
                'jumlah_bayar'    => $jumlahBayar,
                'tanggal_bayar'   => $tanggalBayar,
                'tahap'           => $terakhir
                    ? 'sesudah_bunga'
                    : 'sebelum_bunga',
                'metode'          => $data['metode'],
                'catatan'         => $data['catatan'] ?? null,
            ]);

            /*
             * ============================================================
             * 6. CEK KONDISI Keterlambatan & Sisa Pembayaran
             * ============================================================
             */
            if ($terlambat) {
                // Cek apakah pembayaran menutupi seluruh total kewajiban (termasuk bunganya)
                if ($jumlahBayar >= $totalKewajibanTelat - 0.005) {
                    // Jika bayar >= total kewajiban telat (misal bayar 1.250.000), maka lunas!
                    $t->update([
                        'status' => 'lunas',
                    ]);

                    return 'Pembayaran Rp '
                        .number_format($jumlahBayar, 0, ',', '.')
                        .' untuk '.$t->kode
                        .' tersimpan (termasuk bunga keterlambatan) dan tagihan dinyatakan lunas.';
                } else {
                    // Jika bayar kurang dari total kewajiban (misal sisa 1jt dibayar 1jt, bunganya 250rb belum terbayar)
                    // Sisa yang belum terbayar dari total kewajiban akan menjadi basis untuk tagihan sambungan baru
                    $sisaBelumLunas = round($totalKewajibanTelat - $jumlahBayar, 2);

                    // Catat ke tabel siklus_bunga sesuai ketentuan (tagihan_id, siklus_ke, dan bunga)
                    $siklusKeBaru = $terakhir ? ((int) $terakhir->siklus_ke + 1) : 1;

                    SiklusBunga::create([
                        'tagihan_id' => $t->id,
                        'siklus_ke'  => $siklusKeBaru,
                        'bunga'      => $bungaOtomatis,
                    ]);

                    $keteranganBaru = 'Tagihan lanjutan dari '.$t->kode.'. Sisa kewajiban Rp '
                        .number_format($sisaBelumLunas, 0, ',', '.')
                        .' (termasuk bunga '.$persenBunga.'% sebesar Rp '.number_format($bungaOtomatis, 0, ',', '.').').';

                    // Ubah status tagihan lama menjadi 'bersambung'
                    $t->update([
                        'status' => 'bersambung',
                    ]);

                    $idTagihanAwal = $t->id_tagihan_awal ?? $t->id;

                    // Buat tagihan baru (sambungan) sebesar sisa yang belum lunas
                    $tagihanBaru = Tagihan::create([
                        'nasabah_id'      => $t->nasabah_id,
                        'id_tagihan_awal' => $idTagihanAwal,
                        'jumlah_hutang'   => $sisaBelumLunas,
                        'tanggal_hutang'  => $tanggalBayar->toDateString(),
                        'tenggat_waktu'   => $tanggalBayar
                            ->copy()
                            ->addMonth()
                            ->toDateString(),
                        'penagih_id'      => $penagihIdLogin,
                        'keterangan'      => $keteranganBaru,
                        'kode'            => $this->kodeBaru(),
                        'status'          => 'sambungan',
                    ]);

                    return 'Pembayaran Rp '
                        .number_format($jumlahBayar, 0, ',', '.')
                        .' untuk '.$t->kode
                        .' tersimpan. Karena melewati tenggat, sisa sebesar Rp '.number_format($sisaBelumLunas, 0, ',', '.')
                        .' (termasuk bunga 25%) diteruskan ke tagihan baru '
                        .$tagihanBaru->kode.'.';
                }
            }

            /*
             * ============================================================
             * Kasus B: Pembayaran TEPAT WAKTU (sebelum/pas tenggat)
             * ============================================================
             */
            $sisaSetelahBayar = round($sisaSebelumBayar - $jumlahBayar, 2);

            if ($sisaSetelahBayar <= 0.005) {
                $t->update([
                    'status' => 'lunas',
                ]);

                return 'Pembayaran Rp '
                    .number_format($jumlahBayar, 0, ',', '.')
                    .' untuk '.$t->kode
                    .' tersimpan dan tagihan sudah lunas.';
            }

            return 'Pembayaran Rp '
                .number_format($jumlahBayar, 0, ',', '.')
                .' untuk '.$t->kode
                .' tersimpan. Sisa tagihan Rp '
                .number_format($sisaSetelahBayar, 0, ',', '.')
                .'.';
        });

        return back()->with('ok', $pesan);
    }

    private function kodeBaru(): string
    {
        $terakhir = Tagihan::withTrashed()
            ->latest('id')
            ->first();

        $nomor = $terakhir ? ((int) $terakhir->id + 1) : 1;

        return 'TGH-'.str_pad($nomor, 6, '0', STR_PAD_LEFT);
    }
}