@extends('layouts.admin.app')

@section('title', 'Admin Locations')

@push('css_or_js')
<link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
    <script src="https://maps.googleapis.com/maps/api/js?libraries=places"></script>
@endpush

@section('content')
    <div class="content container-fluid">
        <div class="page-header">
            <h1 class="page-header-title">Admin Locations</h1>
        </div>

        <div id="map" style="height: 500px;"></div>
    </div>
@endsection

@push('script_2')
    <script>
 let map;
const OFFSET = 0.0001; // Offset value to adjust positions slightly

function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        center: { lat: 30.0444, lng: 31.2357 }, // Example: Cairo, Egypt
        zoom: 10 // Adjust the initial zoom level as needed
    });

    // Admins data from backend
    const admins = @json($admins);
    const positions = {}; // Store positions to handle overlapping markers

    admins.forEach(admin => {
        if (admin.latitude && admin.longitude) {
            let lat = parseFloat(admin.latitude);
            let lng = parseFloat(admin.longitude);
            const positionKey = `${lat},${lng}`;

            // If a marker already exists in this position, adjust it slightly
            if (positions[positionKey]) {
                lat += Math.random() * OFFSET; // Adjust latitude
                lng += Math.random() * OFFSET; // Adjust longitude
            }

            positions[positionKey] = true; // Mark this position as occupied

            const marker = new google.maps.Marker({
                position: { lat, lng },
                map: map,
                title: admin.name // Assuming you have a 'name' attribute for the admin
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `<h6>${admin.f_name}</h6><p>Lat: ${admin.latitude}, Lng: ${admin.longitude}</p>`
            });

            marker.addListener('click', function () {
                infoWindow.open(map, marker);
            });
        }
    });
}

// Check if the Google Maps API is loaded
if (typeof google !== 'undefined') {
    google.maps.event.addDomListener(window, 'load', initMap);
} else {
    console.error('Google Maps API not loaded.');
}

    </script>
@endpush
