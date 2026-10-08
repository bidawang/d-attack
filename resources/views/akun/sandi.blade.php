@extends('layouts.app')
@section('title', 'Ganti Kata Sandi — Kashi')
@section('content')
    @include('partials.nav')
    <main class="wrap">
        <div class="card form">
            <h1>Ganti kata sandi</h1>
            @include('partials.flash')

            <form method="post" action="{{ route('akun.sandi.simpan') }}">
                @csrf @method('PUT')

                <label class="fld"><span>Kata sandi lama</span>
                    <input type="password" name="sandi_lama" required autocomplete="current-password">
                </label>
                <label class="fld"><span>Kata sandi baru (min. 8 karakter)</span>
                    <input type="password" name="sandi_baru" required minlength="8" autocomplete="new-password">
                </label>
                <label class="fld"><span>Ulangi kata sandi baru</span>
                    <input type="password" name="sandi_baru_confirmation" required minlength="8" autocomplete="new-password">
                </label>

                <button class="btn primary block">Simpan</button>
            </form>
        </div>
    </main>
@endsection