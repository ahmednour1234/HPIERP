<div id="headerMain" class="d-none">
    <header id="header" class="navbar navbar-expand-lg navbar-fixed navbar-height navbar-flush navbar-container navbar-bordered header-style">
        <div class="navbar-nav-wrap">
            <div class="navbar-brand-wrapper">
                <!-- Logo -->
                @php($shop_logo = $badgeService->setting('shop_logo'))
                <a class="navbar-brand" href="{{ route('admin.dashboard') }}" aria-label="">
                    <img class="navbar-brand-logo"
                         onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                         src="{{ asset('storage/shop/' . $shop_logo) }}" alt="Logo">
                </a>
                <!-- End Logo -->
            </div>

            <div class="navbar-nav-wrap-content-left">
                <!-- Navbar Vertical Toggle -->
                <button type="button" class="js-navbar-vertical-aside-toggle-invoker close mr-3">
                    <i class="tio-first-page navbar-vertical-aside-toggle-short-align" data-toggle="tooltip" data-placement="right" title="Collapse"></i>
                    <i class="tio-last-page navbar-vertical-aside-toggle-full-align"
                       data-template='<div class="tooltip d-none d-sm-block" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>'
                       data-toggle="tooltip" data-placement="right" title="Expand"></i>
                </button>
                <!-- End Navbar Vertical Toggle -->
            </div>

            <!-- Secondary Content -->
            <div class="navbar-nav-wrap-content-right">
                <!-- Navbar -->
                <ul class="navbar-nav align-items-center flex-row">
                    <li class="nav-item d-sm-inline-block">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary"
                               href="{{ route('admin.pos.index', ['type' => 4]) }}" target="_blank">
                                <span class="m-3 text-white">{{ \App\CPU\translate('POS') }}</span>
                            </a>
                        </div>
                    </li>

                    <li class="nav-item d-sm-inline-block">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary rounded-circle"
                               href="{{ route('admin.pos.orders') }}">
                                <i class="tio-shopping-basket text-white"></i>
                            </a>
                        </div>
                    </li>
                    
        @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->notification == 1)
<li class="nav-item">
    <div class="hs-unfold">


        <a class="js-hs-unfold-invoker btn btn-icon btn-ghost-secondary" href="javascript:;"
           data-hs-unfold-options='{
                "target": "#notificationDropdown",
                "type": "css-animation"
            }'>
            <i class="tio-notifications text-white"></i>

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
                View all notifications
            </a>
        </div>
    </div>
</li>
@endif

                    {{-- اللغة: زر عرض فقط في الوقت الحالي.
                         المشروع يحمل مجلد لغة واحدًا (en) تختلط فيه العربية
                         والإنجليزية، فلا توجد لغة ثانية يُبدَّل إليها. عند
                         تجهيز ملف عربي منفصل يُربط الزر بمسار التبديل. --}}
                    <li class="nav-item d-none d-sm-inline-block">
                        <a class="btn btn-icon btn-ghost-secondary d-flex align-items-center px-2"
                           href="javascript:;"
                           title="{{ \App\CPU\translate('اللغة') }}">
                            <i class="tio-globe text-white"></i>
                            <span class="text-white ml-1 small">{{ \App\CPU\translate('العربية') }}</span>
                        </a>
                    </li>

                    {{-- الإعدادات: نفس وجهة الرابط الموجود في قائمة الحساب --}}
                    <li class="nav-item d-none d-sm-inline-block">
                        <a class="btn btn-icon btn-ghost-secondary rounded-circle"
                           href="{{ route('admin.settings') }}"
                           title="{{ \App\CPU\translate('settings') }}">
                            <i class="tio-settings text-white"></i>
                        </a>
                    </li>

                    {{-- ملء الشاشة --}}
                    <li class="nav-item d-none d-sm-inline-block">
                        <a class="btn btn-icon btn-ghost-secondary rounded-circle" href="javascript:;"
                           id="navbarFullscreen"
                           title="{{ \App\CPU\translate('ملء الشاشة') }}">
                            <i class="tio-fullscreen text-white"></i>
                        </a>
                    </li>

                    <li class="nav-item">
                        <div class="hs-unfold">
                            <a class="js-hs-unfold-invoker navbar-dropdown-account-wrapper" href="javascript:;"
                               data-hs-unfold-options='{
                                     "target": "#accountNavbarDropdown",
                                     "type": "css-animation"
                                   }'>
                                <div class="avatar avatar-sm avatar-circle">
                                    <img class="avatar-img"
                                         onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                         src="{{ asset('storage/admin') }}/{{ auth('admin')->user()->image }}"
                                         alt="{{ \App\CPU\translate('image_description') }}">
                                    <span class="avatar-status avatar-sm-status avatar-status-success"></span>
                                </div>
                            </a>

                            <div id="accountNavbarDropdown"
                                 class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu navbar-dropdown-account">
                                <div class="dropdown-item-text">
                                    <div class="media align-items-center">
                                        <div class="avatar avatar-sm avatar-circle mr-2">
                                            <img class="avatar-img"
                                                 onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                                                 src="{{ asset('storage/admin') }}/{{ auth('admin')->user()->image }}"
                                                 alt="{{ \App\CPU\translate('image_description') }}">
                                        </div>
                                        <div class="media-body">
                                            <span class="card-title h5">{{ auth('admin')->user()->f_name }}</span>
                                            <span class="card-text">{{ auth('admin')->user()->email }}</span>
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

@push('script_2')
    <script>
        "use strict";

        document.addEventListener('DOMContentLoaded', function () {
            var btn = document.getElementById('navbarFullscreen');
            if (!btn) { return; }

            btn.addEventListener('click', function (e) {
                e.preventDefault();

                var icon = btn.querySelector('i');

                if (!document.fullscreenElement) {
                    // الطلب قد يُرفض (إعدادات المتصفح)، فنتجاهل الرفض بهدوء.
                    var req = document.documentElement.requestFullscreen
                           || document.documentElement.webkitRequestFullscreen;

                    if (req) {
                        var p = req.call(document.documentElement);
                        if (p && p.catch) { p.catch(function () {}); }
                    }

                    if (icon) { icon.className = 'tio-fullscreen-exit text-white'; }
                } else {
                    var exit = document.exitFullscreen || document.webkitExitFullscreen;
                    if (exit) { exit.call(document); }

                    if (icon) { icon.className = 'tio-fullscreen text-white'; }
                }
            });
        });
    </script>
@endpush
