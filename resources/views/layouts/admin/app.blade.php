<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width">
    <!-- Title -->
    <title>@yield('title')</title>
    <!-- Favicon -->
    <link rel="shortcut icon" href="">
    <!-- Font -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/google-fonts.css">
    <!-- CSS Implementing Plugins -->
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('public/assets/admin/img/photo_2025-06-10_23-16-52.png') }}"/>

    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/theme.minc619.css?v=1.0">
    <!-- select picker -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/bootstrap-select.min.css"/>
    @stack('css_or_js')

    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>

    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">
</head>

<body class="footer-offset">

    <!-- Toggler -->
    <div class="direction-toggle">
        <i class="tio-settings"></i>
        <span></span>
    </div>
    <!-- Toggler -->

{{--loader--}}
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div id="loading" class="d-none">
                <div class="loader-img">
                    <img width="200" src="{{asset('public/assets/admin/img/loader.gif')}}">
                </div>
            </div>
        </div>
    </div>
</div>
{{--loader--}}

<!-- JS Preview mode only -->
@include('layouts.admin.partials._header')
@include('layouts.admin.partials._sidebar')
<!-- END ONLY DEV -->

<main id="content" role="main" class="main pointer-event">
    <!-- Content -->
@yield('content')
<!-- End Content -->

    <!-- Footer -->
@include('layouts.admin.partials._footer')
<!-- End Footer -->

    <div class="modal fade" id="popup-modal">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">
                            <center>
                                <h2 class="title-new-order">
                                    <i class="tio-shopping-cart-outlined"></i> {{\App\CPU\translate('You_have_new_order,_Check_Please')}}.
                                </h2>
                                <hr>
                                <button onclick="check_order()" class="btn btn-primary">{{\App\CPU\translate('Ok,_let_me_check')}}</button>
                            </center>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>
<!-- ========== END MAIN CONTENT ========== -->

<!-- ========== END SECONDARY CONTENTS ========== -->
<script src="{{asset('public/assets/admin')}}/js/custom.js"></script>
<!-- JS Implementing Plugins -->

@stack('script')

<!-- JS Front -->
<script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/sweet_alert.js"></script>
<script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
<!-- select picker -->
<script src="{{asset('public/assets/admin')}}/js/bootstrap-select.min.js"></script>
<script>
    $(document).ready(function () {
        if (!$.fn.selectpicker) {
            return;
        }

        $('select[multiple]')
            .not('.select2-multiple, .select2-hidden-accessible, .js-select2-custom, .selectpicker-ignore')
            .each(function () {
                var $select = $(this);

                if ($select.data('selectpicker')) {
                    $select.selectpicker('refresh');
                    $select.closest('.bootstrap-select').addClass('searchable-multiple-picker');
                    return;
                }

                var id = $select.attr('id');
                var label = id ? $('label[for="' + id + '"]').first().text().trim() : '';
                if (!label) {
                    label = $select.closest('.form-group, .col-md-6, .col-lg-3, .col-lg-4, .col-12').find('label').first().text().trim();
                }

                var placeholder = $select.data('placeholder') || $select.attr('title') || label || 'اختر';

                $select
                    .addClass('selectpicker searchable-multiple-picker')
                    .attr('title', placeholder)
                    .attr('data-live-search', 'true')
                    .attr('data-actions-box', 'true')
                    .attr('data-width', '100%')
                    .attr('data-size', '8')
                    .attr('data-selected-text-format', 'count > 2')
                    .attr('data-count-selected-text', '{0} محدد')
                    .attr('data-select-all-text', 'تحديد الكل')
                    .attr('data-deselect-all-text', 'إلغاء الكل')
                    .attr('data-none-results-text', 'لا توجد نتائج مطابقة {0}');

                $select.selectpicker({
                    liveSearch: true,
                    actionsBox: true,
                    style: '',
                    styleBase: 'form-control'
                });
                $select.closest('.bootstrap-select').addClass('searchable-multiple-picker');
            });
    });
</script>
<!-- ck editor -->
<script src="{{asset('public/assets/admin')}}/js/ck-editor.js"></script>
{!! Toastr::message() !!}

@if ($errors->any())
    <script>
        @foreach($errors->all() as $error)
        toastr.error('{{$error}}', Error, {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
    </script>
@endif
<!-- Toggle Direction Init -->
<script>
    $(document).on('ready', function(){

        $(".direction-toggle").on("click", function () {
            setDirection(localStorage.getItem("direction"));
        });

        function setDirection(direction) {
            if (direction == "rtl") {
                localStorage.setItem("direction", "ltr");
                $("html").attr('dir', 'ltr');
            $(".direction-toggle").find('span').text('Toggle RTL')
            } else {
                localStorage.setItem("direction", "rtl");
                $("html").attr('dir', 'rtl');
            $(".direction-toggle").find('span').text('Toggle LTR')
            }
        }

        if (localStorage.getItem("direction") == "rtl") {
            $("html").attr('dir', "rtl");
            $(".direction-toggle").find('span').text('Toggle LTR')
        } else {
            $("html").attr('dir', "ltr");
            $(".direction-toggle").find('span').text('Toggle RTL')
        }

    })



    $(document).ready(function () {
        if ($(".navbar-vertical-content li.active").length) {
            $('.navbar-vertical-content').animate({
                scrollTop: $(".navbar-vertical-content li.active").offset().top - 150
            }, 10);
        }

        function alignAdminTablesToRight() {
            if ($('html').attr('dir') !== 'rtl') {
                return;
            }

            $('.table-responsive, .datatable-custom').each(function () {
                this.scrollLeft = this.scrollWidth;
            });
        }

        alignAdminTablesToRight();
        setTimeout(alignAdminTablesToRight, 300);
        $(document).on('draw.dt shown.bs.tab shown.bs.modal', alignAdminTablesToRight);

        function initAdminTableScrollProxy() {
            var proxy = document.getElementById('admin-table-scroll-proxy');
            if (!proxy) {
                proxy = document.createElement('div');
                proxy.id = 'admin-table-scroll-proxy';
                proxy.className = 'admin-table-scroll-proxy';
                proxy.innerHTML = '<div class="admin-table-scroll-proxy__spacer"></div>';
                document.body.appendChild(proxy);
            }

            var spacer = proxy.firstElementChild;
            var activeScroller = null;
            var syncing = false;

            function getScrollers() {
                return Array.prototype.slice.call(document.querySelectorAll('.table-responsive, .datatable-custom'))
                    .filter(function (el) {
                        return el.offsetParent !== null && el.scrollWidth > el.clientWidth + 6;
                    });
            }

            function pickScroller() {
                var scrollers = getScrollers();
                var targetLine = window.innerHeight * .58;
                var best = null;
                var bestScore = Infinity;

                scrollers.forEach(function (el) {
                    var rect = el.getBoundingClientRect();
                    if (rect.bottom < 0 || rect.top > window.innerHeight) {
                        return;
                    }

                    var score = Math.abs(((rect.top + rect.bottom) / 2) - targetLine);
                    if (score < bestScore) {
                        best = el;
                        bestScore = score;
                    }
                });

                return best || scrollers[0] || null;
            }

            function onActiveScroll() {
                if (!activeScroller || syncing) {
                    return;
                }

                syncing = true;
                proxy.scrollLeft = activeScroller.scrollLeft;
                syncing = false;
            }

            function bindScroller(scroller) {
                if (activeScroller === scroller) {
                    return;
                }

                if (activeScroller) {
                    activeScroller.removeEventListener('scroll', onActiveScroll);
                }

                activeScroller = scroller;

                if (activeScroller) {
                    activeScroller.addEventListener('scroll', onActiveScroll, { passive: true });
                }
            }

            function updateProxy() {
                var scroller = pickScroller();
                bindScroller(scroller);

                if (!activeScroller) {
                    proxy.style.display = 'none';
                    return;
                }

                var rect = activeScroller.getBoundingClientRect();
                var left = Math.max(8, rect.left);
                var width = Math.max(160, Math.min(rect.width, window.innerWidth - left - 8));

                proxy.style.display = 'block';
                proxy.style.left = left + 'px';
                proxy.style.width = width + 'px';
                spacer.style.width = activeScroller.scrollWidth + 'px';

                syncing = true;
                proxy.scrollLeft = activeScroller.scrollLeft;
                syncing = false;
            }

            proxy.addEventListener('scroll', function () {
                if (!activeScroller || syncing) {
                    return;
                }

                syncing = true;
                activeScroller.scrollLeft = proxy.scrollLeft;
                syncing = false;
            }, { passive: true });

            window.addEventListener('scroll', updateProxy, { passive: true });
            window.addEventListener('resize', updateProxy);
            $(document).on('draw.dt shown.bs.tab shown.bs.modal hidden.bs.modal', updateProxy);

            setTimeout(updateProxy, 150);
            setTimeout(updateProxy, 700);
        }

        initAdminTableScrollProxy();
    });
</script>
<!-- JS Plugins Init. -->
<script src="{{asset('public/assets/admin')}}/js/app-page.js"></script>


{{-- النوافذ المنبثقة.
     القالب يحمّل نسخة مختصرة من bootstrap بلا قواعد .modal وبلا
     bootstrap.js، فكانت كل نافذة تُعرض داخل الصفحة كقسم عادي بدل أن تطفو
     فوقها. القواعد والسكربت هنا تخدم كل الشاشات (31 شاشة) مرة واحدة.
     !important لأن ملفات القالب تُحمّل بعد هذه الكتلة. --}}
<style>
    .modal { display: none !important; }
    .modal.show { display: block !important; }
    .modal-backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
                      background: #000; opacity: .5; z-index: 1040; }
    .modal.show { z-index: 1050; }
    body.modal-open { overflow: hidden; }
</style>

<script>
    "use strict";

    document.addEventListener('DOMContentLoaded', function () {

        function openModal(modal) {
            if (!modal) { return; }

            modal.classList.add('show');
            document.body.classList.add('modal-open');

            if (!document.querySelector('.modal-backdrop')) {
                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop';
                document.body.appendChild(backdrop);
            }
        }

        function closeModal(modal) {
            if (!modal) { return; }

            modal.classList.remove('show');
            document.body.classList.remove('modal-open');

            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) { backdrop.remove(); }
        }

        // مفوَّض على المستند: يشمل الصفوف المضافة لاحقًا عبر AJAX.
        document.addEventListener('click', function (e) {
            var opener = e.target.closest('[data-toggle="modal"]');
            if (opener) {
                e.preventDefault();
                openModal(document.querySelector(opener.getAttribute('data-target')));
                return;
            }

            var closer = e.target.closest('[data-dismiss="modal"]');
            if (closer) {
                e.preventDefault();
                closeModal(closer.closest('.modal'));
                return;
            }

            // الضغط على الخلفية نفسها يغلق، لا الضغط داخل المحتوى.
            if (e.target.classList && e.target.classList.contains('modal')) {
                closeModal(e.target);
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                var open = document.querySelector('.modal.show');
                if (open) { closeModal(open); }
            }
        });
    });
</script>

@stack('script_2')
<audio id="myAudio">
    <source src="{{asset('public/assets/admin/sound/notification.mp3')}}" type="audio/mpeg">
</audio>

</body>
</html>
