@extends('layouts.app')
@section('title', ($item->exists ? 'Edit' : 'Tambah') . ' Penagih — Kashi')
@section('content')
    @include('partials.nav')
    <main class="wrap">
        <div class="card form">
            <h1>{{ $item->exists ? 'Edit penagih' : 'Tambah penagih' }}</h1>
            @include('partials.flash')

            <form method="post" action="{{ $item->exists ? route('penagih.update', $item->id) : route('penagih.store') }}">
                @csrf
                @if ($item->exists) @method('PUT') @endif

                <label class="fld"><span>Nama</span>
                    <input name="name" value="{{ old('name', $item->name) }}" required maxlength="100">
                </label>
                <label class="fld"><span>Email</span>
                    <input type="email" name="email" value="{{ old('email', $item->email) }}" required>
                </label>
                <label class="fld"><span>No HP</span>
                    <input name="no_hp" value="{{ old('no_hp', $item->no_hp) }}" maxlength="20" inputmode="tel">
                </label>
                <label class="fld">
                    <span>{{ $item->exists ? 'Kata sandi baru (kosongkan jika tidak diubah)' : 'Kata sandi' }}</span>
                    <input type="password" name="password" minlength="8" autocomplete="new-password"
                           @required(! $item->exists)>
                </label>
                <label class="chk">
                    <input type="checkbox" name="is_aktif" value="1" @checked(old('is_aktif', $item->is_aktif))>
                    <span>Akun aktif</span>
                </label>

                <div class="dlg-act">
                    <a class="btn" href="{{ route('penagih.index') }}">Batal</a>
                    <button class="btn primary">Simpan</button>
                </div>
            </form>
        </div>
    </main>
@endsection