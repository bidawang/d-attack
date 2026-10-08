<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="color-scheme" content="light dark">
    <title>Dalam Pengembangan — {{ config('app.name', 'Sistem') }}</title>

    <style>
        :root {
            --bg: #f6f5f1;
            --surface: #ffffff;
            --ink: #1b1b18;
            --muted: #6b6a65;
            --line: #e3e2dc;
            --accent: #e8590c;
            --accent-soft: #fff0e6;
            --grid: rgba(27, 27, 24, .05);
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0d0d0c;
                --surface: #161615;
                --ink: #ededec;
                --muted: #a1a09a;
                --line: #2f2f2b;
                --accent: #ff8a3d;
                --accent-soft: #2a1a0e;
                --grid: rgba(255, 255, 255, .045);
            }
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        html { -webkit-text-size-adjust: 100%; }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            background-color: var(--bg);
            background-image:
                linear-gradient(var(--grid) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid) 1px, transparent 1px);
            background-size: 32px 32px;
            color: var(--ink);
            font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        .card {
            width: 100%;
            max-width: 560px;
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 40px 32px 28px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04), 0 12px 32px -16px rgba(0, 0, 0, .18);
            animation: rise .6s ease-out both;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 999px;
            background: var(--accent-soft);
            color: var(--accent);
            font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
            font-size: 12px;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--accent);
            animation: pulse 1.6s ease-in-out infinite;
        }

        h1 {
            margin-top: 20px;
            font-size: clamp(26px, 5.5vw, 34px);
            line-height: 1.18;
            letter-spacing: -.02em;
            font-weight: 650;
        }

        .lead {
            margin-top: 12px;
            color: var(--muted);
            font-size: 15px;
        }

        .bar {
            position: relative;
            height: 8px;
            margin-top: 28px;
            border-radius: 999px;
            background: var(--line);
            overflow: hidden;
        }

        .bar::after {
            content: "";
            position: absolute;
            inset: 0;
            width: 40%;
            border-radius: inherit;
            background: repeating-linear-gradient(
                -45deg,
                var(--accent) 0 10px,
                color-mix(in srgb, var(--accent) 70%, var(--surface)) 10px 20px
            );
            background-size: 28px 28px;
            animation: slide 1.8s ease-in-out infinite alternate, stripes 1s linear infinite;
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 14px;
            list-style: none;
            font-size: 12px;
            color: var(--muted);
        }

        .steps li {
            padding-top: 8px;
            border-top: 2px solid var(--line);
        }

        .steps li.done { border-top-color: var(--accent); color: var(--ink); }
        .steps li.now  { border-top-color: var(--accent); color: var(--accent); font-weight: 600; }

        .note {
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px dashed var(--line);
            color: var(--muted);
            font-size: 13px;
        }

        .foot {
            margin-top: 6px;
            font-size: 12px;
            color: var(--muted);
        }

        @keyframes rise   { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: none; } }
        @keyframes pulse  { 0%, 100% { opacity: 1; transform: scale(1); } 50% { opacity: .35; transform: scale(.8); } }
        @keyframes slide  { from { transform: translateX(0); } to { transform: translateX(150%); } }
        @keyframes stripes { to { background-position: 28px 0; } }

        @media (prefers-reduced-motion: reduce) {
            .card, .dot, .bar::after { animation: none; }
            .bar::after { width: 55%; }
        }

        @media (max-width: 420px) {
            .card { padding: 32px 22px 22px; }
            .steps { grid-template-columns: repeat(2, 1fr); row-gap: 12px; }
        }
    </style>
</head>
<body>
    <main class="card" role="main">
        <span class="badge"><span class="dot" aria-hidden="true"></span>Dalam pengembangan</span>

        <h1>Sistem ini sedang kami kembangkan</h1>

        <p class="lead">
            Halaman ini belum bisa digunakan sepenuhnya. Kami sedang menyiapkan
            fitur-fitur baru agar hasilnya rapi dan nyaman dipakai.
            Silakan kembali lagi nanti.
        </p>

        <div class="bar" role="progressbar" aria-label="Pengembangan sedang berjalan"></div>

        <ol class="steps" aria-label="Tahapan">
            <li class="done">Perancangan</li>
            <li class="now">Pengembangan</li>
            <li>Pengujian</li>
            <li>Peluncuran</li>
        </ol>

        <p class="note">
            Terima kasih atas kesabaran Anda.
        </p>
        <p class="foot">&copy; {{ date('Y') }} {{ config('app.name', 'Sistem') }}</p>
    </main>
</body>
</html>