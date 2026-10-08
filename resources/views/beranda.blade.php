@extends('layouts.app')

@section('title', 'Beranda · Kashi')

@section('content')
@php
    $rp = fn ($n) => number_format(abs($n) < 0.5 ? 0 : $n, 0, ',', '.');
    $tglTeks = $tenggat->copy()->locale('id')->translatedFormat('j F Y');
@endphp

<header class="top">
    <div class="top-in">
        <a class="brand" href="{{ route('beranda') }}"><span class="logo">K</span><b>Kashi</b></a>
        <div class="who">
            <span class="nm">{{ $user->name }}</span>
            <span class="pill">{{ $admin ? 'Admin' : 'Penagih' }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn ghost sm" type="submit">Keluar</button>
        </form>
    </div>
</header>

<main class="wrap">
    @if (session('ok'))
        <div class="alert ok" role="status">{{ session('ok') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert err" role="alert">{{ $errors->first() }}</div>
    @endif

    <section class="tools">
        <form method="GET" action="{{ route('beranda') }}">
            <label class="fld inline">
                <span>Periode</span>
                <input type="month" name="bulan" value="{{ $bulan }}">
            </label>
        </form>
        <div class="tg">
            <span>Tenggat</span><b>{{ $tglTeks }}</b>
            @if ($admin)
                <button type="button" class="link" data-buka="dlg-tgl">Ubah tanggal</button>
            @endif
        </div>
    </section>

    <section class="stats">
        <div><span>Tagihan</span><b>Rp {{ $rp($sum['tagihan']) }}</b></div>
        <div><span>Bayar</span><b>Rp {{ $rp($sum['bayar']) }}</b></div>
        <div><span>Total</span><b>Rp {{ $rp($sum['total']) }}</b></div>
        @if ($admin)
            <div><span>Fee</span><b>Rp {{ $rp($sum['fee']) }}</b></div>
        @endif
    </section>

    <input type="search" id="cari" class="srch" placeholder="Cari kode atau nama nasabah…" autocomplete="off">
    <p class="count"><span id="jml">{{ $rows->count() }}</span> tagihan · tenggat {{ $tglTeks }}</p>

    @if ($rows->isEmpty())
        <div class="empty">Tidak ada tagihan dengan tenggat {{ $tglTeks }}.</div>
    @else
        <div class="tbwrap">
            <table class="tb">
                <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nasabah</th>
                    <th>Input bayar<small>sebelum tenggat</small></th>
                    <th>Tagihan</th>
                    <th>Final<small>bunga</small></th>
                    <th>Bayar</th>
                    <th>Total</th>
                    <th>Capai</th>
                    @if ($admin)
                        <th>Fee</th>
                        <th>Pembagian fee</th>
                        <th>Bon gantung</th>
                        <th>Total semua</th>
                        <th>Sisa</th>
                        <th>Var</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @foreach ($rows as $r)
                    @php
                        $cls = $r['capai'] >= $ambang ? 'ok' : ($r['capai'] > 0 ? 'warn' : '');
                        $payload = [
                            'id'   => $r['id'],
                            'kode' => $r['kode'],
                            'nama' => $r['nama'],
                            'sisa' => $r['sisa_bayar'],
                            'tahap' => $r['diproses'] ? 'sesudah pembungaan' : 'sebelum tenggat',
                        ];
                    @endphp
                    <tr data-cari="{{ mb_strtolower($r['kode'].' '.$r['nama']) }}">
                        <td class="c-kode" data-l="Kode"><span class="mono">{{ $r['kode'] }}</span></td>

                        <td class="c-nama" data-l="Nasabah">
                            <b>{{ $r['nama'] }}</b>
                            @if ($r['lunas'])
                                <span class="tag ok">Lunas</span>
                            @elseif ($r['diproses'])
                                <span class="tag warn">Berbunga</span>
                            @else
                                <span class="tag info">Bon gantung</span>
                            @endif
                        </td>

                        <td class="c-in" data-l="Bayar sblm tenggat">
                            <span class="v">{{ $rp($r['bayar_seb']) }}</span>
                            @if ($r['bisa_bayar'] && ! $r['diproses'])
                                <button type="button" class="btn sm pay" data-bayar="{{ json_encode($payload) }}">+ Bayar</button>
                            @endif
                        </td>

                        <td class="c-tag" data-l="Tagihan"><span class="v">{{ $rp($r['tagihan']) }}</span></td>

                        <td class="c-fin" data-l="Final · bunga">
                            <span class="v">{{ $rp($r['bunga']) }}</span>
                            @unless ($r['diproses'])<i class="est" title="Perkiraan, belum dibungakan">est.</i>@endunless
                        </td>

                        <td class="c-bay" data-l="Bayar">
                            <span class="v">{{ $rp($r['bayar']) }}</span>
                            @if ($r['bisa_bayar'] && $r['diproses'])
                                <button type="button" class="btn sm pay" data-bayar="{{ json_encode($payload) }}">+ Bayar</button>
                            @endif
                        </td>

                        <td class="c-tot" data-l="Total"><b class="v">{{ $rp($r['total']) }}</b></td>

                        <td class="c-cap" data-l="Capai">
                            <div class="cap {{ $cls }}">
                                <div class="bar"><i style="width: {{ min(100, $r['capai']) }}%"></i><u style="left: {{ $ambang }}%"></u></div>
                                <b>{{ number_format($r['capai'], 0) }}%</b>
                            </div>
                        </td>

                        @if ($admin)
                            <td class="c-fee" data-l="Fee"><span class="v">{{ $r['fee'] > 0 ? $rp($r['fee']) : '–' }}</span></td>

                            <td class="c-bag" data-l="Pembagian fee">
                                <div class="bagi">
                                    @foreach ($komponen as $k)
                                        <span><i>{{ $k->nama }}</i><em>{{ $r['fee'] > 0 ? $rp($r['bagi'][$k->kode] ?? 0) : '–' }}</em></span>
                                    @endforeach
                                </div>
                            </td>

                            <td class="c-bon" data-l="Bon gantung"><span class="v">{{ $r['bon'] > 0 ? $rp($r['bon']) : '–' }}</span></td>
                            <td class="c-tsm" data-l="Total semua"><span class="v">{{ $rp($r['total_semua']) }}</span></td>
                            <td class="c-sis" data-l="Sisa"><b class="v">{{ $rp($r['sisa']) }}</b></td>
                            <td class="c-var" data-l="Var">
                                @if ($r['finish'])
                                    <span class="tag ok">Finish</span>
                                @else
                                    <span class="tag">Belum</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="empty" id="kosong" hidden>Tidak ada yang cocok dengan pencarian.</div>
    @endif
</main>

{{-- Input pembayaran --}}
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
    {{-- Ubah tanggal tenggat --}}
    <dialog id="dlg-tgl" class="dlg">
        <form method="POST" action="{{ route('pengaturan.tenggat') }}" class="dlg-in">
            @csrf
            <h2>Tanggal tenggat</h2>
            <p class="muted">Beranda menampilkan tagihan yang jatuh tempo pada tanggal ini setiap bulan. Bulan yang lebih pendek memakai hari terakhirnya.</p>
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
        var $ = function (s, r) { return (r || document).querySelector(s); };
        var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };

        // ganti periode
        var per = $('input[name=bulan]');
        if (per) per.addEventListener('change', function () { if (per.value) per.form.submit(); });

        // pencarian
        var cari = $('#cari'), rows = $$('tbody tr[data-cari]'), jml = $('#jml'), kosong = $('#kosong');
        if (cari) cari.addEventListener('input', function () {
            var q = cari.value.trim().toLowerCase(), n = 0;
            rows.forEach(function (r) {
                var ok = !q || r.dataset.cari.indexOf(q) !== -1;
                r.hidden = !ok;
                if (ok) n++;
            });
            jml.textContent = n;
            if (kosong) kosong.hidden = n > 0 || !rows.length;
        });

        // dialog
        $$('[data-buka]').forEach(function (b) {
            b.addEventListener('click', function () { $('#' + b.dataset.buka).showModal(); });
        });
        $$('[data-tutup]').forEach(function (b) {
            b.addEventListener('click', function () { b.closest('dialog').close(); });
        });
        $$('dialog').forEach(function (d) {
            d.addEventListener('click', function (e) { if (e.target === d) d.close(); });
        });

        // input bayar
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
        $('#b-sisa').addEventListener('click', function () { $('#b-jml').value = sisa; });
    })();
</script>
@endpush
