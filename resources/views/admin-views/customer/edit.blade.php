@extends('layouts.admin.app')

@section('title', \App\CPU\translate('update_customer'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}"/>
@endpush

@section('content')
<div class="content container-fluid">
    <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title text-capitalize">
                <i class="tio-edit"></i> {{ \App\CPU\translate('تحديث بيانات العميل') }}
            </h1>
        </div>
    </div>
    <!-- End Page Header -->
    <div class="row gx-2 gx-lg-3">
        <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('admin.customer.update', [$customer->id]) }}" method="post" id="product_form" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="balance" value="{{ $customer->balance }}">

                        <!-- Customer Name Fields -->
                        <div class="row pl-2">
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('اسم العميل بالعربي') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="{{ $customer->name }}" required>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('اسم العميل بالإنجليزي') }} <span class="text-danger">*</span></label>
                                    <input type="text" name="name_en" class="form-control" value="{{ $customer->name_en }}">
                                </div>
                            </div>
                        </div>

                       <!-- Region Selection -->
<div class="row pl-2">
    <div class="col-12 col-sm-6">
        <div class="form-group">
            <label class="input-label">{{ \App\CPU\translate('المنطقة') }}</label>
            <select name="region_id" class="form-control">
                <option value="">{{ \App\CPU\translate('اختار المنطقة') }}</option>
                @foreach ($regions as $region)
                    <option value="{{ $region->id }}" 
                        {{ old('region_id', $customer->region_id ?? '') == $region->id ? 'selected' : '' }}>
                        {{ \App\CPU\translate($region->name) }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
</div>


<!-- Specialist Selection with Pharmacy Conditional Input -->
<div class="row pl-2">
    <div class="col-12">
        <div class="form-group">
            <label class="input-label">{{ \App\CPU\translate('التخصص') }} <span class="text-danger">*</span></label><br>
            
            <label>
                <input type="radio" name="specialist" value="1" id="pharmacy_check" 
                    {{ old('specialist', $customer->specialist) == 1 ? 'checked' : '' }}> 
                {{ \App\CPU\translate('صيدلية') }}
            </label><br>
            
            <label>
                <input type="radio" name="specialist" value="2" 
                    {{ old('specialist', $customer->specialist) == 2 ? 'checked' : '' }}> 
                {{ \App\CPU\translate('مركز طبي') }}
            </label><br>
            
            <label>
                <input type="radio" name="specialist" value="3" 
                    {{ old('specialist', $customer->specialist) == 3 ? 'checked' : '' }}> 
                {{ \App\CPU\translate('مستشفي') }}
            </label><br>
            
            <label>
                <input type="radio" name="specialist" value="4" 
                    {{ old('specialist', $customer->specialist) == 4 ? 'checked' : '' }}> 
                {{ \App\CPU\translate('طبيب') }}
            </label>
        </div>
    </div>
</div>

<!-- Pharmacy Name Conditional Field -->
<div class="row pl-2" id="pharmacy_name_field" style="display: {{ in_array(old('specialist', $customer->specialist), [2, 3, 4]) ? 'block' : 'none' }};">
    <div class="col-12 col-sm-6">
        <div class="form-group">
            <label class="input-label">{{ \App\CPU\translate('اسم الصيدلية') }}</label>
            <input type="text" name="pharmacy_name" class="form-control" placeholder="{{ \App\CPU\translate('pharmacy_name') }}"
                value="{{ old('pharmacy_name', $customer->pharmacy_name) }}">
        </div>
    </div>
</div>
            <!-- Contact Information -->
                        <div class="row pl-2">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="input-label">{{ __('رقم الهاتف') }} <span class="text-danger">*</span></label>
                                    <input type="number" name="mobile" class="form-control" value="{{ $customer->mobile }}" required>
                                </div>
                            </div>
                        </div>

                        <!-- Email and Address Fields -->
                        <div class="row pl-2">
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('البريد الإلكتروني') }}</label>
                                    <input type="email" name="email" class="form-control" value="{{ $customer->email }}">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('المقاطعة') }}</label>
                                    <input type="text" name="state" class="form-control" value="{{ $customer->state }}">
                                </div>
                            </div>
                        </div>

                        <!-- Additional Location Information -->
                        <div class="row pl-2">
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('المدينة') }}</label>
                                    <input type="text" name="city" class="form-control" value="{{ $customer->city }}">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('كود المحافظة') }}</label>
                                    <input type="text" name="zip_code" class="form-control" value="{{ $customer->zip_code }}">
                                </div>
                            </div>
                        </div>

                        <!-- Address and Coordinates Fields -->
                        <div class="row pl-2">
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('العنوان') }}</label>
                                    <input type="text" name="address" class="form-control" value="{{ $customer->address }}">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('خطوط الطول') }}</label>
                                    <input type="text" name="longitude" class="form-control" value="{{ $customer->longitude }}">
                                </div>
                            </div>
                            <div class="col-12 col-sm-6">
                                <div class="form-group">
                                    <label class="input-label">{{ __('خطوط العرض') }}</label>
                                    <input type="text" name="latitude" class="form-control" value="{{ $customer->latitude }}">
                                </div>
                            </div>
                        </div>

                        <!-- Customer Image Upload -->
                        <div class="col-12 col-sm-6">
                            <div class="form-group">
                                <label>{{ __('الصورة') }}</label><small> ( {{ __('النسبة_1:1') }} )( {{ __('اختياري') }} )</small>
                                <div class="custom-file">
                                    <input type="file" name="image" id="customFileEg1" class="custom-file-input" accept="image/*">
                                    <label class="custom-file-label" for="customFileEg1">{{ __('اختر_ملف') }}</label>
                                </div>
                                <div class="form-group my-4">
                                    <center>
                                        <img class="img-one-cusu" id="viewer" src="{{ asset('storage/customer/' . $customer['image']) }}" onerror="this.src='{{ asset('public/assets/admin/img/400x400/img2.jpg') }}'" alt="{{ __('الصورة') }}"/>
                                    </center>
                                </div>
                            </div>
                        </div>

                   <!-- Category Selection -->
<div class="col-12 col-sm-6">
    <select name="category_id" id="category_id" class="form-control">
        <option value="">{{ \App\CPU\translate('اختار التخصص') }}</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" {{ old('category_id', $customer->category_id ?? '') == $category->id ? 'selected' : '' }}>
                {{ \App\CPU\translate($category->name) }}
            </option>
        @endforeach
    </select>
</div>

             <!-- Customer Type Selection -->
<div class="row pl-2">
    <div class="col-12 col-sm-6">
        <div class="form-group">
            <label class="input-label">{{ \App\CPU\translate('تصنيف العميل') }} <span class="text-danger">*</span></label>
            <select name="type" class="form-control" required>
                <option value="1" {{ old('type', $customer->type ?? '') == 1 ? 'selected' : '' }}>{{ \App\CPU\translate('A') }}</option>
                <option value="2" {{ old('type', $customer->type ?? '') == 2 ? 'selected' : '' }}>{{ \App\CPU\translate('B') }}</option>
                <option value="3" {{ old('type', $customer->type ?? '') == 3 ? 'selected' : '' }}>{{ \App\CPU\translate('C') }}</option>
                <option value="4" {{ old('type', $customer->type ?? '') == 4 ? 'selected' : '' }}>{{ \App\CPU\translate('D') }}</option>
            </select>
        </div>
    </div>
</div>

                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('update') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@push('script')
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

@endpush
@endsection
