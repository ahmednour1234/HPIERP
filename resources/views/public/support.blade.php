@php($currentRoute = 'public.support')
@extends('public.layout')

@section('title', $locale === 'ar' ? 'الدعم الفني' : 'Support')

@section('lead', $locale === 'ar'
    ? 'تواصل معنا، وحلول سريعة لأكثر ما يُسأل عنه.'
    : 'How to reach us, and quick answers to the questions we are asked most.')

@section('content')
@if($locale === 'ar')

    <h2>تواصل معنا</h2>
    <div class="contacts">
        <a class="contact" href="mailto:{{ $contact['email'] }}">
            <div class="k">البريد الإلكتروني</div>
            <div class="v">{{ $contact['email'] }}</div>
        </a>

        @if($contact['phone'])
            <a class="contact" href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}">
                <div class="k">الهاتف</div>
                <div class="v">{{ $contact['phone'] }}</div>
            </a>
        @endif

        @if($contact['address'])
            <div class="contact">
                <div class="k">العنوان</div>
                <div class="v" style="direction:rtl">{{ $contact['address'] }}</div>
            </div>
        @endif
    </div>

    <p style="margin-top:14px">
        نردّ خلال يوم عمل. ولتسريع الحلّ، اذكر في رسالتك كود المندوب، ورقم
        الفاتورة إن وُجد، ونصّ الرسالة التي ظهرت لك، وصورة للشاشة.
    </p>

    <h2>أسئلة متكرّرة</h2>

    <p><strong>نسيت كلمة المرور.</strong><br>
        الحسابات تُدار من الشركة، فاطلب من مسؤول النظام إعادة تعيينها. لا
        يوجد تسجيل ذاتي.</p>

    <p><strong>التطبيق لا يسجّل الزيارة ويطلب الموقع.</strong><br>
        الزيارة تحتاج إثبات موقع. فعّل خدمة الموقع للتطبيق من إعدادات الهاتف،
        واخرج إلى مكان مكشوف لحظة التسجيل.</p>

    <p><strong>فاتورة محصَّلة تظهر غير محصَّلة.</strong><br>
        التحصيل يظهر بعد مزامنة الجهاز. إن بقيت كذلك بعد المزامنة، راسلنا
        برقم الفاتورة وتاريخ التحصيل.</p>

    <p><strong>الكمية المتاحة لا تطابق ما معي.</strong><br>
        المتاح يُحسب من أوامر الصرف ناقص المُباع والمردود. راسلنا بكود المندوب
        وكود الصنف لنراجع الحركة.</p>

    <p><strong>أريد حذف حسابي وبياناتي.</strong><br>
        راسلنا من البريد المسجَّل في حسابك. التفاصيل في صفحة
        <a href="{{ route('public.privacy', ['locale' => 'ar']) }}">سياسة الخصوصية</a>.</p>

    <div class="note">
        للإبلاغ عن خلل يمنع العمل، اكتب «عاجل» في عنوان الرسالة مع كود المندوب.
    </div>

@else

    <h2>Contact us</h2>
    <div class="contacts">
        <a class="contact" href="mailto:{{ $contact['email'] }}">
            <div class="k">Email</div>
            <div class="v">{{ $contact['email'] }}</div>
        </a>

        @if($contact['phone'])
            <a class="contact" href="tel:{{ preg_replace('/\s+/', '', $contact['phone']) }}">
                <div class="k">Phone</div>
                <div class="v">{{ $contact['phone'] }}</div>
            </a>
        @endif

        @if($contact['address'])
            <div class="contact">
                <div class="k">Address</div>
                <div class="v" style="direction:ltr">{{ $contact['address'] }}</div>
            </div>
        @endif
    </div>

    <p style="margin-top:14px">
        We reply within one business day. To get to an answer faster, include
        your representative code, the invoice number if there is one, the exact
        message you saw, and a screenshot.
    </p>

    <h2>Common questions</h2>

    <p><strong>I forgot my password.</strong><br>
        Accounts are managed by the company, so ask your system administrator to
        reset it. There is no self sign-up.</p>

    <p><strong>The app will not log a visit and asks for location.</strong><br>
        A visit needs proof of location. Enable location for the app in your
        phone settings, and step into the open when you log it.</p>

    <p><strong>An invoice I collected still shows as uncollected.</strong><br>
        Collections appear once the device has synced. If it persists after a
        sync, send us the invoice number and the date you collected it.</p>

    <p><strong>My available stock does not match what I am carrying.</strong><br>
        Available stock is issued quantity less what was sold and returned. Send
        your representative code and the product code and we will trace the
        movement.</p>

    <p><strong>I want my account and data deleted.</strong><br>
        Write to us from the address registered on your account. The details are
        on the <a href="{{ route('public.privacy', ['locale' => 'en']) }}">privacy policy</a> page.</p>

    <div class="note">
        To report a fault that stops you working, put "Urgent" in the subject
        line along with your representative code.
    </div>

@endif
@endsection
