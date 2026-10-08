@extends('layouts.app')
@section('title', 'Penagih — Kashi')
@section('content')
    @include('partials.nav')
    <main class="wrap">
        @include('partials.flash')

        <div class="tools">
            <form method="get" class="tools">
                <label class="fld"><span>Cari</span>
                    <input name="q" value="{{ $q }}" placeholder="Nama, email, atau HP">
                </label>
                <button class="btn">Cari</button>
            </form>
            <a class="btn primary" href="{{ route('penagih.create') }}">+ Penagih</a>
        </div>

        @if ($items->isEmpty())
            <div class="empty">Belum ada penagih.</div>
        @else
            <div class="tbwrap">
                <table class="tb simple">
                    <thead>
                    <tr>
                        <th>Nama</th><th>Email</th><th>No HP</th><th>Tagihan berjalan</th><th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach ($items as $p)
                        <tr>
                            <td class="c-nama">
                                <b>{{ $p->name }}</b>
                                <span class="tag {{ $p->is_aktif ? 'ok' : 'warn' }}">{{ $p->is_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td data-l="Email">{{ $p->email }}</td>
                            <td data-l="No HP">{{ $p->no_hp ?: '-' }}</td>
                            <td data-l="Tagihan berjalan"><span class="v">{{ $p->tagihan_aktif }}</span></td>
                            <td class="c-act">
                                <a class="btn sm" href="{{ route('penagih.edit', $p->id) }}">Edit</a>
                                <form method="post" action="{{ route('penagih.aktif', $p->id) }}">
                                    @csrf @method('PATCH')
                                    <button class="btn sm">{{ $p->is_aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                                <form method="post" action="{{ route('penagih.destroy', $p->id) }}"
                                      onsubmit="return confirm('Hapus penagih ini? Riwayat pembayarannya tetap tersimpan.')">
                                    @csrf @method('DELETE')
                                    <button class="btn sm danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @include('partials.paging')
        @endif
    </main>
@endsection