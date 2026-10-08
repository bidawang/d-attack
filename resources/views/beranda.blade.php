@extends('layouts.app')

@section('title', 'Beranda · Kashi')

@section('content')
@php
    $rp = fn ($n) => number_format(abs($n) < 0.5 ? 0 : $n, 0, ',', '.');
    $tglTeks = $tenggat->copy()->locale('id')->translatedFormat('j F Y');
@endphp

@include('partials.nav')

<main class="wrap">
    @include('partials.flash')

    {{-- Filter Rentang Tanggal & Pengaturan Tenggat --}}
    <section class="tools" style="display: flex; flex-wrap: wrap; gap: 16px; align-items: flex-end; justify-content: space-between;">
        <form method="GET" action="{{ route('beranda') }}" id="form-filter" style="display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="tab" value="{{ $tab }}">
            
            <label class="fld inline">
                <span>Dari Tanggal</span>
                <input type="date" name="dari_tanggal" value="{{ $dari_tanggal }}" onchange="this.form.submit()">
            </label>

            <label class="fld inline">
                <span>Sampai Tanggal</span>
                <input type="date" name="sampai_tanggal" value="{{ $sampai_tanggal }}" onchange="this.form.submit()">
            </label>
        </form>

        <div class="tg" style="display: flex; align-items: center; gap: 10px;">
            <span>Tenggat Default:</span>
            <b>Tgl {{ $tanggal }} setiap bulan</b>
            @if ($admin)
                <button type="button" class="btn sm ghost" data-buka="dlg-tgl">Ubah Tanggal Tenggat</button>
            @endif
        </div>
    </section>

    {{-- NAVTAB FILTER --}}
    <nav class="nav-tabs" style="display: flex; gap: 8px; margin: 16px 0; border-bottom: 1px solid var(--border, #ccc); padding-bottom: 8px; overflow-x: auto;">
        @php
            $queryParams = ['dari_tanggal' => $dari_tanggal, 'sampai_tanggal' => $sampai_tanggal];
        @endphp
        <a href="{{ route('beranda', array_merge($queryParams, ['tab' => 'aktif'])) }}" class="tab-item {{ $tab === 'aktif' ? 'active' : '' }}">
            Belum Lunas <span class="badge">{{ $counts['aktif'] }}</span>
        </a>
        <a href="{{ route('beranda', array_merge($queryParams, ['tab' => 'berbunga'])) }}" class="tab-item {{ $tab === 'berbunga' ? 'active' : '' }}">
            Berbunga <span class="badge">{{ $counts['berbunga'] }}</span>
        </a>
        <a href="{{ route('beranda', array_merge($queryParams, ['tab' => 'bon_gantung'])) }}" class="tab-item {{ $tab === 'bon_gantung' ? 'active' : '' }}">
            Bon Gantung <span class="badge">{{ $counts['bon_gantung'] }}</span>
        </a>
        <a href="{{ route('beranda', array_merge($queryParams, ['tab' => 'lunas'])) }}" class="tab-item {{ $tab === 'lunas' ? 'active' : '' }}">
            Lunas <span class="badge">{{ $counts['lunas'] }}</span>
        </a>
        <a href="{{ route('beranda', array_merge($queryParams, ['tab' => 'semua'])) }}" class="tab-item {{ $tab === 'semua' ? 'active' : '' }}">
            Semua <span class="badge">{{ $counts['semua'] }}</span>
        </a>
    </nav>

    {{-- Ringkasan Statistik --}}
    <section class="stats">
        <div><span>Tagihan</span><b>Rp {{ $rp($sum['tagihan']) }}</b></div>
        <div><span>Bayar</span><b>Rp {{ $rp($sum['bayar']) }}</b></div>
        <div><span>Total</span><b>Rp {{ $rp($sum['total']) }}</b></div>
        @if ($admin)
            <div><span>Fee</span><b>Rp {{ $rp($sum['fee']) }}</b></div>
        @endif
    </section>

    <input type="search" id="cari" class="srch" placeholder="Cari kode atau nama nasabah…" autocomplete="off">
    <p class="count"><span id="jml">{{ $rows->count() }}</span> tagihan · periode {{ Carbon\Carbon::parse($dari_tanggal)->translatedFormat('d/m/Y') }} – {{ Carbon\Carbon::parse($sampai_tanggal)->translatedFormat('d/m/Y') }}</p>

    @if ($rows->isEmpty())
        <div class="empty">Tidak ada tagihan dengan status ini pada periode yang dipilih.</div>
    @else
        <div class="tbwrap">
            <table class="tb">
                <thead>
                    <tr>
                        <th>Kode & Nasabah</th>
                        <th style="text-align: right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @foreach ($rows as $r)
                    @php
                        $cls = $r['capai'] >= $ambang ? 'ok' : ($r['capai'] > 0 ? 'warn' : '');
                        $payload = [
                            'id'    => $r['id'],
                            'kode'  => $r['kode'],
                            'nama'  => $r['nama'],
                            'sisa'  => $r['sisa_bayar'],
                            'tahap' => $r['diproses'] ? 'sesudah pembungaan' : 'sebelum tenggat',
                        ];
                    @endphp

                    {{-- Header Accordion --}}
                    <tr class="item-row" data-acc="acc-{{ $r['id'] }}" data-cari="{{ mb_strtolower($r['kode'].' '.$r['nama']) }}">
                        <td class="c-nama" style="display: flex; align-items: center; gap: 10px;" data-l="Nasabah">
                            <span class="mono" style="font-weight: 700; color: var(--acc);">{{ $r['kode'] }}</span>
                            <b>{{ $r['nama'] }}</b>
                            @if ($r['status_tab'] === 'lunas')
                                <span class="tag ok">Lunas</span>
                            @elseif ($r['status_tab'] === 'berbunga')
                                <span class="tag warn">Berbunga</span>
                            @else
                                <span class="tag info">Bon gantung</span>
                            @endif
                            <span class="acc-indicator">▼</span>
                        </td>
                        <td class="c-act" style="text-align: right;">
                            @if ($r['bisa_bayar'])
                                <button type="button" class="btn sm pay" data-bayar="{{ json_encode($payload) }}" onclick="event.stopPropagation()">+ Bayar</button>
                            @endif
                        </td>
                    </tr>

                    {{-- Isi Accordion --}}
                    <tr class="detail-row" id="acc-{{ $r['id'] }}">
                        <td colspan="2">
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <span>Bayar Sblm Tenggat</span>
                                    <b class="v">Rp {{ $rp($r['bayar_seb']) }}</b>
                                </div>
                                <div class="detail-item">
                                    <span>Tagihan</span>
                                    <b class="v">Rp {{ $rp($r['tagihan']) }}</b>
                                </div>
                                <div class="detail-item">
                                    <span>Final Bunga</span>
                                    <b class="v">Rp {{ $rp($r['bunga']) }}</b>
                                    @unless ($r['diproses'])<i class="est" title="Perkiraan">est.</i>@endunless
                                </div>
                                <div class="detail-item">
                                    <span>Bayar</span>
                                    <b class="v">Rp {{ $rp($r['bayar']) }}</b>
                                </div>
                                <div class="detail-item">
                                    <span>Total</span>
                                    <b class="v" style="color: var(--acc);">Rp {{ $rp($r['total']) }}</b>
                                </div>
                                <div class="detail-item">
                                    <span>Pencapaian</span>
                                    <div class="cap {{ $cls }}" style="margin-top: 4px;">
                                        <div class="bar"><i style="width: {{ min(100, $r['capai']) }}%"></i><u style="left: {{ $ambang }}%"></u></div>
                                        <b>{{ number_format($r['capai'], 0) }}%</b>
                                    </div>
                                </div>

                                @if ($admin)
                                    <div class="detail-item">
                                        <span>Fee</span>
                                        <b class="v">{{ $r['fee'] > 0 ? 'Rp '.$rp($r['fee']) : '–' }}</b>
                                    </div>
                                    <div class="detail-item">
                                        <span>Pembagian Fee</span>
                                        <div class="bagi" style="margin-top: 2px;">
                                            @foreach ($komponen as $k)
                                                <span><i>{{ $k->nama }}</i>: <em>{{ $r['fee'] > 0 ? $rp($r['bagi'][$k->kode] ?? 0) : '–' }}</em></span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="detail-item">
                                        <span>Bon Gantung</span>
                                        <b class="v">{{ $r['bon'] > 0 ? 'Rp '.$rp($r['bon']) : '–' }}</b>
                                    </div>
                                    <div class="detail-item">
                                        <span>Total Semua</span>
                                        <b class="v">Rp {{ $rp($r['total_semua']) }}</b>
                                    </div>
                                    <div class="detail-item">
                                        <span>Sisa</span>
                                        <b class="v" style="color: var(--err);">Rp {{ $rp($r['sisa']) }}</b>
                                    </div>
                                    <div class="detail-item">
                                        <span>Status Finish</span>
                                        <div>
                                            @if ($r['finish'])
                                                <span class="tag ok">Finish</span>
                                            @else
                                                <span class="tag">Belum</span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="empty" id="kosong" hidden>Tidak ada yang cocok dengan pencarian.</div>

        {{-- Pagination Control --}}
        <div class="paging" id="paging-wrap">
            <button type="button" class="btn sm" id="btn-prev">‹ Sblm</button>
            <span class="info" id="page-info">Halaman 1 dari 1</span>
            <button type="button" class="btn sm" id="btn-next">Slanjutnya ›</button>
        </div>
    @endif
</main>

{{-- Input Pembayaran --}}
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
    {{-- Ubah Tanggal Tenggat --}}
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

@push('styles')
<style>
    .nav-tabs .tab-item {
        padding: 6px 14px;
        text-decoration: none;
        color: var(--fg-muted, #666);
        border-radius: 6px;
        font-weight: 500;
        white-space: nowrap;
        font-size: 0.9rem;
    }
    .nav-tabs .tab-item.active {
        background-color: var(--acc, #0066cc);
        color: #fff;
    }
    .nav-tabs .tab-item .badge {
        font-size: 0.75rem;
        background: rgba(0,0,0,0.1);
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 4px;
    }
    .nav-tabs .tab-item.active .badge {
        background: rgba(255,255,255,0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var $ = function (s, r) { return (r || document).querySelector(s); };
        var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

        // ===== ACCORDION TUNGGAL =====
        $$('.item-row').forEach(function (row) {
            row.addEventListener('click', function () {
                var targetId = row.dataset.acc;
                var targetDetail = $('#' + targetId);
                var isAlreadyOpen = row.classList.contains('open');

                $$('.item-row.open').forEach(function (r) { r.classList.remove('open'); });
                $$('.detail-row.open').forEach(function (d) { d.classList.remove('open'); });

                if (!isAlreadyOpen && targetDetail) {
                    row.classList.add('open');
                    targetDetail.classList.add('open');
                }

                renderTable();
            });
        });

        // ===== PAGING & SEARCH ENGINE =====
        var rows = $$('tbody tr.item-row');
        var cari = $('#cari'), jml = $('#jml'), kosong = $('#kosong');
        var btnPrev = $('#btn-prev'), btnNext = $('#btn-next'), pageInfo = $('#page-info');

        var perPage = 10;
        var currentPage = 1;
        var filteredRows = rows.slice();

        function renderTable() {
            var total = filteredRows.length;
            var maxPage = Math.ceil(total / perPage) || 1;
            if (currentPage > maxPage) currentPage = maxPage;
            if (currentPage < 1) currentPage = 1;

            var start = (currentPage - 1) * perPage;
            var end = start + perPage;

            rows.forEach(function (r) {
                r.style.display = 'none';
                var accRow = $('#' + r.dataset.acc);
                if (accRow) accRow.style.display = 'none';
            });

            filteredRows.slice(start, end).forEach(function (r) {
                r.style.display = '';
                if (r.classList.contains('open')) {
                    var accRow = $('#' + r.dataset.acc);
                    if (accRow) {
                        accRow.style.display = window.innerWidth < 768 ? 'block' : 'table-row';
                    }
                }
            });

            if (jml) {
    jml.textContent = total;
}

if (kosong) {
    kosong.hidden = total > 0 || !rows.length;
}

if (pageInfo) {
    pageInfo.textContent = 'Halaman ' + currentPage + ' dari ' + maxPage;
}

if (btnPrev) {
    btnPrev.disabled = currentPage <= 1;
}

if (btnNext) {
    btnNext.disabled = currentPage >= maxPage;
}
        }

        if (cari) {
            cari.addEventListener('input', function () {
                var q = cari.value.trim().toLowerCase();
                filteredRows = rows.filter(function (r) {
                    return !q || r.dataset.cari.indexOf(q) !== -1;
                });
                currentPage = 1;
                renderTable();
            });
        }

        if (btnPrev) btnPrev.addEventListener('click', function () { currentPage--; renderTable(); });
        if (btnNext) btnNext.addEventListener('click', function () { currentPage++; renderTable(); });

        renderTable();

        // ===== DIALOG & INPUT BAYAR =====
        $$('[data-buka]').forEach(function (b) {
            b.addEventListener('click', function () { $('#' + b.dataset.buka).showModal(); });
        });
        $$('[data-tutup]').forEach(function (b) {
            b.addEventListener('click', function () { b.closest('dialog').close(); });
        });
        $$('dialog').forEach(function (d) {
            d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
        });

        var sisa = 0, fmt = new Intl.NumberFormat('id-ID');
        $$('.pay').forEach(function (b) {
            b.addEventListener('click', function () {
                var d = JSON.parse(b.dataset.bayar);
                sisa = d.sisa;
                $('#b-id').value = d.id;
                $('#b-info').textContent = d.kode + ' · ' + d.nama + ' — bayar ' + d.tahap + ' · sisa Rp ' + fmt.format(d.sisa);
                $('#b-jml').value = '';
                $('#b-jml').max = d.sisa;
                var n = new Date();
                n.setMinutes(n.getMinutes() - n.getTimezoneOffset());
                $('#b-tgl').value = n.toISOString().slice(0, 16);
                $('#dlg-bayar').showModal();
                $('#b-jml').focus();
            });
        });
var btnSisa = $('#b-sisa');

if (btnSisa) {
    btnSisa.addEventListener('click', function () {
        $('#b-jml').value = sisa;
    });
}    })();
</script>
@endpush