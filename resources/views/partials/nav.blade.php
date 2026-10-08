@php($u = auth()->user())
<header class="top">
    <div class="top-in">
        <a class="brand" href="{{ route('beranda') }}"><span class="logo">K</span></a>
        <nav class="nav">
            <a href="{{ route('beranda') }}" @class(['on' => request()->routeIs('beranda')])>Beranda</a>
            <a href="{{ route('nasabah.index') }}" @class(['on' => request()->routeIs('nasabah.*')])>Nasabah</a>
            @if ($u->isAdmin())
                <a href="{{ route('penagih.index') }}" @class(['on' => request()->routeIs('penagih.*')])>Penagih</a>
            @endif
        </nav>
        <div class="who">
            <span class="nm">{{ $u->name }}</span>
            <span class="pill">{{ $u->isAdmin() ? 'Admin' : 'Penagih' }}</span>
            <a class="link" href="{{ route('akun.sandi') }}">Sandi</a>
            <form method="post" action="{{ route('logout') }}" style="display:inline">
                @csrf
                <button class="link">Keluar</button>
            </form>
        </div>
    </div>
</header>