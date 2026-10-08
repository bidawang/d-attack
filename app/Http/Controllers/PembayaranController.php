<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
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
             *
             * lockForUpdate() mencegah dua pembayaran bersamaan
             * menggunakan nilai sisa yang sama.
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
            if ($t->status === 'lunas' || $sisaSebelumBayar <= 0) {
                throw ValidationException::withMessages([
                    'tagihan_id' => 'Tagihan '.$t->kode.' sudah lunas.',
                ]);
            }

            /*
             * Jangan boleh membayar lebih besar dari sisa tagihan.
             */
            if ($jumlahBayar > $sisaSebelumBayar + 0.005) {
                throw ValidationException::withMessages([
                    'jumlah_bayar' => 'Jumlah melebihi sisa tagihan (Rp '
                        .number_format($sisaSebelumBayar, 0, ',', '.')
                        .').',
                ]);
            }

            /*
             * ============================================================
             * 4. Tentukan tanggal pembayaran
             * ============================================================
             */
            $tanggalBayar = !empty($data['tanggal_bayar'])
                ? Carbon::parse($data['tanggal_bayar'])
                : now();

            $tenggat = Carbon::parse($t->tenggat_waktu);

            /*
             * ============================================================
             * 5. Tentukan siklus bunga terakhir
             * ============================================================
             */
            $terakhir = $t->siklus->last();

            /*
             * ============================================================
             * 6. Simpan pembayaran aktual
             * ============================================================
             *
             * Ini adalah uang yang benar-benar dibayarkan oleh nasabah.
             *
             * Contoh:
             * Tagihan       = Rp1.000.000
             * Dibayar       = Rp700.000
             *
             * Maka hanya Rp700.000 yang menjadi pembayaran.
             *
             * Rp300.000 sisanya TIDAK dibuat sebagai pembayaran kedua.
             * Sisa tersebut akan menjadi tagihan baru apabila pembayaran
             * dilakukan setelah tenggat.
             */
            Pembayaran::create([
                'tagihan_id'      => $t->id,
                'siklus_bunga_id' => $terakhir?->id,
                'penagih_id'      => $request->user()->id,
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
             * 7. Hitung sisa setelah pembayaran
             * ============================================================
             */
            $sisaSetelahBayar = round(
                $sisaSebelumBayar - $jumlahBayar,
                2
            );

            /*
             * ============================================================
             * 8. CEK PEMBAYARAN TERLAMBAT
             * ============================================================
             *
             * Tagihan baru hanya dibuat apabila:
             *
             * - tanggal pembayaran > tenggat
             * - masih ada sisa tagihan
             *
             * Contoh:
             *
             * Tagihan       = Rp1.000.000
             * Dibayar       = Rp700.000
             * Sisa          = Rp300.000
             * Tenggat       = 10 Oktober
             * Bayar         = 12 Oktober
             *
             * Karena 12 Oktober > 10 Oktober:
             *
             * Sisa          = Rp300.000
             * Bunga 25%     = Rp75.000
             * Tagihan baru   = Rp375.000
             */
            if (
                $tanggalBayar->gt($tenggat)
                && $sisaSetelahBayar > 0.005
            ) {
                /*
                 * Persentase bunga.
                 *
                 * Saat ini sesuai aturan yang diminta:
                 * 25%
                 */
                $persenBunga = 25;

                /*
                 * Hitung bunga dari sisa setelah pembayaran.
                 */
                $bunga = round(
                    $sisaSetelahBayar * ($persenBunga / 100),
                    2
                );

                /*
                 * Total tagihan baru:
                 *
                 * sisa + bunga
                 */
                $totalTagihanBaru = round(
                    $sisaSetelahBayar + $bunga,
                    2
                );

                /*
                 * ========================================================
                 * 9. Buat keterangan tagihan baru
                 * ========================================================
                 *
                 * Keterangan dibuat lengkap supaya ketika melihat
                 * tagihan baru bisa diketahui asal perhitungannya.
                 */
                $keteranganBaru =
                    'Tagihan lanjutan dari '.$t->kode.'. '
                    .'Total tagihan sebelumnya Rp'
                    .number_format($sisaSebelumBayar, 0, ',', '.')
                    .'. '
                    .'Sudah dibayar Rp'
                    .number_format($jumlahBayar, 0, ',', '.')
                    .'. '
                    .'Sisa Rp'
                    .number_format($sisaSetelahBayar, 0, ',', '.')
                    .'. '
                    .'Karena pembayaran dilakukan setelah tenggat '
                    .$tenggat->format('d-m-Y')
                    .', sisa dikenakan bunga '.$persenBunga.'%. '
                    .'Bunga Rp'
                    .number_format($bunga, 0, ',', '.')
                    .' (Rp'
                    .number_format($sisaSetelahBayar, 0, ',', '.')
                    .' × '.$persenBunga.'%). '
                    .'Total tagihan baru Rp'
                    .number_format($totalTagihanBaru, 0, ',', '.')
                    .'.';

                /*
                 * ========================================================
                 * 10. Tandai tagihan lama sebagai lunas
                 * ========================================================
                 *
                 * Sisa hutang tidak hilang.
                 *
                 * Sisa tersebut sudah dipindahkan menjadi tagihan baru.
                 */
                $t->update([
                    'status' => 'lunas',
                ]);

                /*
                 * ========================================================
                 * 11. Buat tagihan baru
                 * ========================================================
                 */
                $tagihanBaru = Tagihan::create([
                    'nasabah_id'     => $t->nasabah_id,
                    'jumlah_hutang'  => $totalTagihanBaru,
                    'tanggal_hutang' => $tanggalBayar->toDateString(),
                    'tenggat_waktu'  => $tanggalBayar
                        ->copy()
                        ->addMonth()
                        ->toDateString(),
                    'penagih_id'     => $t->penagih_id,
                    'keterangan'     => $keteranganBaru,
                    'kode'           => $this->kodeBaru(),
                    'status'         => 'bon_gantung',
                ]);

                return 'Pembayaran Rp'
                    .number_format($jumlahBayar, 0, ',', '.')
                    .' untuk '.$t->kode
                    .' tersimpan. '
                    .'Tagihan lama dinyatakan lunas dan sisa Rp'
                    .number_format($sisaSetelahBayar, 0, ',', '.')
                    .' diteruskan menjadi tagihan baru '
                    .$tagihanBaru->kode
                    .' sebesar Rp'
                    .number_format($totalTagihanBaru, 0, ',', '.')
                    .' setelah bunga '.$persenBunga.'%.';
            }

            /*
             * ============================================================
             * 12. Jika tidak terlambat dan sudah lunas
             * ============================================================
             */
            if ($sisaSetelahBayar <= 0.005) {
                dd('tidak terlambat dan sudah lunas');
                $t->update([
                    'status' => 'lunas',
                ]);

                return 'Pembayaran Rp'
                    .number_format($jumlahBayar, 0, ',', '.')
                    .' untuk '.$t->kode
                    .' tersimpan dan tagihan sudah lunas.';
            }

            /*
             * ============================================================
             * 13. Pembayaran sebagian dan belum melewati tenggat
             * ============================================================
             */
            return 'Pembayaran Rp'
                .number_format($jumlahBayar, 0, ',', '.')
                .' untuk '.$t->kode
                .' tersimpan. Sisa tagihan Rp'
                .number_format($sisaSetelahBayar, 0, ',', '.')
                .'.';
        });

        return back()->with('ok', $pesan);
    }

    /**
     * Membuat kode tagihan baru.
     *
     * CATATAN:
     * Jika method kodeBaru() milik TagihanController saat ini memiliki
     * format khusus, sebaiknya logic-nya dipindahkan ke service bersama
     * agar PembayaranController dan TagihanController menggunakan
     * generator kode yang sama.
     */
    private function kodeBaru(): string
    {
        $terakhir = Tagihan::withTrashed()
            ->latest('id')
            ->first();

        $nomor = $terakhir ? ((int) $terakhir->id + 1) : 1;

        return 'TGH-'.str_pad($nomor, 6, '0', STR_PAD_LEFT);
    }
}