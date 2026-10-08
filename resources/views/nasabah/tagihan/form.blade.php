@extends('layouts.app')
@section('title', ($item->exists ? 'Edit' : 'Buat') . ' Tagihan — Kashi')
@section('content')
    @include('partials.nav')
    <main class="wrap">
        <div class="card form">
            <h1>{{ $item->exists ? 'Edit tagihan ' . $item->kode : 'Buat tagihan' }}</h1>
            @include('partials.flash')

            @if ($terkunci)
                <div class="alert ok">Sudah ada pembayaran atau bunga, jadi jumlah dan tanggal dikunci. Penagih dan keterangan masih bisa diubah.</div>
            @endif

            <form method="post" action="{{ $item->exists ? route('tagihan.update', $item->id) : route('tagihan.store') }}">
                @csrf
                @if ($item->exists) @method('PUT') @endif

                @if ($item->exists)
                    <div class="fld"><span>Nasabah</span><b>{{ $item->nasabah->nama ?? '-' }}</b></div>
                @else
                    <label class="fld"><span>Nasabah</span>
                        <select name="nasabah_id" required>
                            <option value="">— pilih —</option>
                            @foreach ($nasabahList as $n)
                                <option value="{{ $n->id }}" @selected(old('nasabah_id', $item->nasabah_id) == $n->id)>{{ $n->nama }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif

                <label class="fld"><span>Jumlah hutang (Rp)</span>
                    <input type="number" name="jumlah_hutang" min="1" step="0.01" inputmode="decimal"
                           value="{{ old('jumlah_hutang', $item->jumlah_hutang ? (float) $item->jumlah_hutang : '') }}"
                           @disabled($terkunci) @required(! $terkunci)>
                </label>
                <label class="fld"><span>Tanggal hutang</span>
                    <input type="date" name="tanggal_hutang"
                           value="{{ old('tanggal_hutang', $item->tanggal_hutang?->format('Y-m-d')) }}"
                           @disabled($terkunci) @required(! $terkunci)>
                </label>
                <label class="fld"><span>Tenggat</span>
                    <input type="date" name="tenggat_waktu"
                           value="{{ old('tenggat_waktu', $item->tenggat_waktu?->format('Y-m-d') ?? $tenggatDefault) }}"
                           @disabled($terkunci) @required(! $terkunci)>
                </label>
                <label class="fld"><span>Penagih</span>
                    <select name="penagih_id" required>
                        <option value="">— pilih —</option>
                        @foreach ($penagihList as $p)
                            <option value="{{ $p->id }}" @selected(old('penagih_id', $item->penagih_id) == $p->id)>{{ $p->name }}</option>
                        @endforeach
                        @if ($item->exists && $item->penagih && ! $penagihList->contains('id', $item->penagih_id))
                            <option value="{{ $item->penagih_id }}" selected>{{ $item->penagih->name }} (nonaktif)</option>
                        @endif
                    </select>
                </label>
                <label class="fld"><span>Keterangan</span>
                    <textarea name="keterangan" rows="3" maxlength="500">{{ old('keterangan', $item->keterangan) }}</textarea>
                </label>

                <div class="dlg-act">
                    <a class="btn" href="{{ $item->nasabah_id ? route('nasabah.show', $item->nasabah_id) : route('nasabah.index') }}">Batal</a>
                    <button class="btn primary">Simpan</button>
                </div>
            </form>
        </div>
    </main>
@endsection