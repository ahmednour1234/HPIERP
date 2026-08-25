<div class="card shadow-sm p-4">
    <h5 class="mb-4 text-primary">معلومات المصنع</h5>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="name" class="form-label">اسم المصنع <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $factory->name ?? '') }}" required>
        </div>
        <div class="col-md-6">
            <label for="phone" class="form-label">رقم الهاتف</label>
            <input type="text" name="phone" class="form-control" value="{{ old('phone', $factory->phone ?? '') }}">
        </div>
    </div>

    <div class="row mb-3">
        <div class="col-md-6">
            <label for="email" class="form-label">البريد الإلكتروني</label>
            <input type="email" name="email" class="form-control" value="{{ old('email', $factory->email ?? '') }}">
        </div>
        <div class="col-md-6">
            <label for="address" class="form-label">العنوان</label>
            <input type="text" name="address" class="form-control" value="{{ old('address', $factory->address ?? '') }}">
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <label for="lang" class="form-label">خط الطول (Longitude)</label>
            <input type="text" name="lang" id="lang" class="form-control" value="{{ old('lang', $factory->lang ?? '') }}">
        </div>
        <div class="col-md-6">
            <label for="late" class="form-label">خط العرض (Latitude)</label>
            <input type="text" name="late" id="late" class="form-control" value="{{ old('late', $factory->late ?? '') }}">
        </div>
    </div>

    <div class="mb-3">
        <label class="form-label d-block">الموقع على الخريطة</label>
        <div id="map" style="height: 400px; border: 1px solid #ccc; border-radius: 10px;"></div>
    </div>
</div>

{{-- ✅ Google Maps API --}}
<script async defer
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAQgTQ30_TriFBdJPKKOK4zZQ8rfHCUk6c&callback=initMap">
</script>

{{-- ✅ سكربت تفعيل الخريطة --}}
<script>
    let map, marker;

    function initMap() {
        let lat = parseFloat(document.getElementById("late").value) || 24.7136;
        let lng = parseFloat(document.getElementById("lang").value) || 46.6753;

        const center = { lat: lat, lng: lng };

        map = new google.maps.Map(document.getElementById("map"), {
            center: center,
            zoom: 12,
        });

        marker = new google.maps.Marker({
            position: center,
            map: map,
            draggable: true
        });

        // تحديث الإحداثيات عند سحب العلامة
        marker.addListener("dragend", function (event) {
            document.getElementById("late").value = event.latLng.lat();
            document.getElementById("lang").value = event.latLng.lng();
        });

        // تحديث الخريطة عند تغيير الإحداثيات يدويًا
        document.getElementById("late").addEventListener("input", updateMarkerPosition);
        document.getElementById("lang").addEventListener("input", updateMarkerPosition);
    }

    function updateMarkerPosition() {
        let lat = parseFloat(document.getElementById("late").value);
        let lng = parseFloat(document.getElementById("lang").value);

        if (!isNaN(lat) && !isNaN(lng)) {
            const position = { lat: lat, lng: lng };
            marker.setPosition(position);
            map.setCenter(position);
        }
    }
</script>
