<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{\App\CPU\translate('admin')}} | {{\App\CPU\translate('HPI')}}</title>

    <!-- Font -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/google-fonts.css">
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('public/assets/admin/img/brand/hpi-logo.png') }}"/>

    <style>
        @font-face {
            font-family: 'Bahij';
            src: url("{{ asset('public/assets/admin/css/fonts/Bahij_TheSansArabic-Plain.ttf') }}") format('truetype');
            font-weight: normal;
            font-style: normal;
            font-display: swap;
        }

        :root {
            --hpi-navy:      #14395c;
            --hpi-navy-deep: #0d2840;
            --hpi-blue:      #8ec5ef;
            --hpi-blue-soft: #d6e9f8;
            --hpi-ink:       #1c2b3a;
            --hpi-muted:     #7c8ea1;
            --hpi-line:      #e3ecf4;
        }

        * { box-sizing: border-box; }

        html, body { height: 100%; }

        body {
            font-family: 'Bahij', 'Segoe UI', sans-serif;
            margin: 0;
            color: var(--hpi-ink);
            background: #fff;
        }

        /* الصفحة كلها لا بطاقة عائمة: البطاقة كانت تترك فراغًا واسعًا
           حولها على الشاشات الكبيرة. */
        .auth-shell {
            min-height: 100vh;
            min-height: 100dvh;   /* شريط المتصفح على الجوال يقتطع من vh */
            display: grid;
            /* اللوحة الجانبية أعرض من النموذج لتحمل الصورة دون اقتطاع. */
            grid-template-columns: minmax(420px, 1fr) minmax(360px, 520px);
            background: #fff;
        }

        /* ---------- اللوحة الجانبية ---------- */

        .auth-aside {
            position: relative;
            /* clamp: الحشو يتبع عرض الشاشة بدل أن يبقى ثابتًا فيزدحم على
               اللابتوب ويتبعثر على الشاشة الكبيرة. */
            padding: clamp(32px, 4vw, 64px);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            /* الشعار فاتح على شفاف، فيحتاج أرضية داكنة ليظهر أصلًا. */
            background:
                linear-gradient(150deg, rgba(13, 40, 64, .88) 0%, rgba(20, 57, 92, .72) 50%, rgba(30, 82, 128, .52) 100%),
                url("{{ asset('public/assets/admin/img/brand/login-side.png') }}") center/cover no-repeat;
            color: #fff;
        }

        /* قوس يردّد شكل الشعار. */
        .auth-aside::after {
            content: '';
            position: absolute;
            left: -140px;
            bottom: -190px;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            border: 1.5px solid rgba(142, 197, 239, .22);
            pointer-events: none;
        }

        .aside-brand { display: flex; align-items: center; gap: 14px; }

        /* الرمز وحده: الاسم مكتوب نصًّا بجانبه، فالملف الكامل يكرره
           مرتين بخط غير مقروء. */
        .aside-brand img { width: 52px; height: auto; flex: none; }

        .aside-brand .brand-name {
            font-size: 1.02rem;
            font-weight: 600;
            letter-spacing: .01em;
            line-height: 1.5;
        }

        .aside-brand .brand-tag {
            display: block;
            font-size: .74rem;
            font-weight: 400;
            color: var(--hpi-blue);
            letter-spacing: .06em;
        }

        .aside-body { position: relative; z-index: 1; }

        .aside-body h1 {
            font-size: clamp(1.8rem, 2.6vw, 2.6rem);
            line-height: 1.32;
            font-weight: 700;
            margin: 0 0 14px;
        }

        .aside-body h1 span { color: var(--hpi-blue); }

        .aside-body p {
            margin: 0;
            max-width: 30ch;
            font-size: .95rem;
            line-height: 1.9;
            color: rgba(255, 255, 255, .78);
        }

        .aside-features {
            position: relative;
            z-index: 1;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
        }

        .feature {
            padding: 14px 10px;
            text-align: center;
            border-radius: 14px;
            background: rgba(255, 255, 255, .08);
            border: 1px solid rgba(255, 255, 255, .12);
            backdrop-filter: blur(4px);
        }

        .feature i {
            display: block;
            font-size: 1.3rem;
            margin-bottom: 6px;
            color: var(--hpi-blue);
        }

        .feature span {
            font-size: .72rem;
            line-height: 1.5;
            color: rgba(255, 255, 255, .85);
        }

        /* ---------- النموذج ---------- */

        .auth-main {
            padding: clamp(28px, 3vw, 56px) clamp(24px, 3vw, 52px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            /* عمود الشبكة لا يصغر دون محتواه افتراضيًا، فيدفع المحتوى
               خارج الشاشة الضيقة. */
            min-width: 0;
            overflow-y: auto;
        }

        /* النموذج لا يتمدد مع العمود: حقل بعرض 500px يصعب مسحه بالعين. */
        .auth-main > * {
            width: 100%;
            max-width: 400px;
            margin-inline: auto;
        }

        .auth-head { text-align: center; margin-bottom: 34px; }

        /* الشعار فاتح على شفاف فيكاد يختفي على الأبيض؛ قرص داكن يعيده
           للظهور ويطابق زر الدخول. */
        .auth-head .logo-badge {
            width: 84px;
            height: 84px;
            margin: 0 auto 16px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(135deg, var(--hpi-navy) 0%, var(--hpi-navy-deep) 100%);
            box-shadow: 0 10px 22px rgba(20, 57, 92, .24);
        }

        .auth-head .logo-badge img { width: 46px; height: auto; }

        .auth-head h2 {
            font-size: 1.6rem;
            font-weight: 700;
            margin: 0 0 6px;
            color: var(--hpi-navy);
        }

        .auth-head p { margin: 0; font-size: .9rem; color: var(--hpi-muted); }

        .field { margin-bottom: 18px; }

        .field label {
            display: block;
            font-size: .84rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--hpi-navy);
        }

        .field-input { position: relative; }

        .field-input > i {
            position: absolute;
            top: 50%;
            inset-inline-start: 16px;
            transform: translateY(-50%);
            color: var(--hpi-muted);
            font-size: 1.05rem;
            pointer-events: none;
        }

        .form-control {
            width: 100%;
            padding: 14px 48px;
            font-family: inherit;
            font-size: .95rem;
            color: var(--hpi-ink);
            background: #f6fafd;
            border: 1.5px solid var(--hpi-line);
            border-radius: 12px;
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        .form-control::placeholder { color: #a9b8c7; }

        .form-control:focus {
            outline: none;
            background: #fff;
            border-color: var(--hpi-blue);
            box-shadow: 0 0 0 4px rgba(142, 197, 239, .22);
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            inset-inline-end: 14px;
            transform: translateY(-50%);
            border: 0;
            background: none;
            padding: 4px;
            cursor: pointer;
            color: var(--hpi-muted);
            font-size: 1.05rem;
            line-height: 1;
        }

        .toggle-password:hover { color: var(--hpi-navy); }

        .field-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 26px;
            font-size: .85rem;
        }

        .remember { display: flex; align-items: center; gap: 8px; color: var(--hpi-muted); cursor: pointer; }
        .remember input { width: 16px; height: 16px; accent-color: var(--hpi-navy); cursor: pointer; }

        .btn-login {
            width: 100%;
            padding: 15px;
            font-family: inherit;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
            background: linear-gradient(135deg, var(--hpi-navy) 0%, var(--hpi-navy-deep) 100%);
            border: 0;
            border-radius: 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: transform .15s, box-shadow .2s, filter .2s;
            box-shadow: 0 10px 24px rgba(20, 57, 92, .26);
        }

        .btn-login:hover { filter: brightness(1.12); box-shadow: 0 14px 30px rgba(20, 57, 92, .34); }
        .btn-login:active { transform: translateY(1px); }
        .btn-login i { font-size: 1.1rem; }

        /* السهم يشير إلى جهة القراءة، فينقلب مع اتجاه الصفحة. */
        [dir="rtl"] .btn-login i { transform: scaleX(-1); }

        .auth-foot {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--hpi-line);
            text-align: center;
            font-size: .72rem;
            letter-spacing: .05em;
            color: #9db0c2;
        }

        .auth-foot strong { display: block; margin-bottom: 5px; color: var(--hpi-navy); letter-spacing: .12em; }

        /* ---------- الشاشات الصغيرة ---------- */

        /* اللوحة تضيق قبل النموذج، فالنموذج هو المهم. */
        @media (max-width: 1100px) {
            .auth-shell { grid-template-columns: 1fr minmax(340px, 440px); }
            .aside-body h1 { font-size: 2rem; }
            .aside-features { grid-template-columns: repeat(2, 1fr); }
        }

        @media (max-width: 860px) {
            /* اللوحة الجانبية زينة: تُخفى قبل أن تضغط النموذج. */
            .auth-shell { grid-template-columns: 1fr; }
            .auth-aside { display: none; }
        }

        /* شاشة قصيرة: التوسيط يقصّ أعلى النموذج، فيبدأ من فوق ويُمرَّر. */
        @media (max-height: 700px) {
            .auth-main { justify-content: flex-start; }
            .auth-head { margin-bottom: 22px; }
        }

        @media (max-width: 420px) {
            .auth-main { padding: 28px 18px; }
            .auth-head h2 { font-size: 1.35rem; }
            .auth-head .logo-badge { width: 68px; height: 68px; }
        }
    </style>
</head>
<body>

<div class="auth-shell">

    <aside class="auth-aside">
        <div class="aside-brand">
            <img src="{{ asset('public/assets/admin/img/brand/hpi-mark.png') }}" alt="Hendy Pharmaceutical Industries">
            <div class="brand-name">
                Hendy Pharmaceutical Industries
                <span class="brand-tag">INNOVATIVE PHARMA SOLUTIONS</span>
            </div>
        </div>

        <div class="aside-body">
            <h1>{{\App\CPU\translate('نظام')}} <span>ERP</span> {{\App\CPU\translate('متكامل')}}</h1>
            <p>{{\App\CPU\translate('حلول متكاملة لإدارة المخزون والمبيعات والتوزيع')}}</p>
        </div>

        <div class="aside-features">
            <div class="feature">
                <i class="tio-shopping"></i>
                <span>{{\App\CPU\translate('إدارة المخزون')}}</span>
            </div>
            <div class="feature">
                <i class="tio-chart-bar-4"></i>
                <span>{{\App\CPU\translate('المبيعات والتوزيع')}}</span>
            </div>
            <div class="feature">
                <i class="tio-group-equal"></i>
                <span>{{\App\CPU\translate('المناديب')}}</span>
            </div>
            <div class="feature">
                <i class="tio-file-text"></i>
                <span>{{\App\CPU\translate('التقارير')}}</span>
            </div>
        </div>
    </aside>

    <main class="auth-main">
        <div class="auth-head">
            <div class="logo-badge">
                <img src="{{ asset('public/assets/admin/img/brand/hpi-mark.png') }}" alt="HPI">
            </div>
            <h2>{{\App\CPU\translate('أهلاً بعودتك')}}</h2>
            <p>{{\App\CPU\translate('سجّل الدخول للمتابعة إلى حسابك')}}</p>
        </div>

        <form action="{{route('admin.auth.login')}}" method="post">
            @csrf

            <div class="field">
                <label for="email">{{\App\CPU\translate('البريد الإلكتروني')}}</label>
                <div class="field-input">
                    <i class="tio-user"></i>
                    {{-- old(): إدخال خاطئ كان يمسح البريد فيعيد كتابته كاملًا. --}}
                    <input id="email" type="email" class="form-control" name="email"
                           value="{{ old('email') }}"
                           placeholder="email@address.com" required autofocus>
                </div>
            </div>

            <div class="field">
                <label for="password">{{\App\CPU\translate('كلمة المرور')}}</label>
                <div class="field-input">
                    <i class="tio-lock"></i>
                    <input id="password" type="password" class="form-control" name="password"
                           placeholder="••••••••" required>
                    <button type="button" class="toggle-password" id="togglePassword"
                            aria-label="{{\App\CPU\translate('إظهار كلمة المرور')}}">
                        <i class="tio-invisible" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <div class="field-row">
                <label class="remember">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    {{\App\CPU\translate('تذكّرني')}}
                </label>
            </div>

            <button type="submit" class="btn-login">
                {{\App\CPU\translate('تسجيل دخول')}}
                <i class="tio-arrow-forward"></i>
            </button>
        </form>

        <div class="auth-foot">
            <strong>HENDY ERP</strong>
            {{\App\CPU\translate('آمن · موثوق · لغدٍ أفضل')}}
        </div>
    </main>

</div>

<script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
{!! Toastr::message() !!}

@if ($errors->any())
    <script>
        "use strict";
        @foreach($errors->all() as $error)
        toastr.error('{{$error}}', 'Error', {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
    </script>
@endif

<script>
    (function () {
        var toggle = document.getElementById('togglePassword');
        var field  = document.getElementById('password');
        var icon   = document.getElementById('toggleIcon');

        if (!toggle || !field) { return; }

        toggle.addEventListener('click', function () {
            var hidden = field.type === 'password';
            field.type = hidden ? 'text' : 'password';
            icon.className = hidden ? 'tio-visible' : 'tio-invisible';
        });
    })();
</script>
</body>
</html>
