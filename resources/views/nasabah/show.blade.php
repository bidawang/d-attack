@extends('layouts.app')

@section('title', $nasabah->nama . ' — Kashi')

@section('content')
    @include('partials.nav')
    @php
        $admin = auth()->user()->isAdmin();
        $rp = fn ($v) => 'Rp ' . number_format((float) $v, 0, ',', '.');
    @endphp

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

        <h2>Daftar Tagihan</h2>

        {{-- TAB FILTER STATUS --}}
        <nav class="nav-tabs" style="display: flex; gap: 8px; margin: 16px 0; border-bottom: 1px solid var(--line, #ccc); padding-bottom: 8px; overflow-x: auto;">
            <a href="{{ route('nasabah.show', ['nasabah' => $nasabah->id, 'status' => 'semua']) }}" 
               class="tab-item {{ $status === 'semua' ? 'active' : '' }}">
                Semua <span class="badge">{{ $counts['semua'] }}</span>
            </a>
            <a href="{{ route('nasabah.show', ['nasabah' => $nasabah->id, 'status' => 'bon_gantung']) }}" 
               class="tab-item {{ $status === 'bon_gantung' ? 'active' : '' }}">
                Bon Gantung <span class="badge">{{ $counts['bon_gantung'] }}</span>
            </a>
            <a href="{{ route('nasabah.show', ['nasabah' => $nasabah->id, 'status' => 'berbunga']) }}" 
               class="tab-item {{ $status === 'berbunga' ? 'active' : '' }}">
                Berbunga <span class="badge">{{ $counts['berbunga'] }}</span>
            </a>
            <a href="{{ route('nasabah.show', ['nasabah' => $nasabah->id, 'status' => 'lunas']) }}" 
               class="tab-item {{ $status === 'lunas' ? 'active' : '' }}">
                Lunas <span class="badge">{{ $counts['lunas'] }}</span>
            </a>
        </nav>

        @if ($tagihan->isEmpty())
            <div class="empty">Tidak ada tagihan dengan status ini.</div>
        @else
            <div class="acc-wrapper">
                @foreach ($tagihan as $t)
                    @php
                        $bunga  = (float) $t->siklus->sum('bunga');
                        $bayar  = (float) $t->pembayaran->sum('jumlah_bayar');
                        $sisa   = max(0, $ringkas->sisaTagihan($t));
                        $kunci  = $t->pembayaran->isNotEmpty() || $t->siklus->isNotEmpty();
                        $labels = ['bon_gantung' => 'Bon gantung', 'berbunga' => 'Berbunga', 'lunas' => 'Lunas'];
                    @endphp

                    <div class="acc-item card" style="padding: 0; overflow: hidden; margin-bottom: 12px;">
                        {{-- Header Accordion --}}
                        <div class="acc-header" data-acc-target="acc-tagihan-{{ $t->id }}" style="padding: 16px; cursor: pointer; display: flex; align-items: center; justify-content: space-between; background: var(--bg-card, #fff);">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="mono" style="font-weight: 700; font-size: 1.05rem; color: var(--acc, #0066cc);">{{ $t->kode }}</span>
                                <span class="tag {{ $t->status === 'lunas' ? 'ok' : ($t->status === 'berbunga' ? 'warn' : 'info') }}">
                                    {{ $labels[$t->status] ?? $t->status }}
                                </span>
                            </div>
                            <span class="acc-icon" style="transition: transform 0.2s; font-size: 12px; color: var(--mut, #888);">▼</span>
                        </div>

                        {{-- Isi Accordion --}}
                        <div class="acc-body" id="acc-tagihan-{{ $t->id }}" style="display: none; padding: 0 16px 16px 16px; border-top: 1px solid var(--line, #eee);">
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

@push('styles')
<style>
    .nav-tabs .tab-item {
        padding: 6px 14px;
        text-decoration: none;
        color: var(--fg-muted, #666);
        border-radius: 6px;
        font-weight: 500;
        white-space: nowrap;
        font-size: 0.9rem;
    }
    .nav-tabs .tab-item.active {
        background-color: var(--acc, #0066cc);
        color: #fff;
    }
    .nav-tabs .tab-item .badge {
        font-size: 0.75rem;
        background: rgba(0,0,0,0.08);
        padding: 2px 6px;
        border-radius: 10px;
        margin-left: 4px;
    }
    .nav-tabs .tab-item.active .badge {
        background: rgba(255,255,255,0.25);
    }
</style>
@endpush

@push('scripts')
<script>
    (function () {
        var headers = document.querySelectorAll('.acc-header');

        headers.forEach(function (header) {
            header.addEventListener('click', function () {
                var targetId = header.dataset.accTarget;
                var targetBody = document.getElementById(targetId);
                var isAlreadyOpen = header.classList.contains('open');

                // Tutup semua accordion yang terbuka
                headers.forEach(function (h) {
                    h.classList.remove('open');
                    var icon = h.querySelector('.acc-icon');
                    if (icon) icon.style.transform = 'rotate(0deg)';
                });
                document.querySelectorAll('.acc-body').forEach(function (b) {
                    b.style.display = 'none';
                });

                // Buka elemen yang diklik
                if (!isAlreadyOpen && targetBody) {
                    header.classList.add('open');
                    targetBody.style.display = 'block';
                    var icon = header.querySelector('.acc-icon');
                    if (icon) icon.style.transform = 'rotate(180deg)';
                }
            });
        });
    })();
</script>
@endpush