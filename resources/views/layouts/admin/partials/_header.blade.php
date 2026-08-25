<div id="headerMain" class="d-none">
    <header id="header" class="navbar navbar-expand-lg navbar-fixed navbar-height navbar-flush navbar-container navbar-bordered header-style">
        <div class="navbar-nav-wrap">
            <div class="navbar-brand-wrapper">
                <!-- Logo -->
                @php($shop_logo = \App\Models\BusinessSetting::where(['key' => 'shop_logo'])->first()->value)
                <a class="navbar-brand" href="{{ route('admin.dashboard') }}" aria-label="">
                    <img class="navbar-brand-logo"
                         onerror="this.src='{{ asset('public/assets/admin/img/160x160/img1.jpg') }}'"
                         src="{{ asset('storage/app/public/shop/' . $shop_logo) }}" alt="Logo">
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

            @if(\App\Models\Order::where('notification', 1)->get()->count() +\App\Models\ReserveProduct::where('notification', 1)->get()->count()  +\App\Models\HistoryInstallment::where('notification', 1)->get()->count() > 0)
                <span class="badge badge-pill badge-danger">{{ \App\Models\Order::where('notification', 1)->get()->count() +\App\Models\ReserveProduct::where('notification', 1)->get()->count()  +\App\Models\HistoryInstallment::where('notification', 1)->get()->count()+\App\Models\TransactionSeller::get()->count() }}</span>
            @endif
        </a>

        <div id="notificationDropdown" class="hs-unfold-content dropdown-unfold dropdown-menu dropdown-menu-right navbar-dropdown-menu"
             style="max-height: 400px; overflow-y: auto;">
            <div class="dropdown-item-text">
                <span class="card-title h5">Notifications</span>
            </div>
            <div class="dropdown-divider"></div>

            <!-- Installments Notifications -->
           <!-- Installments Notifications -->
@foreach (\App\Models\HistoryInstallment::where('notification', 1)->orderBy('created_at', 'desc')->get() as $installment)
    <a href="{{ route('admin.admin.notifications.show', ['id' => $installment->id, 'type' => 'installment']) }}">
        <div class="media align-items-center">
            <i class="tio-check-circle text-success mr-2"></i>
            <div class="media-body">
                <span class="text-truncate pr-2">Installment due: {{ $installment->amount }}</span>
                <small class="text-muted">{{ $installment->created_at->diffForHumans() }}</small>
            </div>
        </div>
        <div class="dropdown-divider"></div>
    </a>
@endforeach

<!-- Orders Notifications -->
@foreach (\App\Models\Order::where('notification', 1)->orderBy('created_at', 'desc')->get() as $order)
    <a href="{{ route('admin.admin.notifications.show', ['id' => $order->id, 'type' => 'order']) }}">
        <div class="media align-items-center">
            <i class="tio-shopping-cart text-primary mr-2"></i>
            <div class="media-body">
                <span class="text-truncate pr-2">New order: {{ $order->id }}</span>
                <small class="text-muted">{{ $order->created_at->diffForHumans() }}</small>
            </div>
        </div>
        <div class="dropdown-divider"></div>
    </a>
@endforeach

<!-- Reservations Notifications -->
@foreach (\App\Models\ReserveProduct::where('notification', 1)->orderBy('created_at', 'desc')->get() as $reservation)
    <a href="{{ route('admin.admin.notifications.show', ['id' => $reservation->id, 'type' => 'reserveProduct']) }}">
        <div class="media align-items-center">
            <i class="tio-calendar text-info mr-2"></i>
            <div class="media-body">
                <span class="text-truncate pr-2">Reservation made: {{ $reservation->id }}</span>
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
                                         src="{{ asset('storage/app/public/admin') }}/{{ auth('admin')->user()->image }}"
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
                                                 src="{{ asset('storage/app/public/admin') }}/{{ auth('admin')->user()->image }}"
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
