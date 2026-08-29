@extends('layouts.admin.app')

@section('title', 'خريطة المناديب')

@push('css_or_js')
<link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">خريطة المناديب</h1>
        </div>

        {{-- تظهر هذه الرسالة فقط إذا فشل تحميل الخريطة، فيعرف المستخدم السبب
             بدل مربع رمادي فارغ. --}}
        <div id="map-error" class="alert alert-warning" style="display:none;"></div>

        @if($admins->isEmpty())
            <div class="alert alert-info">
                لا يوجد مناديب لهم إحداثيات مسجَّلة. يُسجَّل الموقع من التطبيق أو من صفحة تعديل المندوب.
            </div>
        @endif

        <div id="map" style="height: 600px;"></div>
    </div>
@endsection

@push('script_2')
    <script>
        "use strict";

        var HPI_ADMINS = @json($admins);

        function initMap() {
            var el = document.getElementById('map');
            if (!el) {
                return;
            }

            var map = new google.maps.Map(el, {
                center: { lat: 26.8206, lng: 30.8025 }, // مركز مصر
                zoom: 6
            });

            var bounds = new google.maps.LatLngBounds();
            var seen = {};
            var plotted = 0;
            var OFFSET = 0.0002;

            HPI_ADMINS.forEach(function (admin) {
                var lat = parseFloat(admin.latitude);
                var lng = parseFloat(admin.longitude);

                // إحداثيات غير صالحة أو (0,0) تُتجاهل.
                if (!isFinite(lat) || !isFinite(lng) || (lat === 0 && lng === 0)) {
                    return;
                }

                var key = lat.toFixed(6) + ',' + lng.toFixed(6);
                if (seen[key]) {
                    // إزاحة ثابتة حسب عدد التكرارات بدل قيمة عشوائية، حتى لا
                    // تقفز العلامة إلى مكان مختلف مع كل إعادة تحميل.
                    lat += OFFSET * seen[key];
                    lng += OFFSET * seen[key];
                    seen[key]++;
                } else {
                    seen[key] = 1;
                }

                var name = [admin.f_name, admin.l_name].filter(Boolean).join(' ');
                var position = { lat: lat, lng: lng };

                var marker = new google.maps.Marker({
                    position: position,
                    map: map,
                    title: name
                });

                var info = new google.maps.InfoWindow({
                    content: '<div style="min-width:160px">'
                        + '<h6 style="margin:0 0 4px">' + name + '</h6>'
                        + (admin.mandob_code ? '<div>كود المندوب: ' + admin.mandob_code + '</div>' : '')
                        + '<div>' + lat.toFixed(6) + ', ' + lng.toFixed(6) + '</div>'
                        + '</div>'
                });

                marker.addListener('click', function () {
                    info.open(map, marker);
                });

                bounds.extend(position);
                plotted++;
            });

            // ضبط الإطار على العلامات الفعلية بدل تقريب ثابت.
            if (plotted === 1) {
                map.setCenter(bounds.getCenter());
                map.setZoom(12);
            } else if (plotted > 1) {
                map.fitBounds(bounds);
            }
        }

        // يستدعيها سكربت Google عبر callback=initMap، فلا حاجة لانتظار
        // window.load — الاعتماد عليه كان يفوّت الحدث لأنه غالبًا وقع بالفعل.
        window.initMap = initMap;

        function hpiMapFailed() {
            var box = document.getElementById('map-error');
            if (box) {
                box.style.display = 'block';
                box.textContent = 'تعذّر تحميل خرائط Google. تأكد من مفتاح الخرائط (GOOGLE_MAPS_API_KEY) ومن الاتصال بالإنترنت.';
            }
        }
    </script>

    {{-- المفتاح إلزامي: بدونه ترفض Google تحميل الـ API وتبقى الخريطة فارغة،
         وهو سبب عدم عمل هذه الشاشة. --}}
    <script async defer
            onerror="hpiMapFailed()"
            src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key') }}&callback=initMap"></script>
@endpush
