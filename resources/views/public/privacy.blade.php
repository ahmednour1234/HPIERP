@php($currentRoute = 'public.privacy')
@extends('public.layout')

@section('title', $locale === 'ar' ? 'سياسة الخصوصية' : 'Privacy Policy')

@section('lead', $locale === 'ar'
    ? 'ما الذي يجمعه تطبيق مناديب المبيعات، ولماذا، وكيف يمكنك التحكّم فيه.'
    : 'What the sales representative app collects, why, and how you stay in control of it.')

@section('content')
@if($locale === 'ar')

    <h2>من نحن</h2>
    <p>
        {{ $contact['company'] }} تشغّل تطبيقًا داخليًّا يستعمله مناديب المبيعات
        لتسجيل الزيارات والطلبات والتحصيلات أثناء العمل الميداني. التطبيق
        ليس موجّهًا للجمهور، ويُمنح الحساب من إدارة الشركة.
    </p>

    <h2>البيانات التي نجمعها</h2>
    <ul>
        <li><strong>بيانات الحساب:</strong> الاسم والبريد ورقم الهاتف وكود المندوب، تُنشئها الإدارة.</li>
        <li><strong>بيانات العمل:</strong> الفواتير والتحصيلات والمرتجعات وأرصدة العملاء والمخزون المصروف.</li>
        <li><strong>الموقع الجغرافي:</strong> يُسجَّل عند تسجيل زيارة أو حضور/انصراف، للتحقّق من تنفيذ الزيارة.</li>
        <li><strong>الصور:</strong> صور الفواتير وإيصالات التحصيل التي يرفعها المندوب.</li>
        <li><strong>بيانات تقنية:</strong> نوع الجهاز وإصدار التطبيق، لتشخيص الأعطال.</li>
    </ul>

    <h2>لماذا نجمعها</h2>
    <p>
        لتشغيل دورة البيع والتحصيل ومتابعتها: إصدار الفواتير، وتسجيل ما حُصِّل،
        وحساب أرصدة العملاء، وتأكيد تنفيذ الزيارات، ومطابقة المخزون المصروف
        لكل مندوب. لا نستعمل هذه البيانات في الإعلانات.
    </p>

    <h2>الموقع الجغرافي</h2>
    <p>
        يُقرأ الموقع عند تنفيذ إجراء يستدعيه فقط — تسجيل زيارة أو حضور أو
        انصراف — ولا يُتتبّع الجهاز في الخلفية. يمكن منع الإذن من إعدادات
        الهاتف، وعندها تبقى بقية وظائف التطبيق عاملة بينما تتعذّر الإجراءات
        التي تتطلّب إثبات الموقع.
    </p>

    <h2>مع من تُشارَك</h2>
    <p>
        لا تُباع البيانات ولا تُشارَك مع معلنين. يطّلع عليها موظفو الشركة
        المخوّلون بحسب صلاحياتهم، ومزوّد الاستضافة الذي يخزّنها نيابةً عنّا،
        والجهات الرسمية إذا طلبها القانون.
    </p>

    <h2>مدّة الحفظ</h2>
    <p>
        تُحفظ السجلّات المالية للمدّة التي يفرضها القانون المحاسبي والضريبي.
        وتُحذف بيانات الحساب أو تُعطَّل عند انتهاء علاقة العمل، مع بقاء
        القيود المالية المرتبطة بها كما يقتضي الحفظ القانوني.
    </p>

    <h2>الأمان</h2>
    <p>
        الاتصال بالخادم مشفَّر، والدخول بحساب وكلمة مرور، والوصول داخل النظام
        محكوم بالأدوار والصلاحيات فلا يرى المستخدم إلا ما يخصّ عمله.
    </p>

    <h2>حقوقك</h2>
    <p>
        لك أن تطلب نسخة من بياناتك أو تصحيحها أو حذف ما لا يلزم حفظه قانونًا.
        تُوجَّه الطلبات إلى الإدارة أو عبر وسائل التواصل في صفحة
        <a href="{{ route('public.support', ['locale' => 'ar']) }}">الدعم</a>.
    </p>

    <h2>حذف الحساب</h2>
    <p>
        لطلب حذف حسابك وبياناتك الشخصية، راسلنا على
        <a href="mailto:{{ $contact['email'] }}" style="direction:ltr;display:inline-block">{{ $contact['email'] }}</a>
        من البريد المسجَّل في حسابك. نردّ خلال ثلاثين يومًا، وتبقى القيود
        المالية المطلوبة قانونًا.
    </p>

    <h2>تعديلات السياسة</h2>
    <p>
        قد تُحدَّث هذه الصفحة، ويظهر تاريخ آخر تحديث في أعلاها. التغيير
        الجوهري يُبلَّغ عبر التطبيق.
    </p>

    <div class="note">
        لأي استفسار عن الخصوصية، راسلنا على
        <a href="mailto:{{ $contact['email'] }}" style="direction:ltr;display:inline-block">{{ $contact['email'] }}</a>.
    </div>

@else

    <h2>Who we are</h2>
    <p>
        {{ $contact['company'] }} operates an internal application used by its
        sales representatives to record visits, orders and collections in the
        field. It is not a consumer product; accounts are issued by the company.
    </p>

    <h2>What we collect</h2>
    <ul>
        <li><strong>Account details:</strong> name, email, phone and representative code, created by administrators.</li>
        <li><strong>Business records:</strong> invoices, collections, returns, customer balances and issued stock.</li>
        <li><strong>Location:</strong> recorded when a visit or a clock-in/out is logged, to confirm the visit took place.</li>
        <li><strong>Images:</strong> invoice photographs and collection receipts uploaded by the representative.</li>
        <li><strong>Technical data:</strong> device model and app version, used to diagnose faults.</li>
    </ul>

    <h2>Why we collect it</h2>
    <p>
        To run and audit the sales and collection cycle: issuing invoices,
        recording what was collected, maintaining customer balances, confirming
        visits, and reconciling the stock issued to each representative. None of
        it is used for advertising.
    </p>

    <h2>Location</h2>
    <p>
        Location is read only when an action requires it — logging a visit, or
        clocking in and out. The device is not tracked in the background. You may
        deny the permission in your phone's settings; the rest of the app
        continues to work, while actions that need proof of location cannot be
        completed.
    </p>

    <h2>Who it is shared with</h2>
    <p>
        We do not sell your data and we do not share it with advertisers. It is
        visible to authorised company staff according to their permissions, to
        the hosting provider that stores it on our behalf, and to authorities
        where the law requires it.
    </p>

    <h2>How long we keep it</h2>
    <p>
        Financial records are retained for the period required by accounting and
        tax law. Account data is deleted or deactivated when employment ends,
        while the financial entries attached to it remain for that same legal
        retention period.
    </p>

    <h2>Security</h2>
    <p>
        Traffic to the server is encrypted, access requires an account and
        password, and permissions inside the system are governed by roles, so a
        user sees only what their work requires.
    </p>

    <h2>Your rights</h2>
    <p>
        You may request a copy of your data, ask for it to be corrected, or ask
        for anything not under legal retention to be deleted. Send requests to
        your administrator or through the
        <a href="{{ route('public.support', ['locale' => 'en']) }}">support</a> page.
    </p>

    <h2>Deleting your account</h2>
    <p>
        To request deletion of your account and personal data, write to
        <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a> from
        the address registered on your account. We respond within thirty days;
        financial entries required by law are retained.
    </p>

    <h2>Changes to this policy</h2>
    <p>
        This page may be updated, and the date at the top shows when it last was.
        Material changes are announced in the app.
    </p>

    <div class="note">
        For any privacy question, write to
        <a href="mailto:{{ $contact['email'] }}">{{ $contact['email'] }}</a>.
    </div>

@endif
@endsection
