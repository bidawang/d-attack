<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Tagihan;
use App\Services\RingkasanPeriode;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class BerandaController extends Controller
{
    /** Relasi yang selalu dimuat untuk setiap tagihan (dipakai RingkasanPeriode::baris juga). */
    private const RELASI = ['nasabah:id,nama', 'tagihanAwal:id,kode', 'siklus', 'pembayaran'];

    public function index(Request $request, RingkasanPeriode $svc)
    {
        $user = $request->user();

        if (! $user) {
            return view('auth.login');
        }

        [$dari, $sampai] = $this->rentangTanggalDipilih($request);

        $tgDari   = $dari->format('Y-m-d');
        $tgSampai = $sampai->format('Y-m-d');

        $tenggat = $svc->tanggalTenggatPada($dari);
        $tab     = $request->query('tab', 'aktif');

        // Tagihan yang tenggatnya (atau tenggat siklus bunganya) jatuh pada rentang
        $rawTagihan = Tagihan::query()
            ->with(self::RELASI)
            ->where(function ($q) use ($tgDari, $tgSampai) {
                $q->whereBetween('tenggat_waktu', [$tgDari, $tgSampai])
                  ->orWhereHas('siklus', fn ($s) => $s->whereBetween('tenggat_waktu', [$tgDari, $tgSampai]));
            })
            ->get();

        // Telusuri id_tagihan_awal sampai tagihan paling awal (akar), lalu kumpulkan seluruh rantainya.
        // Satu rantai = satu baris di beranda, yang ditampilkan adalah tagihan terakhir dalam rantai.
        $akarDiproses      = [];
        $rantaiPerTerakhir = []; // id tagihan terakhir => Collection tagihan (urut dari akar)
        $terakhirList      = collect();

        foreach ($rawTagihan as $t) {
            $akar = $this->cariAkar($t);

            if (isset($akarDiproses[$akar->id])) {
                continue;
            }
            $akarDiproses[$akar->id] = true;

            $rantai   = $this->susunRantai($akar);
            $terakhir = $rantai->last();

            $rantaiPerTerakhir[$terakhir->id] = $rantai;
            $terakhirList->push($terakhir);
        }

        $tagihan = $terakhirList
            ->sortBy(fn ($t) => mb_strtolower($t->nasabah->nama ?? '').'|'.$t->kode)
            ->values();

        $idRantaiSemua = collect($rantaiPerTerakhir)
            ->flatMap(fn (Collection $r) => $r->pluck('id'))
            ->unique()
            ->values()
            ->all();

        // Fee untuk seluruh tagihan di semua rantai (diambil sekali)
        $fees = Fee::query()
            ->whereIn('tagihan_id', $idRantaiSemua)
            ->get()
            ->keyBy('tagihan_id');

        // Bon gantung: tagihan lain milik nasabah yang tenggatnya setelah rentang.
        // Tagihan yang sudah menjadi bagian rantai di atas dikecualikan agar tidak terhitung dua kali.
        $bon = Tagihan::query()
            ->with(['siklus', 'pembayaran'])
            ->whereIn('nasabah_id', $tagihan->pluck('nasabah_id')->unique())
            ->whereNotIn('id', $idRantaiSemua)
            ->where('status', '!=', 'lunas')
            ->whereDate('tenggat_waktu', '>', $tgSampai)
            ->get()
            ->groupBy('nasabah_id')
            ->map(fn ($g) => (float) $g->sum(fn ($t) => max(0.0, $svc->sisaTagihan($t))));

        $sudahDipakai = [];
        $allRows = $tagihan->map(function ($t) use ($svc, $dari, $sampai, $bon, $fees, $rantaiPerTerakhir, &$sudahDipakai) {
            $r = $svc->baris($t, [$dari, $sampai]);

            $r['bon'] = isset($sudahDipakai[$t->nasabah_id]) ? 0.0 : (float) ($bon[$t->nasabah_id] ?? 0.0);
            $sudahDipakai[$t->nasabah_id] = true;

            $r['total_semua'] = round($r['tagihan'] + $r['bunga'] + $r['bon'], 2);
            $r['sisa']        = round($r['total_semua'] - $r['bayar'], 2);
            $r['finish']      = $r['sisa'] <= 0.004;

            // Riwayat rantai (akar -> terakhir), ringkasan, dan fee gabungan seluruh rantai
            $rt = $this->bangunRantai($rantaiPerTerakhir[$t->id], $svc, $fees);

            $r['rantai']  = $rt['items'];
            $r['ringkas'] = $rt['ringkas'];
            $r['bagi']    = $rt['bagi'];
            $r['fee']     = $rt['fee'];

            if ($r['lunas'] || $t->status === 'lunas') {
                $r['status_tab'] = 'lunas';
            } elseif ($t->status === 'bon_gantung') {
                $r['status_tab'] = 'bon_gantung';
            } else {
                $r['status_tab'] = 'aktif';
            }

            $r['status']         = $t->status;
            $r['is_sambungan']   = ! is_null($t->id_tagihan_awal);
            $r['asal_kode']      = $t->tagihanAwal?->kode;
            $r['tanggal_hutang'] = $this->fmt($t->tanggal_hutang);
            $r['tanggal_hutang_raw'] = $this->fmt($t->tanggal_hutang, 'Y-m-d');
            $r['keterangan']     = $t->keterangan;
            $r['semua_kode']     = implode(' ', array_column($rt['items'], 'kode'));
            // Kirim tanggal tenggat asli untuk perhitungan dinamis di form pembayaran
            $r['tenggat_waktu']  = $this->fmt($t->tenggat_waktu, 'Y-m-d');

            return $r;
        });

        $counts = [
            'aktif'       => $allRows->where('status_tab', '!=', 'lunas')->count(),
            'bon_gantung' => $allRows->where('status_tab', 'bon_gantung')->count(),
            'lunas'       => $allRows->where('status_tab', 'lunas')->count(),
            'semua'       => $allRows->count(),
        ];

        $opsiUrut = $this->opsiUrut($user->isAdmin());
$urut     = (string) $request->query('urut', 'nama');
$arah     = strtolower((string) $request->query('arah', 'asc'));

if (! isset($opsiUrut[$urut])) {
    $urut = 'nama';
}
if (! in_array($arah, ['asc', 'desc'], true)) {
    $arah = 'asc';
}

$rows = $allRows->filter(function ($r) use ($tab) {
    return match ($tab) {
        'bon_gantung' => $r['status_tab'] === 'bon_gantung',
        'lunas'       => $r['status_tab'] === 'lunas',
        'semua'       => true,
        default       => $r['status_tab'] !== 'lunas',
    };
})->values();

$rows = $this->urutkan($rows, $opsiUrut[$urut]['nilai'], $arah);

        $sum = [
            'tagihan' => $rows->sum('tagihan'),
            'bayar'   => $rows->sum('bayar'),
            'total'   => $rows->sum('total'),
            'fee'     => $rows->sum('fee'),
        ];

        return view('beranda', [
            'user'           => $user,
            'admin'          => $user->isAdmin(),
            'bulan'          => $dari->format('Y-m'),
            'dari_tanggal'   => $dari->format('Y-m-d'),
            'sampai_tanggal' => $sampai->format('Y-m-d'),
            'tenggat'        => $tenggat,
            'tanggal'        => $svc->tanggalTenggat,
            'rows'           => $rows,
            'sum'            => $sum,
            'ambang'         => $svc->ambangFee,
            'tab'            => $tab,
            'counts'         => $counts,
            'urut'      => $urut,
'arah'      => $arah,
'opsiUrut'  => array_map(fn ($o) => $o['label'], $opsiUrut),
        ]);
    }

    /** Naik lewat id_tagihan_awal sampai tagihan paling awal (dengan pengaman siklus melingkar). */
    private function cariAkar(Tagihan $t): Tagihan
    {
        $akar    = $t;
        $dilihat = [$t->id => true];

        while ($akar->id_tagihan_awal && ! isset($dilihat[$akar->id_tagihan_awal])) {
            $induk = Tagihan::with(self::RELASI)->find($akar->id_tagihan_awal);

            if (! $induk) {
                break;
            }

            $akar = $induk;
            $dilihat[$akar->id] = true;
        }

        return $akar;
    }

    /** Kumpulkan akar beserta seluruh turunannya (sambungan, sambungan dari sambungan, dst), urut id. */
    private function susunRantai(Tagihan $akar): Collection
    {
        $rantai   = collect([$akar->id => $akar]);
        $frontier = [$akar->id];

        while ($frontier) {
            $anak = Tagihan::with(self::RELASI)
                ->whereIn('id_tagihan_awal', $frontier)
                ->whereNotIn('id', $rantai->keys()->all())
                ->get();

            if ($anak->isEmpty()) {
                break;
            }

            foreach ($anak as $a) {
                $rantai->put($a->id, $a);
            }

            $frontier = $anak->pluck('id')->all();
        }

        return $rantai->sortBy('id')->values();
    }

    /** Susun data riwayat tiap tagihan dalam rantai + ringkasan + fee gabungan. */
    private function bangunRantai(Collection $rantai, RingkasanPeriode $svc, Collection $fees): array
    {
        $terakhirId = $rantai->last()->id;
        $akarId     = $rantai->first()->id;

        $items      = [];
        $bungaTotal = 0.0;
        $bayarTotal = 0.0;
        $feeTotal   = 0.0;
        $bagiTotal  = ['akom' => 0.0, 'player' => 0.0, 'support' => 0.0, 'asset' => 0.0];

        foreach ($rantai as $x) {
            $siklus = $x->siklus->sortBy('siklus_ke')->values();

            $pembayaran = $x->pembayaran
                ->sortBy(fn ($p) => sprintf('%012d-%010d', Carbon::parse($p->tanggal_bayar)->timestamp, $p->id))
                ->values();

            $siklusById = $siklus->keyBy('id');

            [$fee, $bagi] = $this->nilaiFee($fees->get($x->id));

            $bungaX  = round((float) $siklus->sum('bunga'), 2);
            $bayarX  = round((float) $pembayaran->sum('jumlah_bayar'), 2);

            $bungaTotal += $bungaX;
            $bayarTotal += $bayarX;
            $feeTotal   += $fee;
            foreach ($bagi as $k => $v) {
                $bagiTotal[$k] += $v;
            }

            $items[] = [
                'id'             => $x->id,
                'kode'           => $x->kode,
                'status'         => $x->status,
                'tanggal_hutang' => $this->fmt($x->tanggal_hutang),
                'tenggat'        => $this->fmt($x->tenggat_waktu),
                'hutang'         => (float) $x->jumlah_hutang,
                'dibayar'        => $bayarX,
                'bunga'          => $bungaX,
                'sisa'           => max(0.0, (float) $svc->sisaTagihan($x)),
                'keterangan'     => $x->keterangan,
                'is_akar'        => $x->id === $akarId,
                'is_terakhir'    => $x->id === $terakhirId,
                'fee'            => $fee,
                'bagi'           => $bagi,
                'siklus'         => $siklus->map(fn ($s) => [
                    'ke'         => (int) $s->siklus_ke,
                    'tenggat'    => $this->fmt($s->tenggat_waktu),
                    'kena_bunga' => (float) $s->sisa_kena_bunga,
                    'bayar_seb'  => (float) $s->bayar_sebelum_tenggat,
                    'bunga'      => (float) $s->bunga,
                ])->all(),
                'pembayaran'     => $pembayaran->map(fn ($p) => [
                    'tanggal'   => $this->fmt($p->tanggal_bayar, 'd/m/Y H:i'),
                    'jumlah'    => (float) $p->jumlah_bayar,
                    'metode'    => ucfirst((string) $p->metode),
                    'tahap'     => str_replace('_', ' ', (string) $p->tahap),
                    'siklus_ke' => $p->siklus_bunga_id
                        ? ($siklusById->get($p->siklus_bunga_id)?->siklus_ke)
                        : null,
                    'catatan'   => $p->catatan,
                ])->all(),
            ];
        }

        return [
            'items'   => $items,
            'ringkas' => [
                'jumlah'        => $rantai->count(),
                'hutang_awal'   => (float) $rantai->first()->jumlah_hutang,
                'bunga_total'   => round($bungaTotal, 2),
                'dibayar_total' => round($bayarTotal, 2),
            ],
            'fee'     => round($feeTotal, 2),
            'bagi'    => array_map(fn ($v) => round($v, 2), $bagiTotal),
        ];
    }

    /**
     * Nilai fee satu tagihan. Memakai kolom `fee` (20% dari total dibayar);
     * untuk data lama yang belum punya kolom tersebut dipakai jumlah pembagiannya.
     *
     * @return array{0: float, 1: array<string, float>}
     */
    private function nilaiFee(?Fee $f): array
    {
        $bagi = [
            'akom'    => (float) ($f->akom ?? 0),
            'player'  => (float) ($f->player ?? 0),
            'support' => (float) ($f->support ?? 0),
            'asset'   => (float) ($f->asset ?? 0),
        ];

        $fee = (float) ($f->fee ?? 0);
        if ($fee <= 0) {
            $fee = round(array_sum($bagi), 2);
        }

        return [round($fee, 2), $bagi];
    }

    private function fmt($nilai, string $format = 'd/m/Y'): ?string
    {
        if (! $nilai) {
            return null;
        }

        try {
            return Carbon::parse($nilai)->format($format);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function rentangTanggalDipilih(Request $request): array
    {
        $dariInput   = (string) $request->query('dari_tanggal', '');
        $sampaiInput = (string) $request->query('sampai_tanggal', '');

        try {
            $dari   = $dariInput ? Carbon::parse($dariInput)->startOfDay() : now()->startOfMonth();
            $sampai = $sampaiInput ? Carbon::parse($sampaiInput)->endOfDay() : now()->endOfMonth();
        } catch (\Exception $e) {
            $dari   = now()->startOfMonth();
            $sampai = now()->endOfMonth();
        }

        return [$dari, $sampai];
    }

    private function opsiUrut(bool $admin): array
{
    $opsi = [
        'nama'       => ['label' => 'Nama nasabah', 'nilai' => fn ($r) => mb_strtolower($r['nama'] ?? '')],
        'kode'       => ['label' => 'Kode tagihan', 'nilai' => fn ($r) => mb_strtolower($r['kode'] ?? '')],
        'tenggat'    => ['label' => 'Tenggat',      'nilai' => fn ($r) => $r['tenggat_waktu'] ?: null],
        'tgl_hutang' => ['label' => 'Tanggal hutang', 'nilai' => fn ($r) => $r['tanggal_hutang_raw'] ?: null],
        'sisa_bayar' => ['label' => 'Sisa bayar',   'nilai' => fn ($r) => (float) $r['sisa_bayar']],
        'tagihan'    => ['label' => 'Tagihan',      'nilai' => fn ($r) => (float) $r['tagihan']],
        'bunga'      => ['label' => 'Bunga',        'nilai' => fn ($r) => (float) $r['bunga']],
        'bayar'      => ['label' => 'Bayar',        'nilai' => fn ($r) => (float) $r['bayar']],
        'total'      => ['label' => 'Total',        'nilai' => fn ($r) => (float) $r['total']],
        'capai'      => ['label' => 'Capaian (%)',  'nilai' => fn ($r) => (float) $r['capai']],
    ];

    if ($admin) {
        $opsi['fee']  = ['label' => 'Fee',        'nilai' => fn ($r) => (float) $r['fee']];
        $opsi['bon']  = ['label' => 'Bon gantung', 'nilai' => fn ($r) => (float) $r['bon']];
        $opsi['sisa'] = ['label' => 'Sisa (total semua)', 'nilai' => fn ($r) => (float) $r['sisa']];
    }

    return $opsi;
}

/** Urutkan baris. Nilai kosong (null) selalu di bawah; seri dipecah berdasarkan nama lalu kode. */
private function urutkan(Collection $rows, \Closure $nilai, string $arah): Collection
{
    $kali = $arah === 'desc' ? -1 : 1;

    return $rows->sort(function ($a, $b) use ($nilai, $kali) {
        $x = $nilai($a);
        $y = $nilai($b);

        if ($x === null && $y === null) {
            $c = 0;
        } elseif ($x === null) {
            return 1;
        } elseif ($y === null) {
            return -1;
        } else {
            $c = ($x <=> $y) * $kali;
        }

        if ($c !== 0) {
            return $c;
        }

        return strnatcasecmp($a['nama'] ?? '', $b['nama'] ?? '')
            ?: strnatcasecmp($a['kode'] ?? '', $b['kode'] ?? '');
    })->values();
}
}