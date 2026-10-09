@extends('layouts.app')

@section('title', $nasabah->nama . ' — Kashi')

@section('content')
    @include('partials.nav')
    @php
        $admin = auth()->user()->isAdmin();
        $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
$labels = ['bon_gantung' => 'Bon gantung', 'sambungan' => 'Sambungan', 'berbunga' => 'Berbunga', 'lunas' => 'Lunas'];    @endphp

    <main class="wrap">
        @include('partials.flash')

        {{-- Profil Nasabah --}}
        <div class="card" style="margin-bottom: 20px;">
            <h1>
                {{ $nasabah->nama }}
                <span class="tag {{ $nasabah->is_aktif ? 'ok' : 'warn' }}">{{ $nasabah->is_aktif ? 'Aktif' : 'Nonaktif' }}</span>
            </h1>
            <p class="muted">{{ $nasabah->no_hp ?: '-' }} · {{ $nasabah->alamat ?: '-' }}</p>
            @if ($nasabah->catatan)
                <p>{{ $nasabah->catatan }}</p>
            @endif
            <div class="row" style="margin-top: 12px;">
                <a class="btn sm" href="{{ route('nasabah.index') }}">‹ Daftar</a>
                @if ($admin)
                    <a class="btn sm" href="{{ route('nasabah.edit', $nasabah->id) }}">Edit</a>
                    <a class="btn sm primary" href="{{ route('tagihan.create', ['nasabah_id' => $nasabah->id]) }}">+ Tagihan</a>
                @endif
            </div>
        </div>

        <div class="count-bar">
            <h2 style="margin: 0;">Daftar Tagihan</h2>
            @unless ($tagihan->isEmpty())
                <button type="button" class="btn sm ghost" id="tutup-semua" disabled>Tutup semua</button>
            @endunless
        </div>

        {{-- TAB FILTER STATUS --}}
        <nav class="nav-tabs">
            @foreach (['semua' => 'Semua', 'bon_gantung' => 'Bon Gantung', 'berbunga' => 'Berbunga', 'lunas' => 'Lunas'] as $key => $label)
                <a href="{{ route('nasabah.show', ['nasabah' => $nasabah->id, 'status' => $key]) }}"
                   class="tab-item {{ $status === $key ? 'active' : '' }}">
                    {{ $label }} <span class="badge">{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </nav>

        @if ($tagihan->isEmpty())
            <div class="empty">Tidak ada tagihan dengan status ini.</div>
        @else
            <div class="acc-wrapper">
                @foreach ($tagihan as $t)
                    @php
                        $bunga = (float) $t->siklus->sum('bunga');
                        $bayar = (float) $t->pembayaran->sum('jumlah_bayar');
                        $sisa  = max(0, $ringkas->sisaTagihan($t));
                        $kunci = $t->pembayaran->isNotEmpty() || $t->siklus->isNotEmpty();
                    @endphp

                    <div class="acc-item card">
                        {{-- Header Accordion --}}
                        <div class="acc-header" tabindex="0" role="button" aria-expanded="false">
                            <div class="ttl">
    <span class="mono" style="font-weight: 700; font-size: 1.05rem; color: var(--acc);">{{ $t->kode }}</span>
    <span class="tag {{ $t->status === 'lunas' ? 'ok' : (in_array($t->status, ['berbunga', 'sambungan']) ? 'warn' : 'info') }}">
        {{ $labels[$t->status] ?? $t->status }}
    </span>
    @if ($t->status === 'sambungan' && $t->tagihanAwal)
        <span class="muted" style="margin: 0;">dari {{ $t->tagihanAwal->kode }}</span>
    @endif
</div>
                            <span class="acc-icon">▼</span>
                        </div>

                        {{-- Isi Accordion --}}
                        <div class="acc-body">
                            <p class="muted" style="margin-top: 12px;">
                                Hutang {{ $t->tanggal_hutang?->format('d/m/Y') }} · Tenggat {{ $t->tenggat_waktu?->format('d/m/Y') }}
                                · Penagih: {{ $t->penagih->name ?? '-' }}
                                @if ($t->keterangan) · {{ $t->keterangan }} @endif
                            </p>

                            <div class="stats" style="margin: 12px 0;">
                                <div><span>Hutang</span><b>{{ $rp($t->jumlah_hutang) }}</b></div>
                                <div><span>Bunga</span><b>{{ $rp($bunga) }}</b></div>
                                <div><span>Dibayar</span><b>{{ $rp($bayar) }}</b></div>
                                <div><span>Sisa</span><b>{{ $rp($sisa) }}</b></div>
                            </div>

                            @if ($admin)
                                <div class="row" style="margin-bottom: 16px;">
                                    <a class="btn sm" href="{{ route('tagihan.edit', $t->id) }}">Edit tagihan</a>
                                    @unless ($kunci)
                                        <form method="post" action="{{ route('tagihan.destroy', $t->id) }}"
                                              onsubmit="return confirm('Hapus tagihan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="btn sm danger" type="submit">Hapus tagihan</button>
                                        </form>
                                    @endunless
                                </div>
                            @endif

                            <h3 style="margin-top: 16px; margin-bottom: 8px;">Pembayaran</h3>
                            @if ($t->pembayaran->isEmpty())
                                <div class="empty">Belum ada pembayaran.</div>
                            @else
                                <div class="tbwrap">
                                    <table class="tb simple">
                                        <thead>
                                            <tr>
                                                <th>Tanggal</th>
                                                <th>Penagih</th>
                                                <th>Jumlah</th>
                                                @if ($admin)<th>Aksi</th>@endif
                                            </tr>
                                        </thead>
                                        <tbody>
                                        @foreach ($t->pembayaran->sortByDesc('tanggal_bayar') as $p)
                                            <tr>
                                                <td class="c-nama">{{ $p->tanggal_bayar?->format('d/m/Y H:i') }}</td>
                                                <td data-l="Penagih">{{ $p->penagih->name ?? '-' }}</td>
                                                <td data-l="Jumlah"><span class="v">{{ $rp($p->jumlah_bayar) }}</span></td>
                                                @if ($admin)
                                                    <td class="c-act">
                                                        @if ((int) ($p->siklus_bunga_id ?? 0) === (int) ($t->siklus->last()?->id ?? 0))
                                                            <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                                                <a class="btn sm" href="{{ route('pembayaran.edit', $p->id) }}">Koreksi</a>
                                                                <form method="post" action="{{ route('pembayaran.destroy', $p->id) }}"
                                                                      onsubmit="return confirm('Hapus pembayaran ini?')">
                                                                    @csrf @method('DELETE')
                                                                    <button class="btn sm danger" type="submit">Hapus</button>
                                                                </form>
                                                            </div>
                                                        @else
                                                            <span class="muted" style="margin:0">Terkunci</span>
                                                        @endif
                                                    </td>
                                                @endif
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- LINK PAGINATION --}}
            <div class="paging-wrap" style="margin-top: 16px;">
                {{ $tagihan->links() }}
            </div>
        @endif
    </main>
@endsection

@push('scripts')
<script>
    (function () {
        var items = Array.prototype.slice.call(document.querySelectorAll('.acc-item'));
        var tutupSemua = document.getElementById('tutup-semua');

        function updateTutup() {
            if (tutupSemua) tutupSemua.disabled = !document.querySelector('.acc-item.open');
        }

        function toggle(item) {
            var open = !item.classList.contains('open');
            item.classList.toggle('open', open);
            var head = item.querySelector('.acc-header');
            if (head) head.setAttribute('aria-expanded', open ? 'true' : 'false');
            updateTutup();
        }

        items.forEach(function (item) {
            var head = item.querySelector('.acc-header');
            if (!head) return;
            head.addEventListener('click', function () { toggle(item); });
            head.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggle(item); }
            });
        });

        if (tutupSemua) {
            tutupSemua.addEventListener('click', function () {
                items.forEach(function (item) {
                    item.classList.remove('open');
                    var head = item.querySelector('.acc-header');
                    if (head) head.setAttribute('aria-expanded', 'false');
                });
                updateTutup();
            });
        }

        updateTutup();
    })();
</script>
@endpush