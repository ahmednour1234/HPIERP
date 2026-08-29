<style>
@font-face {
    font-family: 'Bahij';
    src: url("{{ asset('public/assets/admin/css/fonts/Bahij_TheSansArabic-Plain.ttf') }}") format('truetype');
    font-weight: normal;
    font-style: normal;
}

body {
    font-family: 'Bahij', sans-serif;
    font-weight: 150;
    color: black;
    background:rgba(173, 216, 230, 0.2);
}
.side-logo {
      background-color: #ffffff;
  }
/* ===== مظهر القائمة الجانبية ===== */

/* العنصر النشط: مستطيل أزرق كامل بدل تلوين النص وحده. */
.navbar-vertical .nav-link {
    border-radius: 8px;
    padding: 0.55rem 0.9rem;
    margin: 0 0.5rem;
    transition: background-color .15s ease, color .15s ease;
}

.navbar-vertical .active > .nav-link,
.navbar-vertical .nav-link.active {
    background-color: #2563eb;
    color: #ffffff !important;
    font-weight: 600;
}

.navbar-vertical .active > .nav-link .nav-icon,
.navbar-vertical .nav-link.active .nav-icon,
.navbar-vertical .active > .nav-link .nav-indicator-icon,
.navbar-vertical .nav-link.active .nav-indicator-icon {
    color: #ffffff !important;
}

/* تمرير المؤشر: تظليل خفيف بدل تغيير سُمك الخط، فلا يقفز النص. */
.navbar-vertical .nav-link:hover {
    background-color: rgba(255, 255, 255, .08);
    color: #ffffff;
}

.navbar-vertical .nav-link:hover .nav-indicator-icon {
    color: #ffffff;
}

.badge-danger {
    color: #161853;
    background-color: #bee0ec;
}

/* مسافات أضيق: كانت 1.5rem بين المجموعات و1rem بين العناصر، فتطول
   القائمة بلا داعٍ ويقل ما يظهر منها في الشاشة الواحدة. */
.navbar-vertical-content > ul + ul {
    margin-top: 0.5rem;
}

.navbar-vertical-content > ul > li {
    margin-bottom: 0.15rem;
}

.navbar-vertical-content > ul > li:last-child {
    margin-bottom: 0;
}

/* عناوين الأقسام أصغر وأهدأ. */
.navbar-vertical .nav-subtitle {
    font-size: 0.72rem;
    letter-spacing: .02em;
    opacity: .65;
    padding: 0.5rem 1rem 0.25rem;
}

/* الأيقونات بعرض ثابت حتى تبقى النصوص على استقامة واحدة. */
.navbar-vertical .nav-icon {
    width: 1.4rem;
    text-align: center;
    margin-left: 0.5rem;
}

</style>
<div id="sidebarMain" class="d-none">
    <aside class="aside-back js-navbar-vertical-aside navbar navbar-vertical-aside navbar-vertical navbar-vertical-fixed navbar-expand-xl navbar-bordered  ">
        <div class="navbar-vertical-container text-capitalize">
            <div class="navbar-vertical-footer-offset">
<div class="navbar-brand-wrapper d-flex align-items-center justify-content-between px-3 bg-light">
    <!-- الشعار والنص -->
    <div class="d-flex align-items-center gap-2">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('admin.dashboard') }}" aria-label="Front">
            @php($shop_logo = $badgeService->setting('shop_logo'))
                        <span class="fw-bold ms-2" style="font-size: 20px; color: #333;margin-right:15px;">نظام الإدارة</span>

            <img class="navbar-brand-logo"
                 src="{{ asset('storage/shop/' . $shop_logo) }}"
                 onerror="this.src='{{ asset('public/assets/admin/img/160x160/img2.jpg') }}'"
                 alt="{{ \App\CPU\translate('logo') }}"
                 style="height: 40px; width: auto; border-radius: 6px; margin-right:50px;">
        </a>
    </div>

  
</div>

                <!-- Content -->
                <div class="navbar-vertical-content">
                    <ul class="navbar-nav navbar-nav-lg nav-tabs">
                        <!-- Dashboards -->
        @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->dashboard == 1)
    <li class="nav-item">
        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
    </li>
    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin') ? 'show' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link"
           href="{{ route('admin.dashboard') }}"
           title="{{ \App\CPU\translate('dashboards') }}">
            <i class="tio-home-vs-1-outlined nav-icon"></i>
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                {{ \App\CPU\translate('لوحة التحكم') }}
            </span>
        </a>
    </li>
@else
    <!-- Optionally, handle the case where the user is not authenticated or doesn't have dashboard access -->
@endif
   @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->notification == 1)
       <li class="nav-item">
    <small class="tio-more-horizontal nav-subtitle-replacer"></small>
</li>
   <li class="navbar-vertical-aside-has-menu {{Request::is('admin/admin/notifications*')?'active':''}}">
        <a class="js-navbar-vertical-aside-menu-link nav-link"  href="{{route('admin.admin.notifications.listItems')}}">
        <i class="tio-notifications nav-icon"></i>
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
            {{\App\CPU\translate('الاشعارات')}}
            @if($badgeService->notificationTotal() > 0)
                <span class="badge badge-pill badge-danger ml-1">{{ $badgeService->notificationTotal() }}</span>
            @endif
        </span>
    </a>
 
</li>
@endif
 @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->requests == 1)

<li class="navbar-vertical-aside-has-menu {{Request::is('admin/admin/reservations_notification*')?'active':''}}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
        <i class="tio-shopping nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{ \App\CPU\translate('إدارة المخزون') }}</span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
        <li class="nav-item {{ Request::is('admin/admin/pos/reservations_notification') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.pos.reservation_list_notification', ['type' => 4, 'active' => 1]) }}"
               title="{{ \App\CPU\translate('list_stock') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('حجز المناديب') }}</span>
                <span class="badge badge-success ml-2">{{ $badgeCounts['reserve_type_4_active'] }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/admin/pos/reservations_notification') ? 'active' : '' }}">
<a class="nav-link" href="{{ route('admin.pos.reservation_list_notification', ['type' => 7, 'active' => 1]) }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('رد حجز المناديب') }}</span>
                <span class="badge badge-success ml-2">{{ $badgeCounts['reserve_type_7_active'] }}</span>
            </a>
        </li>
           <li class="nav-item {{Request::is('admin/vehicle-stock')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.stock.index')}}"
                                       title="{{\App\CPU\translate('list_stock')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة المنتجات داخل العربيات')}}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{Request::is('admin/vehicle-stock/create')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.stock.create')}}"
                                       title="{{\App\CPU\translate('add_stock')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('إنشاء رحلة')}}</span>
                                    </a>
                                </li>

                                  <li class="nav-item {{Request::is('admin/vehicle-stock/vehicles')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.stock.vehicles')}}"
                                       title="{{\App\CPU\translate('vehicles_stocks')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('رحلات العربيات الحالية')}}</span>
                                    </a>
                                </li>
      
                                <li class="nav-item {{Request::is('admin/pos/stocks')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.pos.stocks')}}"
                                       title="{{\App\CPU\translate('stock_travels')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate(' رحلات العربيات المنتهية')}}
                                            <span class="badge badge-success ml-2">{{ $badgeCounts['stock_orders'] }}</span>
                                        </span>
                                    </a>
                                </li>
                                    <li class="nav-item {{ Request::is('admin/pos/reservations_notification/3/2') ? 'active' : '' }}">
<a class="nav-link" href="{{ route('admin.pos.reservation_list_notification', ['type' => 3, 'active' => 2]) }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate i">{{ \App\CPU\translate('اوامر الصرف بضاعة') }}</span>
                <span class="badge badge-success ml-2">{{ $badgeCounts['reserve_type_3_active2'] }}</span>
            </a>
        </li>
    </ul>
</li>
@endif
     


<li class="navbar-vertical-aside-has-menu {{ Request::is('admin/admin/pos*') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" data-bs-toggle="collapse" data-bs-target="#salesDropdownContent" aria-expanded="{{ Request::is('admin/admin/pos*') ? 'true' : 'false' }}">
        <i class="tio-shopping nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
            {{ \App\CPU\translate('إدارة المبيعات') }}
        </span>
    </a>

    <ul class="js-navbar-vertical-aside-submenu nav nav-sub collapse {{ Request::is('admin/admin/pos*') ? 'show' : '' }}" id="salesDropdownContent">
            @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->customer == 1)


                        <!-- Customer Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/customer*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-poi-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('العملاء')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/customer*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/customer/add')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.customer.add')}}"
                                       title="{{\App\CPU\translate('add_new_customer')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اضافة عميل')}}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{Request::is('admin/customer/list')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.customer.list')}}"
                                       title="{{\App\CPU\translate('list_of_customers')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة العملاء')}}</span>
                                    </a>
                                </li>
                                     <li class="nav-item {{Request::is('admin/customer/editexport')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.customer.editexport')}}"
                                       title="{{\App\CPU\translate('list_of_customers')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اكسل العملاء')}}</span>
                                    </a>
                                </li>
                                <li class="navbar-vertical-aside-has-menu {{Request::is('admin/special*')?'active':''}}">
                                <li class="nav-item {{Request::is('admin/category/add-special-category')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.category.indexspecial')}}"
                                       title="{{\App\CPU\translate('add_new_category')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('التخصصات')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
                        
                           
                            @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->seller == 1)

                       
                        <!-- Seller Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/seller*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-poi-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('المناديب')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/seller*') || Request::is('admin/regions*') ?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/seller/add')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.seller.add')}}"
                                       title="{{\App\CPU\translate('add_new_seller')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اضافة مندوب')}}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{Request::is('admin/seller/list')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.seller.list')}}"
                                       title="{{\App\CPU\translate('list_of_seller')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة مناديب')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
                             @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->visit == 1)
     
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/visitors*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-shopping nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الزيارات')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/visitors*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/visitors')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.visitor.index')}}"
                                       title="{{\App\CPU\translate('list_stock')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة الزيارات المخططة ')}}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{Request::is('admin/visitors/create')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.visitor.create')}}"
                                       title="{{\App\CPU\translate('add_stock')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('تسجيل الزيارات المخططة')}}</span>
                                    </a>
                                </li>
                                  <li class="nav-item {{Request::is('admin/visitors/indexresult')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.visitor.indexresult')}}"
                                       title="{{\App\CPU\translate('add_stock')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة الزيارات المنفذة')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
                                @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->tracking == 1)
                    
                        <!-- Admin Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/tracking*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-poi-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الخريطة المناديب')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/tracking*')?'d-block':''}}">

                                    <li class="nav-item {{Request::is('admin/tracking/showmap')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.admin.showmap')}}"
                                       title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('خريطة المناديب')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
                                @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->sales == 1)

        <li class="nav-item {{ Request::is('admin/admin/pos*') && request('type') == 4 ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.pos.index', ['type' => 4]) }}" title="{{ \App\CPU\translate('list_stock') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('مبيعات') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/admin/pos*') && request('type') == 7 ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.pos.index', ['type' => 7]) }}" title="{{ \App\CPU\translate('list_stock') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('مرتجع مبيعات') }}</span>
            </a>
        </li>
            <li class="nav-item {{ Request::is('admin/admin/pos*') && request('type') == 12 ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.pos.index', ['type' => 12]) }}" title="{{ \App\CPU\translate('list_stock') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('عينات') }}</span>
            </a>
        </li>
            <li class="nav-item {{ Request::is('admin/admin/pos*') && request('type') == 24 ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.pos.index', ['type' => 24]) }}" title="{{ \App\CPU\translate('list_stock') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('تبرعات') }}</span>
            </a>
        </li>
        @endif
                     @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->pos == 1)
                      
                        <!-- Pos Pages -->
                        @php($orders = $badgeCounts['orders_type_4'])
                        <li class="navbar-vertical-aside-has-menu">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-shopping nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الفواتير')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub">
                                {{--
                                <li class="nav-item {{Request::is('admin/pos/')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.pos.index')}}"
                                       title="{{\App\CPU\translate('POS')}}" target="_blank">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('الفواتير')}}</span>
                                    </a>
                                </li>
                                --}}
                           <li class="nav-item {{Request::is('admin/TransactionSeller')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.TransactionSeller.index')}}"
                                       title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('تحويلات المناديب')}}</span>
                                    <span class="badge badge-success ml-2">{{ $badgeCounts['transaction_sellers'] }} </span>
                                    </a>
                                </li>
                              
                                <li class="nav-item {{Request::is('admin/pos/orders')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.pos.orders')}}"
                                       title="{{\App\CPU\translate('orders')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('المبيعات')}}
                                            <span class="badge badge-success ml-2">{{ $orders }} </span>
                                        </span>
                                    </a>
                                </li>
                                
                                <li class="nav-item {{Request::is('admin/pos/orders/archive')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.pos.orders.archive')}}"
                                       title="{{\App\CPU\translate('أرشيف الفواتير')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('أرشيف الفواتير')}}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{Request::is('admin/pos/refunds')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.pos.refunds')}}"
                                       title="{{\App\CPU\translate('refunds')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('المرتجعات المبيعات')}}
                                            <span class="badge badge-success ml-2">{{ $badgeCounts['orders_type_7'] }}</span>
                                        </span>
                                    </a>
                                </li>
                                    <li class="nav-item {{Request::is('admin/pos/sample')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.pos.sample')}}"
                                       title="{{\App\CPU\translate('sample')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('عينات')}}
                                            <span class="badge badge-success ml-2">{{ $badgeCounts['orders_type_12'] }}</span>
                                        </span>
                                    </a>
                                </li>
                                    <li class="nav-item {{Request::is('admin/pos/donations')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.pos.donations')}}"
                                       title="{{\App\CPU\translate('donations')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('تبرعات')}}
                                            <span class="badge badge-success ml-2">{{ $badgeCounts['orders_type_24'] }}</span>
                                        </span>
                                    </a>
                                </li>
                                                                @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->install == 1)

                                <li class="nav-item {{Request::is('admin/pos/installments')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.pos.installments')}}"
                                       title="{{\App\CPU\translate('installments')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('التحصيلات')}}
                                            <span class="badge badge-success ml-2">{{\App\Models\Installment::get()->count()}}</span>
                                        </span>
                                    </a>
                                </li>
                                @endif
                       <li class="nav-item {{Request::is('admin/pos/reservations')?'active':''}}">
    <a class="nav-link" href="{{ route('admin.pos.reservations', ['type' => 4, 'active' => 'all']) }}" title="{{ \App\CPU\translate('reservations') }}">
        <span class="tio-circle nav-indicator-icon"></span>
        <span class="text-truncate">{{ \App\CPU\translate('الحجوزات') }}
            <span class="badge badge-success ml-2">{{ $badgeCounts['reserve_type_4'] }}</span>
        </span>
    </a>
</li>
<li class="nav-item {{ Request::is('admin/pos/reservations') ? 'active' : '' }}">
    <a class="nav-link" href="{{ route('admin.pos.reservations', ['type' => 7, 'active' => 'all']) }}" title="{{ \App\CPU\translate('reservations') }}">
        <span class="tio-circle nav-indicator-icon"></span>
        <span class="text-truncate">{{ \App\CPU\translate('رد الحجوزات') }}
            <span class="badge badge-success ml-2">{{ $badgeCounts['reserve_type_7'] }}</span>
        </span>
    </a>
</li>

                            </ul>
                        </li>
                                          @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->reports == 1)
    <li class="nav-item">
        <small class="tio-more-horizontal nav-subtitle-replacer"></small>
        <a href="javascript:void(0);" class="nav-link" data-toggle="collapse" data-target="#reportsMenu" aria-expanded="false">
            <span class="tio-circle nav-indicator-icon"></span>
            <span class="text-truncate">{{ \App\CPU\translate('قسم التقارير') }}</span>
        </a>
    </li>
    <ul id="reportsMenu" class="nav collapse">
         <li class="nav-item {{ Request::is('admin/product/listreportexpire') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.product.getreportProducts') }}" title="{{ \App\CPU\translate('getreportProducts') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('كشف المنتجات المباعة') }}</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/reports/monthly-sales') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.reports.monthly-sales') }}" title="ملخص المبيعات الشهري">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">ملخص المبيعات الشهري</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/visitors/showResultVisitors*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.visitor.indexresult') }}" title="تقرير أداء المناديب">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">تقرير أداء المناديب</span>
            </a>
        </li>
        <li class="nav-item {{ Request::is('admin/product/listreportexpire') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.product.listreportexpire') }}" title="{{ \App\CPU\translate('list_of_products') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('كشف الصلاحية') }}</span>
            </a>
        </li>

        <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/stock*') ? 'active' : '' }}">
            <a class="js-navbar-vertical-aside-menu-link nav-link" href="{{ route('admin.stock.stock-limit') }}">
                <i class="tio-warning nav-icon"></i>
                <span class="text-truncate">{{ \App\CPU\translate('كشف نواقص') }}</span>
            </a>
        </li>

        <li class="nav-item {{ Request::is('admin/productsunlike') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.ordernotification.Productunlike') }}" title="{{ \App\CPU\translate('list_of_products') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('كشف الركود') }}</span>
            </a>
        </li>
    </ul>
            {{-- @endif قبل وسمَي الإغلاق: كان بعدهما فيبقى عنصر القائمة
                 مفتوحًا لمن لا يملك صلاحية العملاء. --}}
            @endif
    </ul>
</li>
                      @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->accounts == 1)

             
<li class="navbar-vertical-aside-has-menu {{Request::is('admin/account*')?'active':''}}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
        <i class="tio-wallet nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
            {{\App\CPU\translate('إدارة الحسابات')}}
        </span>
    </a>
    <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/account*')?'d-block':''}}">
        <li class="nav-item {{Request::is('admin/account/add')?'active':''}}">
            <a class="nav-link " href="{{route('admin.account.add')}}"
               title="{{\App\CPU\translate('إضافة حساب جديد')}}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{\App\CPU\translate('إضافة حساب جديد')}}</span>
            </a>
        </li>

        <li class="nav-item {{Request::is('admin/account/list')?'active':''}}">
            <a class="nav-link " href="{{route('admin.account.list')}}"
               title="{{\App\CPU\translate('قائمة الحسابات')}}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{\App\CPU\translate('الحسابات')}}</span>
            </a>
        </li>
        <li class="nav-item {{Request::is('admin/account/add-expense')?'active':''}}">
            <a class="nav-link " href="{{route('admin.account.add-expense')}}"
               title="{{\App\CPU\translate('إضافة مصروف جديد')}}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{\App\CPU\translate('مصروف جديد')}}</span>
            </a>
        </li>
        <li class="nav-item {{Request::is('admin/account/add-income')?'active':''}}">
            <a class="nav-link " href="{{route('admin.account.add-income')}}"
               title="{{\App\CPU\translate('إضافة دخل جديد')}}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{\App\CPU\translate('دخل جديد')}}</span>
            </a>
        </li>
        <li class="nav-item {{Request::is('admin/account/add-transfer')?'active':''}}">
            <a class="nav-link " href="{{route('admin.account.add-transfer')}}"
               title="{{\App\CPU\translate('إضافة تحويل جديد')}}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{\App\CPU\translate('تحويل جديد')}}</span>
            </a>
        </li>
    
        <li class="nav-item {{Request::is('admin/account/list-transection')?'active':''}}">
            <a class="nav-link " href="{{route('admin.account.list-transection')}}"
               title="{{\App\CPU\translate('قائمة المعاملات')}}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{\App\CPU\translate('قائمة المعاملات')}}</span>
            </a>
        </li>
            <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/taxes*') || Request::is('admin/taxes*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle">
            <i class="tio-premium-outlined nav-icon"></i>
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                {{ \App\CPU\translate('الضرائب') }}
            </span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{ Request::is('admin/taxes*') || Request::is('admin/taxes*') ? 'show' : '' }}">
            <li class="nav-item {{ Request::is('admin/taxes/list') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.taxe.list') }}" title="{{ \App\CPU\translate('list_of_taxes') }}">
                    <span class="tio-circle nav-indicator-icon"></span>
                    <span class="text-truncate">{{ \App\CPU\translate('قائمة  الضرائب ') }}</span>
                </a>
            </li>
        </ul>
    </li>
    
@if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->storage == 1)

    <li class="navbar-vertical-aside-has-menu {{ Request::is('admin/storagesseller*') || Request::is('admin/storages*') ? 'active' : '' }}">
        <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle">
            <i class="tio-premium-outlined nav-icon"></i>
            <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
                {{ \App\CPU\translate('الخزن') }}
            </span>
        </a>
        <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{ Request::is('admin/storagesseller*') || Request::is('admin/storages*') ? 'show' : '' }}">
            <li class="nav-item {{ Request::is('admin/storagesseller/list') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.storageseller.list') }}" title="{{ \App\CPU\translate('list_of_storages') }}">
                    <span class="tio-circle nav-indicator-icon"></span>
                    <span class="text-truncate">{{ \App\CPU\translate('قائمة الخزن والمناديب') }}</span>
                </a>
            </li>
            <li class="nav-item {{ Request::is('admin/storages/list') ? 'active' : '' }}">
                <a class="nav-link" href="{{ route('admin.storage.list') }}" title="{{ \App\CPU\translate('list_of_storages') }}">
                    <span class="tio-circle nav-indicator-icon"></span>
                    <span class="text-truncate">{{ \App\CPU\translate('قائمة الخزن') }}</span>
                </a>
            </li>
        </ul>
    </li>
@endif
     @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->sectionsalary == 1)
                     
                        <!-- Admin Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/salaries*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-poi-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('قسم مرتبات')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/salaries*')?'d-block':''}}">

                                    <li class="nav-item {{Request::is('admin/salaries')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.salaries.create')}}"
                                       title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('دفع مرتب')}}</span>
                                    </a>
                                </li>
                                 <li class="nav-item {{Request::is('admin/salaries')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.salaries.index')}}"
                                       title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('مراجعة المرتبات')}}</span>
                                    </a>
                                </li>
                                  
                            </ul>
                        </li>
                        @endif
    </ul>
</li>
@endif
                      @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->production == 1)

<li class="navbar-vertical-aside-has-menu {{ Request::is('admin/factories*')||Request::is('admin/materials*')||Request::is('admin/purchases*')||Request::is('admin/supply_orders*')|| Request::is('admin/production_orders*') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" data-bs-toggle="collapse" data-bs-target="#salesDropdownContent" aria-expanded="{{ Request::is('admin/factories*') ? 'true' : 'false' }}">
        <i class="tio-shopping nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
            {{ \App\CPU\translate('إدارة الإنتاج والتصنيع') }}
        </span>
    </a>

    <ul class="js-navbar-vertical-aside-submenu nav nav-sub collapse {{ Request::is('admin/factories*')||Request::is('admin/materials*')||Request::is('admin/purchases*')||Request::is('admin/supply_orders*') || Request::is('admin/production_orders*') ? 'show' : '' }}" id="salesDropdownContent">
                                 
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/product*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-premium-outlined nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('المنتجات')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/product*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/product/add')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.product.add')}}"
                                       title="{{\App\CPU\translate('add_new_product')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اضافة منتج')}}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{Request::is('admin/product/list')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.product.list')}}"
                                       title="{{\App\CPU\translate('list_of_products')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة المنتجات')}}</span>
                                    </a>
                                </li>
                                 
                      
                                <li class="nav-item {{Request::is('admin/product/bulk-import')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.product.bulk-import')}}"
                                       title="{{\App\CPU\translate('bulk_import')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('استيراد اكسل')}}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{Request::is('admin/product/bulk-export')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.product.bulk-export')}}"
                                       title="{{\App\CPU\translate('bulk_export')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اصدار اكسل')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>

<li class="navbar-vertical-aside-has-menu {{ Request::is('admin/factories*')||Request::is('admin/materials*')||Request::is('admin/purchases*')||Request::is('admin/supply_orders*')|| Request::is('admin/production_orders*') ? 'active' : '' }}">
    <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:" data-bs-toggle="collapse" data-bs-target="#salesDropdownContent" aria-expanded="{{ Request::is('admin/factories*') ? 'true' : 'false' }}">
        <i class="tio-shopping nav-icon"></i>
        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">
            {{ \App\CPU\translate('قسم التصنيع') }}
        </span>
    </a>

    <ul class="js-navbar-vertical-aside-submenu nav nav-sub collapse {{ Request::is('admin/factories*')||Request::is('admin/materials*')||Request::is('admin/purchases*')||Request::is('admin/supply_orders*') || Request::is('admin/production_orders*') ? 'show' : '' }}" id="salesDropdownContent">
        <li class="nav-item {{ Request::is('admin/factories*') ? 'active' : '' }}">
            <a class="nav-link" href="{{ route('admin.factories.index') }}" title="{{ \App\CPU\translate('factories') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('المصانع') }}</span>
            </a>
        </li>
      <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/materials/type/raw') ? 'active' : '' }}" href="{{ route('admin.materials.byType', 'raw') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('المواد الخام') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/materials/type/primary_packaging') ? 'active' : '' }}" href="{{ route('admin.materials.byType', 'primary_packaging') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('مواد التغليف الأساسية') }}</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/materials/type/secondary_packaging') ? 'active' : '' }}" href="{{ route('admin.materials.byType', 'secondary_packaging') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('مواد التغليف الثانوية') }}</span>
            </a>
        </li>    
         <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/supply_orders*') ? 'active' : '' }}" href="{{ route('admin.supply_orders.index') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('أوامر توريد للمصانع') }}</span>
            </a>
        </li>
            <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/production_orders*') ? 'active' : '' }}" href="{{ route('admin.production_orders.index') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('فواتير التصنيع') }}</span>
            </a>
        </li>
    </ul>
</li>
 </ul>
</li>
@endif

                      @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->hr == 1)
                     
                        <!-- Admin Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/salaries*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-poi-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('إدارة الموارد البشرية')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/salaries*')?'d-block':''}}">

                                    <li class="nav-item {{Request::is('admin/salaries')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.salaries.createrating')}}"
                                       title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('تقييم موظف')}}</span>
                                    </a>
                                </li>
                                
                                   <li class="nav-item {{Request::is('admin/salaries')?'active':''}}">
<a class="nav-link" href="{{ route('admin.developsellers.index', ['type' => 0]) }}" title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('تطوير موظف')}}</span>
                                    </a>
                                </li>
                                    <li class="nav-item {{Request::is('admin/salaries')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.coursesellers.index')}}"
                                       title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('كورسات موظف')}}</span>
                                    </a>
                                </li>
                                    <li class="nav-item {{Request::is('admin/salaries')?'active':''}}">
<a class="nav-link" href="{{ route('admin.developsellers.index', ['type' => 1]) }}" title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('طلبات موظف')}}</span>
                                    </a>
                                </li>
                                   <li class="nav-item {{Request::is('admin/salaries')?'active':''}}">
<a class="nav-link" href="{{ route('admin.developsellers.index', ['type' => 2]) }}" title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('طلبات اجازة')}}</span>
                                    </a>
                                </li>
                                   <li class="nav-item {{Request::is('admin/shift/add')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.shift.add')}}"
                                       title="{{\App\CPU\translate('add_new_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate i">{{\App\CPU\translate('اضافة مواعيد عمل')}}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{Request::is('admin/shift/list')|| Request::is('admin/shift/edit*')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.shift.list')}}"
                                       title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate i">{{\App\CPU\translate('قائمة مواعيد العمل')}}</span>
                                    </a>
                                </li>
                                 
                            </ul>
                        </li>
                        @endif
                      @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->attendance == 1)

  <li class="nav-item {{Request::is('admin/attendance') ?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.attendance.index')}}"
                                       title="{{\App\CPU\translate('list_of_seller')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate i">{{\App\CPU\translate('سجلات الحضور والانصراف ')}}</span>
                                    </a>
                                </li>
@endif
                        <!-- Customer Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/documents*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-poi-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('إدارة الوثائق')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/documents*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/customer/add')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.documents.create')}}"
                                       title="{{\App\CPU\translate('add_new_customer')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اضافة وثيقة')}}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{Request::is('admin/documents/list')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.documents.index')}}"
                                       title="{{\App\CPU\translate('list_of_customers')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة الوثائق')}}</span>
                                    </a>
                                </li>

                            </ul>
                        </li>
    @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->supplier == 1)
              <li class="navbar-vertical-aside-has-menu {{Request::is('admin/supplier*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-users-switch nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('إدارة المشتريات')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/supplier*')?'d-block':''}}">

                        <!-- Supplier Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/supplier*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-users-switch nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الموردين')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/supplier*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/supplier/add')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.supplier.add')}}"
                                       title="{{\App\CPU\translate('add_new_supplier')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اضافة مورد')}}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{Request::is('admin/supplier/list')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.supplier.list')}}"
                                       title="{{\App\CPU\translate('list_of_suppliers')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة الموردين')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                         <li class="nav-item">
            <a class="nav-link {{ Request::is('admin/purchases*') ? 'active' : '' }}" href="{{ route('admin.purchases.index') }}">
                <span class="tio-circle nav-indicator-icon"></span>
                <span class="text-truncate">{{ \App\CPU\translate('فاتورة مشتريات') }}</span>
            </a>
        </li>
         </ul>
                        </li>
                        <!-- Supplier end Pages -->
                       
                        @endif

                     
                        
                        <!-- Product End Pages -->
                        <!--@if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->coupon == 1)-->
                        <li class="nav-item">
                            <small
                                class="nav-subtitle">{{\App\CPU\translate('قسم البيزنس')}}</small>
                            <small class="tio-more-horizontal nav-subtitle-replacer"></small>
                        </li>
                        <!-- Coupon End Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/coupon*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                               href="{{route('admin.coupon.add-new')}}">
                                <i class="tio-gift nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('كوبونات الخصومات')}}</span>
                            </a>
                        </li>
                        <!--@endif-->
 

                        <!-- Settings Start Pages -->
                            @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->setting == 1)
                                                    <li class="navbar-vertical-aside-has-menu {{Request::is('admin/business-settings*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-settings nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الاعدادات')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/business-settings*')?'d-block':''}}">
                                       @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->regions == 1)

                        <!-- Admin Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/tracking*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-poi-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('المناطق')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/tracking*')?'d-block':''}}">

                                <li class="nav-item {{Request::is('admin/regions/list') ? 'active':''}}">
                                    <a class="nav-link " href="{{route('admin.regions.list')}}"
                                       title="{{\App\CPU\translate('regions')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('المناطق')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
  @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->unit == 1)
                            
                                  <li class="navbar-vertical-aside-has-menu {{Request::is('admin/unit*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-category nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الوحدات منتجات')}}</span>
                            </a>
                        <!--{{-- <!-- Brand -->-->
                        <!--<li class="navbar-vertical-aside-has-menu {{Request::is('admin/unit*')?'active':''}}">-->
                        <!--    <a class="js-navbar-vertical-aside-menu-link nav-link"-->
                        <!--       href="{{route('admin.brand.add')}}"-->
                        <!--    >-->
                        <!--        <i class="tio-star nav-icon"></i>-->
                        <!--        <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">-->
                        <!--            {{\App\CPU\translate('brand')}}-->
                        <!--        </span>-->
                        <!--    </a>-->
                        <!--</li>-->
                        <!--Brand end --> --}}
                        <!-- unit -->
                                                    <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/unit*')?'d-block':''}}">

                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/unit*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link"
                               href="{{route('admin.unit.index')}}"
                            >
                                <i class="tio-calculator nav-icon"></i>
<span class="text-truncate">                                    {{\App\CPU\translate('وحدات')}}
                                </span>
                            </a>
                        </li>
                                </ul>
                        </li>
                        @endif
                                @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->cat == 1)
   
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/category*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-category nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الاقسام')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/category*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/category/add')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.category.add')}}"
                                       title="{{\App\CPU\translate('add_new_category')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('الاقسام')}}</span>
                                    </a>
                                </li>

                                {{-- <li class="nav-item {{Request::is('admin/category/add-sub-category')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.category.add-sub-category')}}"
                                       title="{{\App\CPU\translate('add_new_sub_category')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('sub_category')}}</span>
                                    </a>
                                </li> --}}
                            </ul>
                        </li>
                            @endif
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/business-settings*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-settings nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الاعدادات')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/business-settings*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/business-settings/shop-setup')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.business-settings.shop-setup')}}"
                                    >
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span
                                            class="text-truncate">{{\App\CPU\translate('الشركة')}} {{\App\CPU\translate('تعديل الاعدادات')}}</span>
                                    </a>
                                </li>
                                
                            </ul>
                        </li>
                                                    @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->store == 1)

                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/store*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:">
                                <i class="tio-shopping nav-icon"></i>
                                <span class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('العربيات')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/store*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/stores')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.stores.index')}}"
                                       title="{{\App\CPU\translate('list_stock')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة العربيات')}}</span>
                                    </a>
                                </li>
                                <li class="nav-item {{Request::is('admin/stores/create')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.stores.create')}}"
                                       title="{{\App\CPU\translate('add_stock')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اضافة عربية')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
@endif
        @if(auth()->guard('admin')->check() && auth()->guard('admin')->user()->admin == 1)
                        
                        <!-- Admin Pages -->
                        <li class="navbar-vertical-aside-has-menu {{Request::is('admin/admin*')?'active':''}}">
                            <a class="js-navbar-vertical-aside-menu-link nav-link nav-link-toggle" href="javascript:"
                            >
                                <i class="tio-poi-user nav-icon"></i>
                                <span
                                    class="navbar-vertical-aside-mini-mode-hidden-elements text-truncate">{{\App\CPU\translate('الادمن')}}</span>
                            </a>
                            <ul class="js-navbar-vertical-aside-submenu nav nav-sub {{Request::is('admin/admin*')?'d-block':''}}">
                                <li class="nav-item {{Request::is('admin/admin/add')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.admin.add')}}"
                                       title="{{\App\CPU\translate('add_new_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('اضافة ادمن')}}</span>
                                    </a>
                                </li>

                                <li class="nav-item {{Request::is('admin/admin/list')?'active':''}}">
                                    <a class="nav-link " href="{{route('admin.admin.list')}}"
                                       title="{{\App\CPU\translate('list_of_admin')}}">
                                        <span class="tio-circle nav-indicator-icon"></span>
                                        <span class="text-truncate">{{\App\CPU\translate('قائمة الادمن')}}</span>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endif
             
                            </ul>
                        </li>
                        
                          
                        
                                            @endif

                    </ul>
                </div>
                <!-- End Content -->
            </div>
        </div>
    </aside>
</div>



/div>



