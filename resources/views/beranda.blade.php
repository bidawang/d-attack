@extends('layouts.app')

@section('title', 'Beranda · Kashi')

@section('content')
@php
    $rp = fn ($n) => number_format(abs($n) < 0.5 ? 0 : $n, 0, ',', '.');
$queryParams = [
    'dari_tanggal'   => $dari_tanggal,
    'sampai_tanggal' => $sampai_tanggal,
    'urut'           => $urut,
    'arah'           => $arah,
];

// Header kolom yang bisa diklik untuk mengurutkan
$th = function (string $kunci, string $label, string $kelas = '') use ($opsiUrut, $urut, $arah, $queryParams, $tab) {
    $kls = $kelas ? ' class="'.e($kelas).'"' : '';

    if (! isset($opsiUrut[$kunci])) {
        return new Illuminate\Support\HtmlString("<th{$kls}>".e($label).'</th>');
    }

    $aktif = $urut === $kunci;
    $url   = route('beranda', array_merge($queryParams, [
        'tab'  => $tab,
        'urut' => $kunci,
        'arah' => ($aktif && $arah === 'asc') ? 'desc' : 'asc',
    ]));
    $panah = $aktif ? ($arah === 'asc' ? ' ▲' : ' ▼') : '';

    return new Illuminate\Support\HtmlString(
        "<th{$kls}><a href=\"".e($url).'" class="th-sort'.($aktif ? ' on' : '').'">'.e($label).$panah.'</a></th>'
    );
};    $tabs = [
        'aktif'       => 'Belum Lunas',
        'bon_gantung' => 'Bon Gantung',
        'lunas'       => 'Lunas',
        'semua'       => 'Semua',
    ];
    $labelFee = [
        'akom'    => 'Akom',
        'player'  => 'Player',
        'support' => 'Support',
        'asset'   => 'Asset',
    ];
    $hariIni = now()->startOfDay();
    $kolom   = $admin ? 11 : 10;
@endphp

@include('partials.nav')

<main class="wrap">
    @include('partials.flash')

    {{-- Filter tanggal & tenggat default --}}
    <section class="tools">
        <form method="GET" action="{{ route('beranda') }}" id="form-filter" class="filter-form">
<input type="hidden" name="tab" value="{{ $tab }}">
<input type="hidden" name="urut" value="{{ $urut }}">
<input type="hidden" name="arah" value="{{ $arah }}">
            <label class="fld">
                <span>Dari Tanggal</span>
                <input type="date" name="dari_tanggal" value="{{ $dari_tanggal }}" onchange="this.form.submit()">
            </label>

            <label class="fld">
                <span>Sampai Tanggal</span>
                <input type="date" name="sampai_tanggal" value="{{ $sampai_tanggal }}" onchange="this.form.submit()">
            </label>
        </form>

        <div class="tg">
            <span>Tenggat default:</span>
            <b>Tgl {{ $tanggal }} setiap bulan</b>
            @if ($admin)
                <button type="button" class="btn sm ghost" data-buka="dlg-tgl">Ubah</button>
            @endif
        </div>
    </section>

    {{-- Tab status --}}
    <nav class="nav-tabs" aria-label="Filter status tagihan">
        @foreach ($tabs as $kunci => $label)
            <a href="{{ route('beranda', array_merge($queryParams, ['tab' => $kunci])) }}"
               class="tab-item {{ $tab === $kunci ? 'active' : '' }}"
               @if ($tab === $kunci) aria-current="page" @endif>
                {{ $label }} <span class="badge">{{ $counts[$kunci] ?? 0 }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Ringkasan --}}
    <section class="stats">
        <div><span>Tagihan</span><b>Rp {{ $rp($sum['tagihan']) }}</b></div>
        <div><span>Bayar</span><b>Rp {{ $rp($sum['bayar']) }}</b></div>
        <div><span>Total</span><b>Rp {{ $rp($sum['total']) }}</b></div>
        @if ($admin)
            <div><span>Fee</span><b>Rp {{ $rp($sum['fee']) }}</b></div>
        @endif
    </section>

    <input type="search" id="cari" class="srch" placeholder="Cari kode atau nama nasabah…" autocomplete="off">
<form method="GET" action="{{ route('beranda') }}" class="sort-form">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="hidden" name="dari_tanggal" value="{{ $dari_tanggal }}">
    <input type="hidden" name="sampai_tanggal" value="{{ $sampai_tanggal }}">

    <label class="fld">
        <span>Tampilkan berdasarkan</span>
        <select name="urut" onchange="this.form.submit()">
            @foreach ($opsiUrut as $kunci => $label)
                <option value="{{ $kunci }}" @selected($urut === $kunci)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label class="fld">
        <span>Urutan</span>
        <select name="arah" onchange="this.form.submit()">
            <option value="asc"  @selected($arah === 'asc')>Naik (A→Z / kecil→besar)</option>
            <option value="desc" @selected($arah === 'desc')>Turun (Z→A / besar→kecil)</option>
        </select>
    </label>
</form>
    <div class="count-bar">
        <p class="count">
            <b id="jml">{{ $rows->count() }}</b> tagihan ·
            {{ Carbon\Carbon::parse($dari_tanggal)->translatedFormat('d/m/Y') }} –
            {{ Carbon\Carbon::parse($sampai_tanggal)->translatedFormat('d/m/Y') }}
        </p>
        @unless ($rows->isEmpty())
            <button type="button" class="btn sm ghost" id="tutup-semua" disabled>Tutup semua</button>
        @endunless
    </div>

    @if ($rows->isEmpty())
        <div class="empty">Tidak ada tagihan dengan status ini pada periode yang dipilih.</div>
    @else
        <p class="bt-hint">Geser tabel ke samping untuk melihat kolom lain →</p>

        <div class="bt-wrap">
            <table class="bt">
                <thead>
    <tr>
        {!! $th('nama', 'Nasabah', 'k-nm') !!}
        <th class="k-ak">Aksi</th>
        <th class="k-st">Status</th>
        {!! $th('tenggat', 'Tenggat', 'k-tg') !!}
        {!! $th('sisa_bayar', 'Sisa bayar') !!}
        {!! $th('tagihan', 'Tagihan') !!}
        {!! $th('bunga', 'Bunga') !!}
        {!! $th('bayar', 'Bayar') !!}
        {!! $th('total', 'Total') !!}
        {!! $th('capai', 'Capaian', 'k-cp') !!}
        @if ($admin){!! $th('fee', 'Fee') !!}@endif
    </tr>
</thead>

                @foreach ($rows as $r)
                    @php
                        $cls = $r['capai'] >= $ambang ? 'ok' : ($r['capai'] > 0 ? 'warn' : '');
                        $payload = [
                            'id'      => $r['id'],
                            'kode'    => $r['kode'],
                            'nama'    => $r['nama'],
                            'sisa'    => $r['sisa_bayar'],
                            'tagihan' => $r['tagihan'],
                            'tenggat' => $r['tenggat_waktu'],
                            'tahap'   => $r['diproses'] ? 'sesudah pembungaan' : 'sebelum tenggat',
                        ];

                        $tgTenggat = !empty($r['tenggat_waktu']) ? Carbon\Carbon::parse($r['tenggat_waktu'])->startOfDay() : null;
                        $lewat     = $tgTenggat && $hariIni->gt($tgTenggat);

                        if ($r['status_tab'] === 'lunas')            { $stKls = 'ok';   $stTxt = 'Lunas'; }
                        elseif ($r['status_tab'] === 'bon_gantung')  { $stKls = 'info'; $stTxt = 'Bon gantung'; }
                        elseif ($lewat)                              { $stKls = 'err';  $stTxt = 'Lewat tenggat'; }
                        else                                         { $stKls = 'warn'; $stTxt = 'Belum lunas'; }
                    @endphp

                    {{-- Satu tbody = satu tagihan (baris data + baris detail) --}}
                    <tbody class="bt-item st-{{ $stKls }}" data-cari="{{ mb_strtolower($r['kode'].' '.$r['nama'].' '.$r['semua_kode']) }}">

                        {{-- Baris data --}}
                        <tr class="bt-row">
                            <td class="k-nm">
                                <button type="button" class="bt-toggle" aria-expanded="false" aria-controls="acc-{{ $r['id'] }}">
                                    <span class="bt-chev" aria-hidden="true">›</span>
                                    <span class="bt-id">
                                        <span class="mono bt-kode">{{ $r['kode'] }}</span>
                                        <span class="bt-nama" title="{{ $r['nama'] }}">{{ $r['nama'] }}</span>
                                    </span>
                                </button>
                            </td>

                            <td class="k-ak">
                                @if ($r['bisa_bayar'])
                                    <button type="button" class="btn sm pay" data-bayar="{{ json_encode($payload) }}">+ Bayar</button>
                                @else
                                    <span class="muted-dash">–</span>
                                @endif
                            </td>

                            <td class="k-st">
                                <span class="tag {{ $stKls }}">{{ $stTxt }}</span>
                                @if ($r['is_sambungan'])
                                    <span class="tag ghost" title="Sambungan dari {{ $r['asal_kode'] ?? 'N/A' }}">Samb.</span>
                                @endif
                            </td>

                            <td class="k-tg">{{ $tgTenggat ? $tgTenggat->translatedFormat('d/m/Y') : '–' }}</td>

                            <td class="{{ $r['sisa_bayar'] > 0 && $lewat ? 'err-text' : '' }}"><b>Rp {{ $rp($r['sisa_bayar']) }}</b></td>
                            <td>Rp {{ $rp($r['tagihan']) }}</td>
                            <td>
                                Rp {{ $rp($r['bunga']) }}
                                @unless ($r['diproses'])<i class="est" title="Perkiraan">est.</i>@endunless
                            </td>
                            <td>Rp {{ $rp($r['bayar']) }}</td>
                            <td class="acc-text"><b>Rp {{ $rp($r['total']) }}</b></td>

                            <td class="k-cp">
                                <span class="cap mini {{ $cls }}" title="Rp {{ $rp($r['dibayar']) }} dari hutang Rp {{ $rp($r['hutang']) }}">
                                    <span class="bar"><i style="width: {{ min(100, $r['capai']) }}%"></i></span>
                                    <b>{{ number_format($r['capai'], 0) }}%</b>
                                </span>
                            </td>

                            @if ($admin)
                                <td>{{ $r['fee'] > 0 ? 'Rp '.$rp($r['fee']) : '–' }}</td>
                            @endif
                        </tr>

                        {{-- Baris detail (accordion) --}}
                        <tr class="bt-det" id="acc-{{ $r['id'] }}" hidden>
                            <td colspan="{{ $kolom }}">
                                <div class="bt-det-in">

                                    <dl class="dg">
                                        <div>
                                            <dt>Bayar sblm tenggat</dt>
                                            <dd>Rp {{ $rp($r['bayar_seb']) }}</dd>
                                        </div>
                                        <div>
                                            <dt>Final bunga</dt>
                                            <dd>
                                                Rp {{ $rp($r['bunga']) }}
                                                @unless ($r['diproses'])<i class="est" title="Perkiraan">est.</i>@endunless
                                            </dd>
                                        </div>
                                        <div>
                                            <dt>Total</dt>
                                            <dd class="acc-text {{ $lewat ? 'lewat' : '' }}">Rp {{ $rp($r['total']) }}</dd>
                                        </div>
                                        @if ($r['is_sambungan'])
                                            <div>
                                                <dt>Sambungan dari</dt>
                                                <dd class="mono">{{ $r['asal_kode'] ?? 'N/A' }}</dd>
                                            </div>
                                        @endif
                                        <div class="w2">
                                            <dt>Pencapaian</dt>
                                            <dd>
                                                <div class="cap {{ $cls }}">
                                                    <div class="bar">
                                                        <i style="width: {{ min(100, $r['capai']) }}%"></i>
                                                        <u style="left: {{ $ambang }}%"></u>
                                                    </div>
                                                    <b>{{ number_format($r['capai'], 0) }}%</b>
                                                </div>
                                                <small class="muted">Rp {{ $rp($r['dibayar']) }} dari hutang Rp {{ $rp($r['hutang']) }}</small>
                                            </dd>
                                        </div>

                                        @if ($admin)
                                            <div>
                                                <dt>Bon gantung</dt>
                                                <dd>{{ $r['bon'] > 0 ? 'Rp '.$rp($r['bon']) : '–' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Total semua</dt>
                                                <dd>Rp {{ $rp($r['total_semua']) }}</dd>
                                            </div>
                                            <div>
                                                <dt>Sisa</dt>
                                                <dd class="err-text">Rp {{ $rp($r['sisa']) }}</dd>
                                            </div>
                                            <div>
                                                <dt>Status finish</dt>
                                                <dd>
                                                    @if ($r['finish'])
                                                        <span class="tag ok">Finish</span>
                                                    @else
                                                        <span class="tag">Belum</span>
                                                    @endif
                                                </dd>
                                            </div>
                                            <div class="w2">
                                                <dt>Pembagian fee</dt>
                                                <dd>
                                                    <div class="bagi-row">
                                                        @foreach ($labelFee as $kode => $label)
                                                            <span>
                                                                <i>{{ $label }}</i>
                                                                <em>{{ $r['fee'] > 0 ? $rp($r['bagi'][$kode] ?? 0) : '–' }}</em>
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </dd>
                                            </div>
                                        @endif
                                    </dl>

                                    {{-- Rantai tagihan --}}
                                    @if (!empty($r['rantai']))
                                        <div class="rt-head">
                                            <b>Riwayat rantai tagihan</b>
                                            <span>Akar → Akhir · {{ count($r['rantai']) }} tagihan</span>
                                        </div>

                                        <div class="rt-wrap">
                                            <table class="rt">
                                                <thead>
                                                    <tr>
                                                        <th>Kode</th>
                                                        <th>Tgl hutang</th>
                                                        <th>Hutang</th>
                                                        <th>Dibayar</th>
                                                        <th>Bunga</th>
                                                        <th>Sisa</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($r['rantai'] as $it)
                                                        <tr class="{{ $it['is_terakhir'] ? 'aktif' : '' }}">
                                                            <td>
                                                                <span class="mono rk">{{ $it['kode'] }}</span>
                                                                @if ($it['is_akar'])<span class="tag">Akar</span>@endif
                                                                @if ($it['is_terakhir'])<span class="tag info">Aktif</span>@endif
                                                            </td>
                                                            <td>{{ $it['tanggal_hutang'] ?? '–' }}</td>
                                                            <td>Rp {{ $rp($it['hutang']) }}</td>
                                                            <td>Rp {{ $rp($it['dibayar']) }}</td>
                                                            <td>Rp {{ $rp($it['bunga']) }}</td>
                                                            <td class="{{ $it['sisa'] > 0 ? 'err-text' : '' }}">Rp {{ $rp($it['sisa']) }}</td>
                                                            <td><span class="tag ghost">{{ ucfirst($it['status']) }}</span></td>
                                                        </tr>

                                                        @if (!empty($it['pembayaran']))
                                                            <tr class="rt-pay">
                                                                <td colspan="7">
                                                                    @foreach ($it['pembayaran'] as $by)
                                                                        <span class="chip">
                                                                            {{ $by['tanggal'] }} · <b>Rp {{ $rp($by['jumlah']) }}</b> · {{ $by['metode'] }}
                                                                            @if ($by['catatan']) · <em>{{ $by['catatan'] }}</em>@endif
                                                                        </span>
                                                                    @endforeach
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    @endif

                                </div>
                            </td>
                        </tr>
                    </tbody>
                @endforeach
            </table>
        </div>

        <div class="empty" id="kosong" hidden>Tidak ada yang cocok dengan pencarian.</div>

        <div class="paging" id="paging-wrap">
            <button type="button" class="btn sm" id="btn-prev">‹ Sebelumnya</button>
            <span class="info" id="page-info">Halaman 1 dari 1</span>
            <button type="button" class="btn sm" id="btn-next">Berikutnya ›</button>
        </div>
    @endif
</main>

{{-- Dialog input pembayaran --}}
<dialog id="dlg-bayar" class="dlg">
    <form method="POST" action="{{ route('pembayaran.store') }}" class="dlg-in">
        @csrf
        <input type="hidden" name="tagihan_id" id="b-id">
        <h2>Input pembayaran</h2>
        <p class="muted" id="b-info"></p>

        <label class="fld">
            <span>Jumlah bayar (Rp)</span>
            <input type="number" name="jumlah_bayar" id="b-jml" inputmode="decimal" min="1" step="any" required>
        </label>
        <button type="button" class="link" id="b-sisa">Isi sebesar sisa</button>

        <label class="fld">
            <span>Tanggal bayar</span>
            <input type="datetime-local" name="tanggal_bayar" id="b-tgl" required>
        </label>
        <label class="fld">
            <span>Metode</span>
            <select name="metode">
                <option value="tunai">Tunai</option>
                <option value="transfer">Transfer</option>
            </select>
        </label>
        <label class="fld">
            <span>Catatan (opsional)</span>
            <input type="text" name="catatan" maxlength="255">
        </label>

        <div class="dlg-act">
            <button type="button" class="btn ghost" data-tutup>Batal</button>
            <button type="submit" class="btn primary">Simpan</button>
        </div>
    </form>
</dialog>

@if ($admin)
    {{-- Dialog ubah tenggat --}}
    <dialog id="dlg-tgl" class="dlg">
        <form method="POST" action="{{ route('pengaturan.tenggat') }}" class="dlg-in">
            @csrf
            <h2>Tanggal tenggat default</h2>
            <p class="muted">Tenggat default setiap bulan untuk perhitungan sistem.</p>
            <label class="fld">
                <span>Tanggal (1–31)</span>
                <input type="number" name="tanggal" min="1" max="31" value="{{ $tanggal }}" inputmode="numeric" required>
            </label>
            <div class="dlg-act">
                <button type="button" class="btn ghost" data-tutup>Batal</button>
                <button type="submit" class="btn primary">Simpan</button>
            </div>
        </form>
    </dialog>
@endif
@endsection

@push('scripts')
<script>
(function () {
    var $  = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

    var items      = $$('.bt-item');
    var cari       = $('#cari');
    var jml        = $('#jml');
    var kosong     = $('#kosong');
    var btnPrev    = $('#btn-prev');
    var btnNext    = $('#btn-next');
    var pageInfo   = $('#page-info');
    var pagingWrap = $('#paging-wrap');
    var tutupSemua = $('#tutup-semua');

    var perPage     = 10;
    var currentPage = 1;
    var filtered    = items.slice();

    /* ===== ACCORDION (klik di mana saja pada baris) ===== */
    function setOpen(item, open) {
        var toggle = $('.bt-toggle', item);
        var body   = $('.bt-det', item);
        item.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        body.hidden = !open;
    }

    function updateTutup() {
        if (tutupSemua) tutupSemua.disabled = !$('.bt-item.open');
    }

    items.forEach(function (item) {
        $('.bt-row', item).addEventListener('click', function (e) {
            if (e.target.closest('.pay')) return; // tombol bayar tidak ikut membuka baris
            setOpen(item, !item.classList.contains('open'));
            updateTutup();
        });
    });

    if (tutupSemua) {
        tutupSemua.addEventListener('click', function () {
            items.forEach(function (item) { setOpen(item, false); });
            updateTutup();
        });
    }

    /* ===== PENCARIAN & PAGING ===== */
    function render(scroll) {
        var total   = filtered.length;
        var maxPage = Math.ceil(total / perPage) || 1;
        if (currentPage > maxPage) currentPage = maxPage;
        if (currentPage < 1) currentPage = 1;

        var start   = (currentPage - 1) * perPage;
        var visible = filtered.slice(start, start + perPage);

        items.forEach(function (item) {
            item.hidden = visible.indexOf(item) === -1;
        });

        if (jml) jml.textContent = total;
        if (kosong) kosong.hidden = total > 0 || !items.length;
        if (pagingWrap) pagingWrap.hidden = total === 0;
        if (pageInfo) pageInfo.textContent = 'Halaman ' + currentPage + ' dari ' + maxPage;
        if (btnPrev) btnPrev.disabled = currentPage <= 1;
        if (btnNext) btnNext.disabled = currentPage >= maxPage;

        if (scroll) {
            var bar = $('.count-bar');
            if (bar) bar.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    if (cari) {
        cari.addEventListener('input', function () {
            var q = cari.value.trim().toLowerCase();
            filtered = items.filter(function (item) {
                return !q || item.dataset.cari.indexOf(q) !== -1;
            });
            currentPage = 1;
            render(false);
        });
    }

    if (btnPrev) btnPrev.addEventListener('click', function () { currentPage--; render(true); });
    if (btnNext) btnNext.addEventListener('click', function () { currentPage++; render(true); });

    render(false);
    updateTutup();

    /* ===== DIALOG ===== */
    $$('[data-buka]').forEach(function (b) {
        b.addEventListener('click', function () { $('#' + b.dataset.buka).showModal(); });
    });
    $$('[data-tutup]').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('dialog').close(); });
    });
    $$('dialog').forEach(function (d) {
        d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
    });

    /* ===== INPUT PEMBAYARAN DINAMIS (BERDASARKAN TANGGAL BAYAR) ===== */
    var currentData = null;
    var fmt = new Intl.NumberFormat('id-ID');

    function hitungSisaDinamis() {
        if (!currentData) return 0;
        var tglBayarInput = $('#b-tgl').value;
        var sisaDasar = currentData.sisa;

        if (tglBayarInput && currentData.tenggat) {
            var dBayar = new Date(tglBayarInput);
            var dTenggat = new Date(currentData.tenggat);
            dTenggat.setHours(23, 59, 59, 999);

            if (dBayar > dTenggat && sisaDasar > 0) {
    var bunga = Math.ceil(Math.round(sisaDasar * 0.25 * 100) / 100 / 1000) * 1000;
    return sisaDasar + bunga;
}
        }
        return sisaDasar;
    }

    $$('.pay').forEach(function (b) {
        b.addEventListener('click', function () {
            currentData = JSON.parse(b.dataset.bayar);
            $('#b-id').value = currentData.id;
            $('#b-jml').value = '';

            var n = new Date();
            n.setMinutes(n.getMinutes() - n.getTimezoneOffset());
            $('#b-tgl').value = n.toISOString().slice(0, 16);

            updateInfoBayar();
            $('#dlg-bayar').showModal();
            $('#b-jml').focus();
        });
    });

    function updateInfoBayar() {
        if (!currentData) return;
        var sisaAktif = hitungSisaDinamis();
        $('#b-jml').max = sisaAktif;
        $('#b-info').textContent = currentData.kode + ' · ' + currentData.nama + ' — sisa bayar: Rp ' + fmt.format(sisaAktif);
    }

    var inputTglBayar = $('#b-tgl');
    if (inputTglBayar) {
        inputTglBayar.addEventListener('change', updateInfoBayar);
    }

    var btnSisa = $('#b-sisa');
    if (btnSisa) {
        btnSisa.addEventListener('click', function () {
            $('#b-jml').value = hitungSisaDinamis();
        });
    }
})();
</script>
@endpush