@extends('layouts.app')

@section('title', 'Nasabah — Kashi')

@section('content')
    @include('partials.nav')
    @php($admin = auth()->user()->isAdmin())

    <main class="wrap">
        @include('partials.flash')

        <div class="tools" style="display: flex; gap: 12px; justify-content: space-between; align-items: flex-end; margin-bottom: 16px;">
            <form method="get" action="{{ route('nasabah.index') }}" style="display: flex; gap: 8px; flex-grow: 1; max-width: 400px;">
                <label class="fld" style="flex-grow: 1; margin: 0;">
                    <span>Cari</span>
                    <input name="q" value="{{ $q }}" placeholder="Nama, HP, atau alamat" autocomplete="off">
                </label>
                <button class="btn" type="submit" style="align-self: flex-end;">Cari</button>
            </form>

            @if ($admin)
                <a class="btn primary" href="{{ route('nasabah.create') }}">+ Nasabah</a>
            @endif
        </div>

        @if ($items->isEmpty())
            <div class="empty">Belum ada nasabah.</div>
        @else
            <div class="tbwrap">
                <table class="tb simple">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>No HP</th>
                            <th>Alamat</th>
                            <th>Tagihan berjalan</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($items as $n)
                        <tr>
                            <td class="c-nama">
                                <b>{{ $n->nama }}</b>
                                <span class="tag {{ $n->is_aktif ? 'ok' : 'warn' }}">{{ $n->is_aktif ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td data-l="No HP">{{ $n->no_hp ?: '-' }}</td>
                            <td data-l="Alamat">{{ $n->alamat ?: '-' }}</td>
                            <td data-l="Tagihan berjalan"><span class="v">{{ $n->tagihan_aktif }}</span></td>
                            <td class="c-act">
                                <a class="btn sm" href="{{ route('nasabah.show', $n->id) }}">Lihat</a>
                                @if ($admin)
                                    <a class="btn sm" href="{{ route('nasabah.edit', $n->id) }}">Edit</a>
                                    <form method="post" action="{{ route('nasabah.destroy', $n->id) }}"
                                          onsubmit="return confirm('Hapus nasabah ini?')" style="display: inline-block;">
                                        @csrf @method('DELETE')
                                        <button class="btn sm danger" type="submit">Hapus</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Link Pagination Laravel --}}
            <div class="paging-wrap" style="margin-top: 16px;">
                {{ $items->links() }}
            </div>
        @endif
    </main>
@endsection