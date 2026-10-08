@extends('layouts.app')
@section('title', 'Koreksi Pembayaran — Kashi')
@section('content')
    @include('partials.nav')
    <main class="wrap">
        <div class="card form">
            <h1>Koreksi pembayaran</h1>
            <p class="muted">
                {{ $p->tagihan->nasabah->nama ?? '-' }} · <span class="mono">{{ $p->tagihan->kode }}</span>
            </p>
            @include('partials.flash')

            <form method="post" action="{{ route('pembayaran.update', $p->id) }}">
                @csrf @method('PUT')

                <label class="fld"><span>Jumlah bayar (Rp)</span>
                    <input type="number" name="jumlah_bayar" min="1" step="0.01" inputmode="decimal" required
                           value="{{ old('jumlah_bayar', (float) $p->jumlah_bayar) }}">
                </label>
                <label class="fld"><span>Tanggal bayar</span>
                    <input type="datetime-local" name="tanggal_bayar" required
                           value="{{ old('tanggal_bayar', $p->tanggal_bayar?->format('Y-m-d\TH:i')) }}">
                </label>
                <label class="fld"><span>Catatan</span>
                    <textarea name="catatan" rows="2" maxlength="500">{{ old('catatan', $p->catatan) }}</textarea>
                </label>

                <div class="dlg-act">
                    <a class="btn" href="{{ route('nasabah.show', $p->tagihan->nasabah_id) }}">Batal</a>
                    <button class="btn primary">Simpan</button>
                </div>
            </form>
        </div>
    </main>
@endsection