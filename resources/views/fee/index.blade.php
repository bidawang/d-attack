@extends('layouts.app')

@section('title', 'Fee · Kashi')

@section('content')
@php
    $rp  = fn ($n) => number_format(abs($n) < 0.5 ? 0 : $n, 0, ',', '.');
    $tgl = fn ($d) => $d ? Carbon\Carbon::parse($d)->format('d/m/Y H:i') : '–';
    $adaFilter = $q !== '' || $dari_tanggal || $sampai_tanggal;
@endphp

@include('partials.nav')

<main class="wrap">
    @include('partials.flash')

    <div class="page-head">
        <h1>Daftar Fee</h1>
    </div>

    {{-- Filter --}}
    <section class="tools">
        <form method="GET" action="{{ route('fee.index') }}" class="filter-row">
            <label class="fld wide">
                <span>Cari</span>
                <input type="search" name="q" value="{{ $q }}" placeholder="Kode atau nama nasabah…" autocomplete="off">
            </label>

            <label class="fld">
                <span>Diperbarui dari</span>
                <input type="date" name="dari_tanggal" value="{{ $dari_tanggal }}">
            </label>

            <label class="fld">
                <span>Sampai</span>
                <input type="date" name="sampai_tanggal" value="{{ $sampai_tanggal }}">
            </label>

            <div class="filter-act">
                <button type="submit" class="btn primary">Terapkan</button>
                @if ($adaFilter)
                    <a class="btn ghost" href="{{ route('fee.index') }}">Reset</a>
                @endif
            </div>
        </form>
    </section>

    {{-- Ringkasan hasil filter --}}
    <section class="stats">
        <div><span>Jumlah data</span><b>{{ number_format($ringkas['jumlah'], 0, ',', '.') }}</b></div>
        <div><span>Akom</span><b>Rp {{ $rp($ringkas['akom']) }}</b></div>
        <div><span>Player</span><b>Rp {{ $rp($ringkas['player']) }}</b></div>
        <div><span>Support</span><b>Rp {{ $rp($ringkas['support']) }}</b></div>
        <div><span>Asset</span><b>Rp {{ $rp($ringkas['asset']) }}</b></div>
        <div><span>Total fee</span><b class="acc-text">Rp {{ $rp($ringkas['fee']) }}</b></div>
    </section>

    @if ($fees->isEmpty())
        <div class="empty">
            Tidak ada fee yang cocok dengan filter.
            @if ($adaFilter)
                <br><a class="link" href="{{ route('fee.index') }}">Hapus semua filter</a>
            @endif
        </div>
    @else
        <p class="count">
            Menampilkan <b>{{ $fees->firstItem() }}–{{ $fees->lastItem() }}</b>
            dari {{ number_format($fees->total(), 0, ',', '.') }} data
        </p>

        <div class="dtab-wrap">
            <table class="dtab">
                <thead>
                    <tr>
                        <th class="k-id">Nasabah</th>
                        <th class="num">Akom</th>
                        <th class="num">Player</th>
                        <th class="num">Support</th>
                        <th class="num">Asset</th>
                        <th class="num">Total</th>
                        <th>Diperbarui</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($fees as $f)
                        @php
                            $t     = $f->tagihan;
                            $akom  = (float) $f->akom;
                            $play  = (float) $f->player;
                            $supp  = (float) $f->support;
                            $asset = (float) $f->asset;
                            $jml   = $akom + $play + $supp + $asset;
                        @endphp
                        <tr>
                            <td class="k-id c-full">
                                <span class="dt-id">
                                    @if ($t)
                                        <span class="mono dt-kode">{{ $t->kode }}</span>
                                    @else
                                        <span class="muted-dash">–</span>
                                    @endif

                                    @if ($t && $t->nasabah)
                                        <a class="dt-nm" href="{{ route('nasabah.show', $t->nasabah->id) }}">{{ $t->nasabah->nama }}</a>
                                    @else
                                        <span class="muted-dash">(nasabah dihapus)</span>
                                    @endif

                                    @if ($t && $t->trashed())
                                        <small class="dt-sub">Tagihan dihapus</small>
                                    @elseif ($t && $t->id_tagihan_awal)
                                        <small class="dt-sub">Sambungan tagihan #{{ $t->id_tagihan_awal }}</small>
                                    @endif
                                </span>
                            </td>

                            <td class="num" data-l="Akom">Rp {{ $rp($akom) }}</td>
                            <td class="num" data-l="Player">Rp {{ $rp($play) }}</td>
                            <td class="num" data-l="Support">Rp {{ $rp($supp) }}</td>
                            <td class="num" data-l="Asset">Rp {{ $rp($asset) }}</td>
                            <td class="num c-full c-key" data-l="Total fee"><b>Rp {{ $rp($jml) }}</b></td>
                            <td class="c-full" data-l="Diperbarui">{{ $tgl($f->updated_at) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td class="k-id">Total seluruh hasil filter</td>
                        <td class="num">Rp {{ $rp($ringkas['akom']) }}</td>
                        <td class="num">Rp {{ $rp($ringkas['player']) }}</td>
                        <td class="num">Rp {{ $rp($ringkas['support']) }}</td>
                        <td class="num">Rp {{ $rp($ringkas['asset']) }}</td>
                        <td class="num">Rp {{ $rp($ringkas['fee']) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="paging">
            @if ($fees->previousPageUrl())
                <a class="btn sm" href="{{ $fees->previousPageUrl() }}">‹ Sebelumnya</a>
            @else
                <button type="button" class="btn sm" disabled>‹ Sebelumnya</button>
            @endif
            <span class="info">Halaman {{ $fees->currentPage() }} dari {{ $fees->lastPage() }}</span>
            @if ($fees->nextPageUrl())
                <a class="btn sm" href="{{ $fees->nextPageUrl() }}">Berikutnya ›</a>
            @else
                <button type="button" class="btn sm" disabled>Berikutnya ›</button>
            @endif
        </div>
    @endif
</main>
@endsection