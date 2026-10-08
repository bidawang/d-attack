@if (session('ok'))
    <div class="alert ok">{{ session('ok') }}</div>
@endif
@if (session('err'))
    <div class="alert err">{{ session('err') }}</div>
@endif
@if ($errors->any())
    <div class="alert err">
        @foreach ($errors->all() as $e)
            <div>{{ $e }}</div>
        @endforeach
    </div>
@endif