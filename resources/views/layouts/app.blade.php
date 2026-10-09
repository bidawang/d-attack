<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <meta name="theme-color" content="#16201e" media="(prefers-color-scheme: dark)">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Kashi')</title>
    <link rel="stylesheet" href="{{ asset('css/kashi.css') }}?v={{ @filemtime(public_path('css/kashi.css')) }}">
    @stack('styles')
</head>
<body class="@yield('body_class')">
@yield('content')
@stack('scripts')
</body>
</html>