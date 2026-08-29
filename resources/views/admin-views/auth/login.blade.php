<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{\App\CPU\translate('admin')}} | {{\App\CPU\translate('HPI')}}</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="">
    <!-- Font -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/google-fonts.css">
    <!-- CSS Implementing Plugins -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/vendor.min.css">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/vendor/icon-set/style.css">
    <!-- CSS Front Template -->
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/toastr.css">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('public/assets/admin/img/photo_2025-06-10_23-16-52.png') }}"/>

    <!-- Custom Styles -->
    <style>
    @font-face {
    font-family: 'Bahij';
    src: url("{{ asset('public/assets/admin/css/fonts/Bahij_TheSansArabic-Plain.ttf') }}") format('truetype');
    font-weight: normal;
    font-style: normal;
}


        body {
    font-family: 'Bahij', sans-serif;
    background:#e9f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            max-height: 100vh;
            margin: 0;
        }

        .card {
            background: white;
            padding: 40px;
            padding-top:40px ;
            margin-top: 130px;
            border-radius: 15px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.15);
            width: 400px;
            text-align: center;
        }

        .logo-container img {
            margin-right:10px;
            max-width: 300px;
        }

        .h-one-auth {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            border: 1px solid #ddd;
            padding: 12px;
            width: 80%;
            margin-bottom: 15px;
            transition: 0.3s;
        }

        .form-control:focus {
            border-color: #f8be1c;
            box-shadow: 0 0 8px rgba(248, 190, 28, 0.5);
        }

        .btn-primary {
            background-color: #0f3c4c;
            border: none;
            padding: 12px;
            color: white;
            width: 100%;
                font-family: 'Bahij', sans-serif;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background-color: #7cc0d8;
        }

        .input-group-text {
            background-color: #7cc0d8;
            border-radius: 8px;
            border-left: none;
            cursor: pointer;
        }

        .forgot-password {
            display: block;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #777;
            text-decoration: none;
        }

        .forgot-password:hover {
            color: #f8be1c;
        }
          .ss {
            color: #7cc0d8;
        }
    </style>
</head>
<body>

<div class="card">
    {{-- optional(): the row does not exist until a logo is uploaded, and
         reading ->value on null took the whole login page down with a 500. --}}
    @php($shop_logo=optional(\App\Models\BusinessSetting::where(['key'=>'shop_logo'])->first())->value)

    <div class="logo-container">
        <img src="{{asset('storage/shop')}}/{{ $shop_logo }}"  alt="IQ Point Logo">
    </div>
    <h2 class="h-one-auth pb-2">{{\App\CPU\translate('نظام')}} <span class="ss">ERP</span> متكامل</h2>

    <form action="{{route('admin.auth.login')}}" method="post">
        @csrf
        <input type="email" class="form-control" name="email"  placeholder="{{\App\CPU\translate('email@address.com')}}" required>

        <div class="input-group">
            <input type="password" class="form-control" name="password" placeholder="{{\App\CPU\translate('Password')}}" required>
         
        </div>

        <button type="submit" class="btn btn-primary">{{\App\CPU\translate('تسجيل دخول')}}</button>
               
    </form>
</div>
<script src="{{asset('public/assets/admin')}}/js/vendor.min.js"></script>

<!-- JS Front -->
<script src="{{asset('public/assets/admin')}}/js/theme.min.js"></script>
<script src="{{asset('public/assets/admin')}}/js/toastr.js"></script>
{!! Toastr::message() !!}

@if ($errors->any())
    <script>
        "use strict";
        @foreach($errors->all() as $error)
        toastr.error('{{$error}}', Error, {
            CloseButton: true,
            ProgressBar: true
        });
        @endforeach
    </script>
@endif

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
</script>
<!-- JS Plugins Init. -->
<script src="{{asset('public/assets/admin')}}/js/auth-page.js"></script>

<!-- IE Support -->
<script>
    if (/MSIE \d|Trident.*rv:/.test(navigator.userAgent)) document.write('<script src="{{asset('public/assets/admin')}}/vendor/babel-polyfill/polyfill.min.js"><\/script>');
</script>
</body>
<!-- JS Implementing Plugins -->

</html>
