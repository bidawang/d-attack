@extends('layouts.app')

@section('title', 'Beranda · Kashi')

@section('content')
@php
    $rp = fn ($n) => number_format(abs($n) < 0.5 ? 0 : $n, 0, ',', '.');
    $queryParams = ['dari_tanggal' => $dari_tanggal, 'sampai_tanggal' => $sampai_tanggal];
    $tabs = [
        'aktif'       => 'Belum Lunas',
        'bon_gantung' => 'Bon Gantung',
        'lunas'       => 'Lunas',
        'semua'       => 'Semua',
    ];
    $hariIni = now()->startOfDay();
@endphp

@include('partials.nav')

<main class="wrap">
    @include('partials.flash')

    {{-- Filter tanggal & tenggat default --}}
    <section class="tools">
        <form method="GET" action="{{ route('beranda') }}" id="form-filter" class="filter-form">
            <input type="hidden" name="tab" value="{{ $tab }}">

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

    <div class="count-bar">
        <p class="count">
            <span id="jml">{{ $rows->count() }}</span> tagihan ·
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
        <div class="bl-list" id="daftar">
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

                    // Status tenggat: lewat jika hari ini sudah melewati tanggal tenggat
                    $tgTenggat = !empty($r['tenggat_waktu']) ? Carbon\Carbon::parse($r['tenggat_waktu'])->startOfDay() : null;
                    $lewat     = $tgTenggat && $hariIni->gt($tgTenggat);
                @endphp

                <article class="bl-item" data-cari="{{ mb_strtolower($r['kode'].' '.$r['nama'].' '.($r['asal_kode'] ?? '')) }}">

                    {{-- Header --}}
                    <div class="bl-head">
                        <button type="button" class="bl-toggle" aria-expanded="false" aria-controls="acc-{{ $r['id'] }}">
                            <span class="bl-main">
                                <span class="bl-title">
                                    <span class="mono bl-kode">{{ $r['kode'] }}</span>
                                    <span class="bl-nama">{{ $r['nama'] }}</span>
                                </span>
                                <span class="bl-tags">
                                    @if ($r['status_tab'] === 'lunas')
                                        <span class="tag ok">Lunas</span>
                                    @elseif ($r['status_tab'] === 'bon_gantung')
                                        <span class="tag info">Bon gantung</span>
                                    @else
                                        <span class="tag warn">Belum Lunas</span>
                                    @endif

                                    @if ($tgTenggat)
                                        @if ($lewat)
                                            <span class="tag err" title="Tenggat {{ $tgTenggat->translatedFormat('d/m/Y') }}">Lewat tenggat</span>
                                        @else
                                            <span class="tag" title="Tenggat {{ $tgTenggat->translatedFormat('d/m/Y') }}">Belum lewat tenggat</span>
                                        @endif
                                    @endif

                                    @if ($r['is_sambungan'])
                                        <span class="tag ghost" title="Merupakan sambungan dari tagihan sebelumnya">
                                            Sambungan (Asal: {{ $r['asal_kode'] ?? 'N/A' }})
                                        </span>
                                    @endif
                                </span>
                            </span>
                            <span class="bl-chev" aria-hidden="true">▼</span>
                        </button>

                        @if ($r['bisa_bayar'])
                            <button type="button" class="btn sm pay" data-bayar="{{ json_encode($payload) }}">+ Bayar</button>
                        @endif
                    </div>

                    {{-- Isi --}}
                    <div class="bl-body" id="acc-{{ $r['id'] }}" hidden>
                        <dl class="bl-grid">
                            <div class="dt">
                                <dt>Tenggat</dt>
                                <dd>{{ $tgTenggat ? $tgTenggat->translatedFormat('d/m/Y') : '–' }}</dd>
                            </div>
                            <div class="dt">
                                <dt>Bayar sblm tenggat</dt>
                                <dd>Rp {{ $rp($r['bayar_seb']) }}</dd>
                            </div>
                            <div class="dt">
                                <dt>Tagihan</dt>
                                <dd>Rp {{ $rp($r['tagihan']) }}</dd>
                            </div>
                            <div class="dt">
                                <dt>Final bunga</dt>
                                <dd>
                                    Rp {{ $rp($r['bunga']) }}
                                    @unless ($r['diproses'])<i class="est" title="Perkiraan">est.</i>@endunless
                                </dd>
                            </div>
                            <div class="dt">
                                <dt>Bayar</dt>
                                <dd>Rp {{ $rp($r['bayar']) }}</dd>
                            </div>
                            <div class="dt">
                                <dt>Total</dt>
                                <dd class="acc-text {{ $lewat ? 'lewat' : '' }}">Rp {{ $rp($r['total']) }}</dd>
                            </div>
                            <div class="dt">
                                <dt>Pencapaian</dt>
                                <dd>
                                    <div class="cap {{ $cls }}">
                                        <div class="bar">
                                            <i style="width: {{ min(100, $r['capai']) }}%"></i>
                                            <u style="left: {{ $ambang }}%"></u>
                                        </div>
                                        <b>{{ number_format($r['capai'], 0) }}%</b>
                                    </div>
                                </dd>
                            </div>

                            @if ($admin)
                                <div class="dt">
                                    <dt>Fee</dt>
                                    <dd>{{ $r['fee'] > 0 ? 'Rp '.$rp($r['fee']) : '–' }}</dd>
                                </div>
                                <div class="dt">
                                    <dt>Bon gantung</dt>
                                    <dd>{{ $r['bon'] > 0 ? 'Rp '.$rp($r['bon']) : '–' }}</dd>
                                </div>
                                <div class="dt">
                                    <dt>Total semua</dt>
                                    <dd>Rp {{ $rp($r['total_semua']) }}</dd>
                                </div>
                                <div class="dt">
                                    <dt>Sisa</dt>
                                    <dd class="err-text">Rp {{ $rp($r['sisa']) }}</dd>
                                </div>
                                <div class="dt">
                                    <dt>Status finish</dt>
                                    <dd>
                                        @if ($r['finish'])
                                            <span class="tag ok">Finish</span>
                                        @else
                                            <span class="tag">Belum</span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="dt full">
                                    <dt>Pembagian fee</dt>
                                    <dd>
                                        <div class="bl-bagi">
                                            @foreach ($komponen as $k)
                                                <span>
                                                    <i>{{ $k->nama }}</i>
                                                    <em>{{ $r['fee'] > 0 ? $rp($r['bagi'][$k->kode] ?? 0) : '–' }}</em>
                                                </span>
                                            @endforeach
                                        </div>
                                    </dd>
                                </div>
                            @endif
                        </dl>
                    </div>
                </article>
            @endforeach
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

    var items      = $$('.bl-item');
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

    /* ===== ACCORDION ===== */
    function setOpen(item, open) {
        var toggle = $('.bl-toggle', item);
        var body   = $('.bl-body', item);
        item.classList.toggle('open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        body.hidden = !open;
    }

    function updateTutup() {
        if (tutupSemua) tutupSemua.disabled = !$('.bl-item.open');
    }

    items.forEach(function (item) {
        $('.bl-toggle', item).addEventListener('click', function () {
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

            // Jika tanggal bayar melebihi tanggal tenggat, sisa dikenakan bunga 25%
            if (dBayar > dTenggat && sisaDasar > 0) {
                var bunga = sisaDasar * 0.25;
                return Math.round((sisaDasar + bunga) * 100) / 100;
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