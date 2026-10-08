@extends('layouts.app')
@section('title', ($item->exists ? 'Edit' : 'Tambah') . ' Nasabah — Kashi')
@section('content')
    @include('partials.nav')
    <main class="wrap">
        <div class="card form">
            <h1>{{ $item->exists ? 'Edit nasabah' : 'Tambah nasabah' }}</h1>
            @include('partials.flash')

            <form method="post" action="{{ $item->exists ? route('nasabah.update', $item->id) : route('nasabah.store') }}">
                @csrf
                @if ($item->exists) @method('PUT') @endif

                <label class="fld"><span>Nama</span>
                    <input name="nama" value="{{ old('nama', $item->nama) }}" required maxlength="100">
                </label>
                <label class="fld"><span>No HP</span>
                    <input name="no_hp" value="{{ old('no_hp', $item->no_hp) }}" maxlength="20" inputmode="tel">
                </label>
                <label class="fld"><span>Alamat</span>
                    <textarea name="alamat" rows="2" maxlength="255">{{ old('alamat', $item->alamat) }}</textarea>
                </label>
                <label class="fld"><span>Catatan</span>
                    <textarea name="catatan" rows="3" maxlength="1000">{{ old('catatan', $item->catatan) }}</textarea>
                </label>
                <label class="chk">
                    <input type="checkbox" name="is_aktif" value="1" @checked(old('is_aktif', $item->is_aktif))>
                    <span>Nasabah aktif</span>
                </label>

                <div class="dlg-act">
                    <a class="btn" href="{{ $item->exists ? route('nasabah.show', $item->id) : route('nasabah.index') }}">Batal</a>
                    <button class="btn primary">Simpan</button>
                </div>
            </form>
        </div>
    </main>
@endsection