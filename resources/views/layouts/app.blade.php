<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0f766e">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Kashi')</title>
    <link rel="stylesheet" href="{{ asset('css/kashi.css') }}?v={{ @filemtime(public_path('css/kashi.css')) }}">
    @stack('scripts')
</head>
<body class="@yield('body_class')">
@yield('content')
@stack('scripts')
</body>
</html>
