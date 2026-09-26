<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $dir }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ $contact['company'] }}</title>

    <link rel="icon" type="image/png" href="{{ asset('public/assets/admin/img/brand/hpi-logo.png') }}">

    {{-- صفحة عامة تُفتح من الهاتف غالبًا، فلا تُحمَّل أصول اللوحة الثقيلة. --}}
    <style>
        :root {
            --navy:      #14395c;
            --navy-deep: #0d2840;
            --blue:      #8ec5ef;
            --ink:       #1c2b3a;
            --muted:     #61748a;
            --line:      #e3ecf4;
            --bg:        #f6f9fc;
        }

        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: {!! $locale === 'ar'
                ? "'Segoe UI', Tahoma, 'Noto Naskh Arabic', sans-serif"
                : "'Segoe UI', system-ui, -apple-system, sans-serif" !!};
            line-height: 1.8;
            -webkit-font-smoothing: antialiased;
        }

        .wrap { max-width: 860px; margin: 0 auto; padding: 0 20px; }

        /* ---------- الترويسة ---------- */

        .top {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(135deg, var(--navy-deep) 0%, var(--navy) 55%, #1e5280 100%);
            padding: clamp(28px, 6vw, 56px) 0 clamp(36px, 7vw, 68px);
        }

        /* قوس يردّد شكل الشعار، كما في صفحة الدخول واللوحة. */
        .top::after {
            content: '';
            position: absolute;
            inset-inline-end: -140px;
            bottom: -240px;
            width: 460px;
            height: 460px;
            border-radius: 50%;
            border: 1.5px solid rgba(142, 197, 239, .2);
            pointer-events: none;
        }

        .brand {
            position: relative;
            z-index: 1;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: clamp(20px, 4vw, 34px);
        }

        .brand img { width: 46px; height: 46px; object-fit: contain; }

        .brand .name { font-size: 1rem; font-weight: 800; }
        .brand .tag  { font-size: .76rem; color: rgba(255,255,255,.62); }

        .top h1 {
            position: relative;
            z-index: 1;
            margin: 0 0 8px;
            font-size: clamp(1.5rem, 4.4vw, 2.2rem);
            font-weight: 800;
        }

        .top p {
            position: relative;
            z-index: 1;
            margin: 0;
            max-width: 60ch;
            color: rgba(255,255,255,.76);
            font-size: clamp(.9rem, 2.4vw, 1rem);
        }

        /* ---------- مبدّل اللغة ---------- */

        .langs {
            position: relative;
            z-index: 1;
            display: inline-flex;
            gap: 4px;
            margin-top: clamp(18px, 3.5vw, 26px);
            padding: 4px;
            border-radius: 999px;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.18);
        }

        .langs a {
            padding: 5px 16px;
            border-radius: 999px;
            color: rgba(255,255,255,.78);
            font-size: .82rem;
            font-weight: 700;
            text-decoration: none;
        }

        .langs a.on { background: #fff; color: var(--navy); }

        /* ---------- المحتوى ---------- */

        .card {
            margin: clamp(-24px, -4vw, -18px) auto clamp(28px, 6vw, 48px);
            position: relative;
            z-index: 2;
            background: #fff;
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: clamp(22px, 5vw, 44px);
            box-shadow: 0 18px 40px rgba(20, 57, 92, .08);
        }

        .card h2 {
            margin: clamp(26px, 5vw, 36px) 0 10px;
            font-size: clamp(1.02rem, 2.8vw, 1.18rem);
            font-weight: 800;
            color: var(--navy);
        }

        .card h2:first-child { margin-top: 0; }

        .card p, .card li { font-size: clamp(.9rem, 2.4vw, .97rem); color: #33465c; }

        .card ul { padding-inline-start: 1.15rem; margin: 0 0 4px; }
        .card li { margin-bottom: 6px; }

        .meta {
            display: inline-block;
            margin-bottom: 4px;
            padding: 4px 12px;
            border-radius: 999px;
            background: #eaf4fb;
            color: var(--navy);
            font-size: .76rem;
            font-weight: 700;
        }

        .note {
            margin-top: 22px;
            padding: 14px 16px;
            border-radius: 12px;
            background: #f6fafd;
            border: 1px solid var(--line);
            font-size: .88rem;
            color: #46596e;
        }

        /* ---------- بطاقات التواصل ---------- */

        .contacts {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 12px;
            margin-top: 6px;
        }

        .contact {
            display: block;
            padding: 16px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fbfdff;
            text-decoration: none;
            color: inherit;
            transition: border-color .15s ease, transform .15s ease;
        }

        .contact:hover { border-color: var(--blue); transform: translateY(-2px); }

        .contact .k { font-size: .74rem; font-weight: 700; color: var(--muted); }

        .contact .v {
            margin-top: 3px;
            font-size: .93rem;
            font-weight: 700;
            color: var(--ink);
            word-break: break-word;
            /* البريد والهاتف يُقرآن يسارًا حتى في صفحة عربية. */
            direction: ltr;
            text-align: start;
        }

        /* ---------- التذييل ---------- */

        .foot {
            padding: 0 0 clamp(28px, 6vw, 44px);
            text-align: center;
            color: var(--muted);
            font-size: .82rem;
        }

        .foot a { color: var(--navy); font-weight: 700; text-decoration: none; }
        .foot a:hover { text-decoration: underline; }
        .foot .sep { margin: 0 8px; opacity: .45; }
    </style>
</head>
<body>

<header class="top">
    <div class="wrap">
        <div class="brand">
            <img src="{{ asset('public/assets/admin/img/brand/hpi-mark.png') }}" alt="">
            <span>
                <span class="name d-block">{{ $contact['company'] }}</span>
                <span class="tag">{{ $locale === 'ar' ? 'حلول دوائية مبتكرة' : 'Innovative Pharma Solutions' }}</span>
            </span>
        </div>

        <h1>@yield('title')</h1>
        <p>@yield('lead')</p>

        {{-- اللغة في الرابط لا في الجلسة، فيبقى الرابط ثابت اللغة أينما أُرسل. --}}
        <nav class="langs">
            <a href="{{ route($currentRoute, ['locale' => 'ar']) }}" class="{{ $locale === 'ar' ? 'on' : '' }}">العربية</a>
            <a href="{{ route($currentRoute, ['locale' => 'en']) }}" class="{{ $locale === 'en' ? 'on' : '' }}">English</a>
        </nav>
    </div>
</header>

<main class="wrap">
    <div class="card">
        <span class="meta">
            {{ $locale === 'ar' ? 'آخر تحديث' : 'Last updated' }}: {{ $updated }}
        </span>

        @yield('content')
    </div>
</main>

<footer class="foot">
    <div class="wrap">
        <a href="{{ route('public.privacy', ['locale' => $locale]) }}">
            {{ $locale === 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy' }}
        </a>
        <span class="sep">·</span>
        <a href="{{ route('public.support', ['locale' => $locale]) }}">
            {{ $locale === 'ar' ? 'الدعم' : 'Support' }}
        </a>

        <div style="margin-top:10px">
            &copy; {{ date('Y') }} {{ $contact['company'] }}
        </div>
    </div>
</footer>

</body>
</html>
