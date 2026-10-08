@extends('layouts.app')

@section('title', 'Pengaturan · Kashi')

@section('content')
@include('partials.nav')

<main class="wrap">
    @include('partials.flash')

    <div class="card form">
        <h1>Pengaturan Aplikasi</h1>
        <p class="muted">Kelola parameter operasional sistem Kashi.</p>

        {{-- Form Pengaturan Tanggal Tenggat --}}
        <form method="POST" action="{{ route('pengaturan.tenggat') }}">
            @csrf
            
            <h3>Sistem Tenggat</h3>
            
            <label class="fld">
                <span>Tanggal Tenggat Bulanan (1–31)</span>
                <input 
                    type="number" 
                    name="tanggal" 
                    min="1" 
                    max="31" 
                    value="{{ old('tanggal', $tanggalTenggat) }}" 
                    inputmode="numeric" 
                    required
                >
            </label>
            <p class="muted" style="margin-top: -8px; font-size: 12px;">
                Beranda menampilkan tagihan yang jatuh tempo pada tanggal ini setiap bulan. Bulan yang memiliki jumlah hari lebih pendek otomatis memakai hari terakhir di bulan tersebut.
            </p>

            <div class="row" style="margin-top: 18px;">
                <button type="submit" class="btn primary">Simpan Pengaturan</button>
            </div>
        </form>
    </div>
</main>
@endsection