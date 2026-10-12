@extends('layouts.app')

@section('title', 'Tagihan · Kashi')

@section('content')
@php
    $rp      = fn ($n) => number_format(abs($n) < 0.5 ? 0 : $n, 0, ',', '.');
    $tgl     = fn ($d) => $d ? Carbon\Carbon::parse($d)->format('d/m/Y') : '–';
    $hariIni = now()->startOfDay();
    $adaFilter = $q !== '' || $status !== '' || $dari_tanggal || $sampai_tanggal;
@endphp

@include('partials.nav')

<main class="wrap">
    @include('partials.flash')

    <div class="page-head">
        <h1>Daftar Tagihan</h1>
        @if ($admin)
            <a class="btn sm primary" href="{{ route('tagihan.create') }}">+ Tagihan</a>
        @endif
    </div>

    {{-- Filter --}}
    <section class="tools">
        <form method="GET" action="{{ route('tagihan.index') }}" class="filter-row">
            <label class="fld wide">
                <span>Cari</span>
                <input type="search" name="q" value="{{ $q }}" placeholder="Kode atau nama nasabah…" autocomplete="off">
            </label>

            <label class="fld">
                <span>Status</span>
                <select name="status" onchange="this.form.submit()">
                    @foreach ($statusOpsi as $kunci => $label)
                        <option value="{{ $kunci }}" @selected($status === (string) $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>

            <label class="fld">
                <span>Tgl hutang dari</span>
                <input type="date" name="dari_tanggal" value="{{ $dari_tanggal }}">
            </label>

            <label class="fld">
                <span>Sampai</span>
                <input type="date" name="sampai_tanggal" value="{{ $sampai_tanggal }}">
            </label>

            <div class="filter-act">
                <button type="submit" class="btn primary">Terapkan</button>
                @if ($adaFilter)
                    <a class="btn ghost" href="{{ route('tagihan.index') }}">Reset</a>
                @endif
            </div>
        </form>
    </section>

    {{-- Ringkasan hasil filter --}}
    <section class="stats">
        <div><span>Jumlah tagihan</span><b>{{ number_format($ringkas['jumlah'], 0, ',', '.') }}</b></div>
        <div><span>Total hutang</span><b>Rp {{ $rp($ringkas['hutang']) }}</b></div>
        <div><span>Total dibayar</span><b>Rp {{ $rp($ringkas['dibayar']) }}</b></div>
    </section>

    @if ($tagihan->isEmpty())
        <div class="empty">
            Tidak ada tagihan yang cocok dengan filter.
            @if ($adaFilter)
                <br><a class="link" href="{{ route('tagihan.index') }}">Hapus semua filter</a>
            @endif
        </div>
    @else
        <p class="count">
            Menampilkan <b>{{ $tagihan->firstItem() }}–{{ $tagihan->lastItem() }}</b>
            dari {{ number_format($tagihan->total(), 0, ',', '.') }} tagihan
        </p>

        <div class="dtab-wrap">
            <table class="dtab">
                <thead>
                    <tr>
                        <th class="k-id">Nasabah</th>
                        <th>Status</th>
                        <th>Tgl hutang</th>
                        <th>Tenggat</th>
                        <th class="num">Hutang</th>
                        <th class="num">Dibayar</th>
                        <th class="num">Sisa</th>
                        <th>Penagih</th>
                        <th>Terbayar</th>
                        @if ($admin)<th class="k-act"></th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tagihan as $t)
                        @php
                            $hutang  = (float) $t->jumlah_hutang;
                            $dibayar = (float) $t->jumlah_dibayar;
                            $capai   = $hutang > 0 ? round($dibayar / $hutang * 100, 2) : 100.0;
                            $cls     = $capai >= $ambang ? 'ok' : ($capai > 0 ? 'warn' : '');
                            $aktif   = ! in_array($t->status, ['lunas', 'bersambung'], true);
                            $sisa    = $aktif ? max(0.0, $svc->sisaTagihan($t)) : null;

                            $tenggat = $t->tenggat_waktu ? Carbon\Carbon::parse($t->tenggat_waktu)->startOfDay() : null;
                            $lewat   = $aktif && $t->status !== 'bon_gantung' && $tenggat && $hariIni->gt($tenggat);
                            $telat   = $lewat && $sisa > 0;

                            [$stKls, $stTxt] = match (true) {
                                $t->status === 'lunas'       => ['ok',   'Lunas'],
                                $t->status === 'bersambung'  => ['info', 'Bersambung'],
                                $t->status === 'bon_gantung' => ['info', 'Bon gantung'],
                                $lewat                       => ['err',  'Lewat tenggat'],
                                default                      => ['warn', 'Belum lunas'],
                            };
                        @endphp
                        <tr class="st-{{ $stKls }}">
                            <td class="k-id c-full">
                                <span class="dt-id">
                                    <span class="mono dt-kode">{{ $t->kode }}</span>
                                    @if ($t->nasabah)
                                        <a class="dt-nm" href="{{ route('nasabah.show', $t->nasabah_id) }}">{{ $t->nasabah->nama }}</a>
                                    @else
                                        <span class="muted-dash">(nasabah dihapus)</span>
                                    @endif
                                    @if ($t->id_tagihan_awal)
                                        <small class="dt-sub">Sambungan tagihan #{{ $t->id_tagihan_awal }}</small>
                                    @endif
                                </span>
                            </td>

                            <td class="c-full"><span class="tag {{ $stKls }}">{{ $stTxt }}</span></td>

                            <td data-l="Tgl hutang">{{ $tgl($t->tanggal_hutang) }}</td>
                            <td data-l="Tenggat" class="{{ $telat ? 'err-text' : '' }}">{{ $tgl($t->tenggat_waktu) }}</td>

                            <td class="num" data-l="Hutang">Rp {{ $rp($hutang) }}</td>
                            <td class="num" data-l="Dibayar">Rp {{ $rp($dibayar) }}</td>

                            <td class="num c-key {{ $telat ? 'err-text' : '' }}" data-l="Sisa">
                                <b>{{ is_null($sisa) ? '–' : 'Rp '.$rp($sisa) }}</b>
                            </td>
                            <td data-l="Penagih">{{ $penagih[$t->penagih_id] ?? '–' }}</td>

                            <td class="c-full" data-l="Terbayar">
                                <div class="cap {{ $cls }}" title="Rp {{ $rp($dibayar) }} dari Rp {{ $rp($hutang) }}">
                                    <div class="bar">
                                        <i style="width: {{ min(100, $capai) }}%"></i>
                                        <u style="left: {{ $ambang }}%"></u>
                                    </div>
                                    <b>{{ number_format($capai, 0) }}%</b>
                                </div>
                            </td>

                            @if ($admin)
                                <td class="k-act c-full">
                                    <a class="btn sm ghost" href="{{ route('tagihan.edit', $t->id) }}">Edit</a>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="paging">
            @if ($tagihan->previousPageUrl())
                <a class="btn sm" href="{{ $tagihan->previousPageUrl() }}">‹ Sebelumnya</a>
            @else
                <button type="button" class="btn sm" disabled>‹ Sebelumnya</button>
            @endif
            <span class="info">Halaman {{ $tagihan->currentPage() }} dari {{ $tagihan->lastPage() }}</span>
            @if ($tagihan->nextPageUrl())
                <a class="btn sm" href="{{ $tagihan->nextPageUrl() }}">Berikutnya ›</a>
            @else
                <button type="button" class="btn sm" disabled>Berikutnya ›</button>
            @endif
        </div>
    @endif
</main>
@endsection