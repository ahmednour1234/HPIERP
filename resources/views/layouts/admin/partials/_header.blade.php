@php
    // أسماء الأيام والشهور عربية صراحةً: translatedFormat يتبع لغة
    // التطبيق وهي en، فيكتب "Wednesday" و"September" في شريط عربي.
    $HD_DAYS = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    $HD_MONTHS = [
        1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
        5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
        9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
    ];
    $hdUser = auth('admin')->user();
@endphp

<style>
    /* شريط علوي فاتح: كان كحليًّا يكرّر لون القائمة الجانبية فيبتلعها. */
    #header.header-style {
        background: #ffffff;
        padding-inline: .9rem;
        border: 1px solid #e3ecf4;
        box-shadow: 0 2px 10px rgba(20, 57, 92, .05);
    }

    #header .hd-date {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        font-size: .82rem;
        font-weight: 600;
        color: #7c8ea1;
        white-space: nowrap;
    }

    /* البحث يذهب إلى قائمة الفواتير، وهي الشاشة التي تملك بحثًا فعليًّا،
       فلا يُعرض حقل لا يؤدي إلى شيء. */
    #header .hd-search {
        position: relative;
        width: min(420px, 38vw);
        margin: 0;
    }

    #header .hd-search input {
        width: 100%;
        border: 1px solid #e3ecf4;
        background: #f6fafd;
        border-radius: 10px;
        /* منطقية لا يمين/يسار: الحشو الطبيعي انقلب في RTL فجلس النص
           تحت شارة الاختصار واختفت أيقونة البحث. */
        padding: .5rem .8rem;
        padding-inline-start: 2.4rem;
        padding-inline-end: 3.4rem;
        font-size: .84rem;
        color: #1c2b3a;
    }

    #header .hd-search input::placeholder {
        color: #9aabbd;
        /* النص أطول من الحقل على الشاشات الضيقة، فيُقصّ بنقاط بدل أن
           يُبتر عند الحافة. */
        text-overflow: ellipsis;
    }

    #header .hd-search input:focus {
        outline: 0;
        background: #fff;
        border-color: #8ec5ef;
        box-shadow: 0 0 0 .18rem rgba(142, 197, 239, .28);
    }

    #header .hd-search .s-icon {
        position: absolute;
        inset-inline-start: .85rem;
        top: 50%;
        transform: translateY(-50%);
        color: #7c8ea1;
        font-size: .95rem;
        pointer-events: none;
    }

    #header .hd-kbd {
        position: absolute;
        inset-inline-end: .55rem;
        top: 50%;
        transform: translateY(-50%);
        font-size: .68rem;
        font-weight: 700;
        color: #7c8ea1;
        background: #fff;
        border: 1px solid #e3ecf4;
        border-radius: 6px;
        padding: .05rem .35rem;
        direction: ltr;
        pointer-events: none;
    }

    /* الأيقونات كانت بيضاء على شريط كحلي، فتختفي على الأبيض. */
    #header .btn-ghost-secondary,
    #header .tio-notifications { color: #4a6076 !important; }

    #header .btn-ghost-secondary:hover { background: #eaf4fb; }

    #header .btn-icon {
        width: 34px;
        height: 34px;
        min-height: 34px;
    }

    /* الشارة نقطة صغيرة فوق الجرس لا لوحة حمراء بجواره: العدد قد يبلغ
       أربعة أرقام فيدفع بقية العناصر. */
    #header .nav-item .badge-danger {
        position: absolute;
        top: .1rem;
        inset-inline-end: .1rem;
        min-width: 17px;
        height: 17px;
        padding: 0 4px;
        font-size: .62rem;
        font-weight: 700;
        line-height: 17px;
        text-align: center;
        background-color: #e5484d;
        color: #fff;
        border: 2px solid #fff;
        border-radius: 99px;
    }

    #header .nav-item .js-hs-unfold-invoker { position: relative; }

    /* بطاقة المستخدم: الاسم والدور بدل صورة مجرّدة. */
    #header .hd-user {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .2rem;
        padding-inline-end: .7rem;
        border: 1px solid #e3ecf4;
        border-radius: 99px;
        background: #fff;
    }

    #header .hd-user:hover { background: #f6fafd; text-decoration: none; }

    #header .hd-user .u-name {
        font-size: .82rem;
        font-weight: 700;
        color: #1c2b3a;
        line-height: 1.2;
    }

    #header .hd-user .u-role { font-size: .7rem; color: #7c8ea1; line-height: 1.2; }

    /* ثلاثة أعمدة: التاريخ يمينًا، البحث في المنتصف، الحساب يسارًا.
       الطرفان بعرض متساوٍ (1fr) فيبقى البحث في وسط الشريط فعليًّا لا
       في وسط ما تبقّى منه. */
    #header .navbar-nav-wrap {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        gap: 1rem;
        width: 100%;
    }

    #header .hd-start { justify-self: start; }
    #header .hd-mid   { justify-self: center; }
    #header .hd-end   { justify-self: end; }

    /* ضيّقًا: يختفي التاريخ واسم المستخدم، ويأخذ البحث ما تبقّى. */
    @media (max-width: 767.98px) {
        #header .hd-date,
        #header .hd-user .u-meta { display: none; }

        #header .navbar-nav-wrap { grid-template-columns: auto 1fr auto; }
        #header .hd-search { width: 100%; }
    }
</style>

<div id="headerMain" class="d-none">
    <header id="header" class="navbar navbar-expand-lg navbar-fixed navbar-height navbar-flush navbar-container navbar-bordered header-style">
        <div class="navbar-nav-wrap">

            {{-- العمود الأول: زرّ الطيّ والتاريخ، في أقصى جهة البداية. --}}
            <div class="hd-start d-flex align-items-center" style="gap: .75rem;">
                <!-- Navbar Vertical Toggle -->
                <button type="button" class="js-navbar-vertical-aside-toggle-invoker close mr-2">
                    <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip" data-placement="right" title="Collapse"></i>
                    <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                       data-template='<div class="tooltip d-none d-sm-block" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
                       data-toggle="tooltip" data-placement="right" title="Expand"></i>
                </button>
                <!-- End Navbar Vertical Toggle -->

                <span class="hd-date">
                    <i class="tio-calendar"></i>
                    {{ $HD_DAYS[(int) now()->format('w')] }}
                    {{ now()->format('j') }}
                    {{ $HD_MONTHS[(int) now()->format('n')] }}
                    {{ now()->format('Y') }}
                </span>
            </div>

            {{-- العمود الأوسط: البحث وحده، فيبقى في منتصف الشريط. --}}
            <form class="hd-search hd-mid" action="{{ route('admin.pos.orders') }}" method="GET" role="search">
                <i class="tio-search s-icon"></i>
                <input type="search" name="search" id="hdSearch"
                       value="{{ request('search') }}"
                       placeholder="{{ \App\CPU\translate('ابحث عن عميل، فاتورة، منتج') }}…"
                       autocomplete="off">
                <span class="hd-kbd">Ctrl K</span>
            </form>

            <!-- Secondary Content -->
            <div class="navbar-nav-wrap-content-right hd-end">
                <!-- Navbar -->
                <ul class="navbar-nav align-items-center flex-row" style="gap: .4rem;">
        @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->notification == 1)
<li class="nav-item">
    <div class="hs-unfold">


        <a class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary" href="javascript:;"
           data-hs-unfold-options='{
                "target": "#notificationDropdown",
                "type": "css-animation"
            }'>
            <i class="tio-notifications"></i>

            {{-- العدد يأتي محسوبًا مرة واحدة من AdminBadgeCounts بدل ست
                 عمليات ->get()->count() متكررة هنا. --}}
            @if($badgeService->notificationTotal() > 0)
                <span class="badge badge-pill badge-danger">{{ $badgeService->notificationTotal() }}</span>
            @endif
        </a>

        <div id="notificationDropdown" class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu"
             style="max-height: 400px; overflow-y: auto;">
            <div class="dropdown-item-text">
                <span class="card-title h5">{{ \App\CPU\translate('Notifications') }}</span>
            </div>
            <div class="dropdown-divider"></div>

            <!-- Installments Notifications -->
@foreach ($badgeService->notifications()['installments'] as $installment)
    <a href="{{ route('admin.admin.notifications.show', ['id' => $installment->id, 'type' => 'installment']) }}">
        <div class="media align-items-center">
            <i class="tio-check-circle text-success mr-2"></i>
            <div class="media-body">
                <span class="text-truncate pr-2">{{ \App\CPU\translate('Installment due') }}: {{ $installment->amount }}</span>
                <small class="text-muted">{{ $installment->created_at->diffForHumans() }}</small>
            </div>
        </div>
        <div class="dropdown-divider"></div>
    </a>
@endforeach

<!-- Orders Notifications -->
@foreach ($badgeService->notifications()['orders'] as $order)
    <a href="{{ route('admin.admin.notifications.show', ['id' => $order->id, 'type' => 'order']) }}">
        <div class="media align-items-center">
            <i class="tio-shopping-cart text-primary mr-2"></i>
            <div class="media-body">
                <span class="text-truncate pr-2">{{ \App\CPU\translate('New order') }}: {{ $order->id }}</span>
                <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
            </div>
        </div>
        <div class="dropdown-divider"></div>
    </a>
@endforeach

<!-- Reservations Notifications -->
@foreach ($badgeService->notifications()['reservations'] as $reservation)
    <a href="{{ route('admin.admin.notifications.show', ['id' => $reservation->id, 'type' => 'reserveProduct']) }}">
        <div class="media align-items-center">
            <i class="tio-calendar text-info mr-2"></i>
            <div class="media-body">
                <span class="text-truncate pr-2">{{ \App\CPU\translate('Reservation made') }}: {{ $reservation->id }}</span>
                <small class="text-muted">{{ $reservation->created_at->diffForHumans() }}</small>
            </div>
        </div>
        <div class="dropdown-divider"></div>
    </a>
@endforeach

            <!-- View All Notifications -->
            <a class="dropdown-item text-center" href="{{ route('admin.admin.notifications.listItems') }}">
                {{ \App\CPU\translate('عرض كل الاشعارات') }}
            </a>
        </div>
    </div>
</li>
@endif
                    <li class="nav-item">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker hd-user" href="javascript:;"
                               data-hs-unfold-options='{
                                     "target": "#accountNavbarDropdown",
                                     "type": "css-animation"
                                   }'>
                                <div class="avatar avatar-sm avatar-circle">
                                    <img class="avatar-img"
                                         onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                         src="{{ asset('storage/admin') }}/{{ $hdUser->image }}"
                                         alt="{{ \App\CPU\translate('image_description') }}">
                                    <span class="avatar-status avatar-sm-status avatar-status-success"></span>
                                </div>

                                <span class="u-meta d-flex flex-column">
                                    <span class="u-name">{{ trim($hdUser->f_name . ' ' . $hdUser->l_name) }}</span>
                                    {{-- السوبر أدمن وحده يُعرّف بذلك؛ غيره "مستخدم". --}}
                                    <span class="u-role">
                                        {{ $hdUser->is_super
                                            ? \App\CPU\translate('مدير النظام')
                                            : \App\CPU\translate('مستخدم') }}
                                    </span>
                                </span>

                                <i class="tio-chevron-down" style="font-size:.8rem;color:#7c8ea1;"></i>
                            </a>

                            <div id="accountNavbarDropdown"
                                 class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account">
                                <div class="dropdown-item-text">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-sm avatar-circle mr-2">
                                            <img class="avatar-img"
                                                 onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                 src="{{ asset('storage/admin') }}/{{ $hdUser->image }}"
                                                 alt="{{ \App\CPU\translate('image_description') }}">
                                        </div>
                                        <div class="media-body">
                                            <span class="card-title h5">{{ $hdUser->f_name }}</span>
                                            <span class="card-text">{{ $hdUser->email }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="dropdown-divider"></div>

                                <a class="dropdown-item" href="{{ route('admin.settings') }}">
                                    <span class="text-truncate pr-2" title="{{ \App\CPU\translate('settings') }}">{{ \App\CPU\translate('settings') }}</span>
                                </a>

                                <div class="dropdown-divider"></div>

                                <a class="dropdown-item" href="javascript:" onclick="Swal.fire({
                                    title: 'Do you want to logout?',
                                    showDenyButton: true,
                                    showCancelButton: true,
                                    confirmButtonColor: '#161853',
                                    cancelButtonColor: '#363636',
                                    confirmButtonText: `Yes`,
                                    denyButtonText: `Don't Logout`,
                                    }).then((result) => {
                                    if (result.value) {
                                    location.href='{{ route('admin.auth.logout') }}';
                                    } else {
                                    Swal.fire('Canceled', '', 'info')
                                    }
                                    })">
                                    <span class="text-truncate pr-2" title="Sign out">{{ \App\CPU\translate('sign_out') }}</span>
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
                <!-- End Navbar -->
            </div>
            <!-- End Secondary Content -->
        </div>
    </header>
</div>
<div id="headerFluid" class="d-none"></div>
<div id="headerDouble" class="d-none"></div>

<script>
    // Ctrl+K يركّز البحث، كما يعلن الاختصار المعروض في الحقل.
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            var box = document.getElementById('hdSearch');

            if (box) {
                e.preventDefault();
                box.focus();
                box.select();
            }
        }
    });
</script>
