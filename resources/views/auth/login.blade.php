@extends('layouts.app')

@section('title', 'Masuk · Kashi')
@section('body_class', 'auth')

@section('content')
<main>
    <form class="auth-card" method="POST" action="{{ route('login.proses') }}">
        @csrf

        <div class="brand">
            <span class="logo">K</span>
            <div>
                <h1>Kashi</h1>
                <p>Penagihan nasabah</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="alert err" role="alert">{{ $errors->first() }}</div>
        @endif

        <label class="fld">
            <span>Email</span>
            <input type="email" name="email" value="{{ old('email') }}" required autofocus
                   autocomplete="username" inputmode="email" autocapitalize="none" spellcheck="false">
        </label>

        <label class="fld">
            <span>Kata sandi</span>
            <div class="pw">
                <input type="password" name="password" id="pw" required autocomplete="current-password">
                <button type="button" id="pw-toggle" aria-controls="pw">Lihat</button>
            </div>
        </label>

        <label class="chk">
            <input type="checkbox" name="ingat" value="1" @checked(old('ingat'))>
            <span>Ingat saya</span>
        </label>

        <button class="btn primary block" type="submit">Masuk</button>
    </form>
</main>
@endsection

@push('scripts')
<script>
    (function () {
        var pw = document.getElementById('pw'), tg = document.getElementById('pw-toggle');
        tg.addEventListener('click', function () {
            var tampil = pw.type === 'password';
            pw.type = tampil ? 'text' : 'password';
            tg.textContent = tampil ? 'Sembunyi' : 'Lihat';
        });
    })();
</script>
@endpush
