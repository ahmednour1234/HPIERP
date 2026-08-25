@extends('layouts.admin.app')

@section('title', \App\CPU\translate('add_new_customer'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-add-circle-outlined"></i> {{ \App\CPU\translate('اضافة عميل جديد') }}
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12  mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{ route('admin.customer.store') }}" method="post" id="product_form" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" class="form-control" name="balance" value="0">

                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('اسم العميل بالعربي') }} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                                            placeholder="{{ \App\CPU\translate('customer_name_ar') }}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('اسم العميل بالانجليزي') }} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="name_en" class="form-control" value="{{ old('name_en') }}"
                                            placeholder="{{ \App\CPU\translate('customer_name_en') }}">
                                    </div>
                                </div>
                            </div>
                             <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('المنطقة') }}</label>
                                        <select name="region_id" class="form-control">
                                            @foreach ($regions as $region)
                                                <option value="{{ $region->id }}" {{ old('region_id') == $region->id ? 'selected' : '' }}>
                                                    {{ \App\CPU\translate($region->name) }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Specialization Options with Pharmacy Radio Buttons -->
                            <div class="row pl-2">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('التخصص') }} <span class="text-danger">*</span></label><br>
                                        
                                        <!-- Specialization Radio Buttons -->
                                        <label><input type="radio" name="specialist" value="1" id="pharmacy_check"> {{ \App\CPU\translate('صيدلية') }}</label><br>
                                        <label><input type="radio" name="specialist" value="2"> {{ \App\CPU\translate('مركز طبي') }}</label><br>
                                        <label><input type="radio" name="specialist" value="3"> {{ \App\CPU\translate('مستشفي') }}</label><br>
                                        <label><input type="radio" name="specialist" value="4"> {{ \App\CPU\translate('طبيب') }}</label>
                                    </div>
                                </div>
                            </div>

                            <!-- Conditional Pharmacy Name Input -->
                            <div class="row pl-2" id="pharmacy_name_field" style="display: none;">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('اسم الصيدلية') }}</label>
                                        <input type="text" name="pharmacy_name" class="form-control" placeholder="{{ \App\CPU\translate('pharmacy_name') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row pl-2">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('رقم الهاتف') }} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" id="mobile" name="mobile" class="form-control" value="{{ old('mobile') }}"
                                            placeholder="{{ \App\CPU\translate('mobile_no') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('الايميل') }}</label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                            placeholder="{{ \App\CPU\translate('Ex_:_ex@example.com') }}">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('المقاطعة') }}</label>
                                        <input type="text" name="state" class="form-control" value="{{ old('state') }}"
                                            placeholder="{{ \App\CPU\translate('state') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('المدينة') }}</label>
                                        <input type="text" name="city" class="form-control" value="{{ old('city') }}"
                                            placeholder="{{ \App\CPU\translate('city') }}">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('كود المحافظة') }}</label>
                                        <input type="text" name="zip_code" class="form-control" value="{{ old('zip_code') }}"
                                            placeholder="{{ \App\CPU\translate('zip_code') }}">
                                    </div>
                                </div>
                            </div>

                            <div class="row pl-2">
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('العنوان') }}</label>
                                        <input type="text" name="address" class="form-control" value="{{ old('address') }}"
                                            placeholder="{{ \App\CPU\translate('address') }}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('خطوط الطول') }}</label>
                                        <input type="text" name="longitude" class="form-control" value="{{ old('longitude') }}"
                                            placeholder="{{ \App\CPU\translate('longitude') }}">
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label">{{ \App\CPU\translate('خطوط العرض') }}</label>
                                        <input type="text" name="latitude" class="form-control" value="{{ old('latitude') }}"
                                            placeholder="{{ \App\CPU\translate('latitude') }}">
                                    </div>
                                </div>


    <!-- New Dropdown for Type -->
        <div class="col-12 col-sm-6">
            <div class="form-group">
                <label class="input-label">{{ \App\CPU\translate('تصنيف العميل') }} <span
                        class="input-label-secondary text-danger">*</span></label>
                <select name="type" class="form-control" required>
                    <option value="1">{{ \App\CPU\translate('A') }}</option>
                    <option value="2">{{ \App\CPU\translate('B') }}</option>
                    <option value="3">{{ \App\CPU\translate('C') }}</option>
                    <option value="4">{{ \App\CPU\translate('D') }}</option>
                </select>
            </div>
        </div>
                 <div class="row-12 col-sm-6">
                                        <label class="input-label">{{ \App\CPU\translate('التخصص') }} <span
                        class="input-label-secondary text-danger">*</span></label>
<select name="category_id" id="category_id" class="form-control">
    <option value="">{{ \App\CPU\translate('اختار التخصص') }}</option> <!-- Default option -->
    
    @foreach ($categories as $category)
        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
            {{ \App\CPU\translate($category->name) }}
        </option>
    @endforeach
</select>
            </div>
                            </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label>{{ \App\CPU\translate('الصورة') }}</label><small> ( {{ \App\CPU\translate('ratio_1:1') }} )( {{ \App\CPU\translate('optional') }} )</small>
                                        <div class="custom-file">
                                            <input type="file" name="image" id="customFileEg1" class="custom-file-input"
                                                accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*">
                                            <label class="custom-file-label" for="customFileEg1">{{ \App\CPU\translate('choose') }} {{ \App\CPU\translate('file') }}</label>
                                        </div>
                                        <div class="form-group my-4">
                                            <center>
                                                <img class="img-one-ci" id="viewer"
                                                    src="{{ asset('public/assets/admin/img/400x400/img2.jpg') }}" alt="image"/>
                                            </center>
                                        </div>
                                    </div>
                                </div>
               

    <!-- New Input for Minimum Credit Term -->
    <!--<div class="row pl-2">-->
    <!--    <div class="col-12 col-sm-6">-->
    <!--        <div class="form-group">-->
    <!--            <label class="input-label">{{ \App\CPU\translate('الحد الأدنى للأجل') }} <span-->
    <!--                    class="input-label-secondary text-danger">*</span></label>-->
    <!--            <input type="number" name="limit" class="form-control" value="{{ old('limit') }}"-->
    <!--                placeholder="{{ \App\CPU\translate('minimum_credit_term') }}" required>-->
    <!--        </div>-->
    <!--    </div>-->
    <!--</div>-->

                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('اضافة') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

  <script>
    // عند تغيير اختيار التخصص
    document.querySelectorAll('input[name="specialist"]').forEach((elem) => {
        elem.addEventListener("change", function(event) {
            const pharmacyField = document.getElementById("pharmacy_name_field");
            const categoryField = document.getElementById("category_id"); // الحقل الخاص بالتخصص
            
            if (event.target.value === '1') { // إذا كان الخيار طبيب
                pharmacyField.style.display = "none"; // إخفاء حقل اسم الصيدلية
                categoryField.style.display = "none"; // إظهار حقل التخصص
            } else {
                categoryField.style.display = event.target.value === '4' ?  "block":"none"; // إخفاء حقل التخصص
                pharmacyField.style.display = event.target.value === '1' ?  "none":"block" ; // إظهار حقل اسم الصيدلية إذا كان صيدلية
            }
        });
    });

    // عند تحميل الصفحة، تحقق من الاختيار الحالي
    window.addEventListener("load", function () {
        const selectedSpecialist = document.querySelector('input[name="specialist"]:checked');
        const event = new Event('change');
        if (selectedSpecialist) {
            selectedSpecialist.dispatchEvent(event);
        }
    });
</script>

    
@endsection
