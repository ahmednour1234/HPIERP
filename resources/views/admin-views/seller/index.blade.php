@extends('layouts.admin.app')

@section('title', 'اضافة مندوب جديد')

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
<style>
    .form-card {
        border-radius: 1rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
    }
    .form-card .card-header {
        background: #bee0ec;
        color: #333;
        padding: 1rem 1.5rem;
        font-size: 1.25rem;
        font-weight: 600;
        border-bottom: none;
    }
    .form-card .card-body {
        padding: 1.5rem;
    }
    .form-card .form-group label {
        font-weight: 600;
    }
    .btn-submit {
        background: #2596be;
        color: #fff;
        border-radius: 0.75rem;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
    }
    .select2-container .select2-selection--multiple {
        border-radius: 0.5rem;
        min-height: 56px;
    }
</style>
@endpush

@section('content')
<div class="content container-fluid" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card form-card">
                <div class="card-header">
                 اضافة مندوب جديد   <i class="tio-add-circle-outlined me-2"></i>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.seller.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>الاسم الأول <span class="text-danger">*</span></label>
                                    <input type="text" name="f_name" class="form-control" value="{{ old('f_name') }}" placeholder="أدخل الاسم الأول" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>الاسم الأخير <span class="text-danger">*</span></label>
                                    <input type="text" name="l_name" class="form-control" value="{{ old('l_name') }}" placeholder="أدخل الاسم الأخير" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>البريد الإلكتروني <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="مثال: ex@example.com" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>كلمة المرور <span class="text-danger">*</span></label>
                                    <input type="password" name="password" class="form-control" placeholder="أدخل كلمة المرور" required>
                                </div>
                            </div>

                            <div class="col-md-12" id="regions-box">
                                <div class="form-group">
                                    <label>المناطق <span class="text-danger">*</span></label>
                                    <select name="reg[]" class="form-control select2-multiple" multiple="multiple" >
                                        @foreach($regions as $reg)
                                            <option value="{{ $reg->id }}" @if(in_array($reg->id, old('reg', []))) selected @endif>{{ $reg->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group"  id="cats-box">
                                    <label>التصنيفات <span class="text-danger">*</span></label>
                                    <select name="cats[]" class="form-control select2-multiple" multiple="multiple" >
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->id }}" @if(in_array($cat->id, old('cats', []))) selected @endif>{{ $cat->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12" id="customers-box">
                                <div class="form-group">
                                    <label>العملاء <span class="text-danger">*</span></label>
                                    <select name="customers[]" class="form-control select2-multiple" multiple="multiple" >
                                        @foreach($customers as $cust)
                                            <option value="{{ $cust->id }}" @if(in_array($cust->id, old('customers', []))) selected @endif>{{ $cust->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>كود المندوب <span class="text-danger">*</span></label>
                                    <input type="text" name="mandob_code" class="form-control" value="{{ old('mandob_code') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>كود العربة <span class="text-danger">*</span></label>
                                    <select name="vehicle_code" class="form-control select2-single" required>
                                        <option value="" disabled selected>اختر كود العربة</option>
                                        @foreach($vehicles as $veh)
                                            <option value="{{ $veh->store_id }}" @if(old('vehicle_code') == $veh->store_id) selected @endif>{{ $veh->store_name1 }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>المخزن <span class="text-danger">*</span></label>
                                    <select name="store_id" class="form-control select2-single" required>
                                        @foreach($storages as $st)
                                            <option value="{{ $st->id }}" @if(old('store_id') == $st->id) selected @endif>{{ $st->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                           
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>الراتب <span class="text-danger">*</span></label>
                                    <input type="number" name="salary" class="form-control" value="{{ old('salary') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>% نسبة المبيعات <span class="text-danger">*</span></label>
                                    <input type="number" name="precent_of_sales" class="form-control" value="{{ old('precent_of_sales') }}" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>عدد الإجازات <span class="text-danger">*</span></label>
                                    <input type="number" name="holidays" class="form-control" value="{{ old('holidays') }}" required>
                                </div>
                            </div>
                                 <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('عدد الزيارات المطلوبة') }}</label>
                                <input type="number" name="visitors" class="form-control" value="{{old('visitors')  }}">
                            </div>
                            
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>مديونية المندوب <span class="text-danger">*</span></label>
                                    <input type="number" name="balance" class="form-control" value="{{ old('balance') }}" required>
                                </div>
                            </div>
  <div class="col-md-4">
                                <div class="form-group">
                                    <label>النوع <span class="text-danger">*</span></label>
                                    <select name="type" id="type-select" class="form-control select2-single" required>
                                        <option value="" disabled selected>اختر النوع</option>
                                        <option value="mandob" @if(old('type')=='mandob') selected @endif>مندوب</option>
                                        <option value="manager" @if(old('type')=='manager') selected @endif>مدير</option>
                                        <option value="bigmanager" @if(old('type')=='bigmanager') selected @endif>مدير المدير</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-4" id="admin-select-box" style="display: none;">
                                <div class="form-group">
                                    <label>اختر المستخدمين</label>
                                    <select name="admins[]" class="form-control select2-multiple" multiple></select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">اسم الشيفت <span class="text-danger">*</span></label>
                                    <select name="shift_id[]" class="form-control select2-multiple" multiple required>
                                        <option value="" hidden>-- اختر شيفت --</option>
                                        @foreach($shifts as $item)
                                            <option value="{{ $item->id }}"
                                                {{ in_array($item->id, old('shift_id', [])) ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- صلاحيات كمندوب -->
                            <div class="col-12">
                                <div class="form-group">
                                    <label>الصلاحيات كمندوب</label>
                                    <select name="permissions[]" class="form-control select2-multiple" multiple="multiple">
                                        @foreach(['dashboard' => 'لوحة التحكم', 'stock' => 'حجز', 'store' => 'طلب', 'admin' => 'تقسيط', 'regions' => 'الموقع'] as $key => $label)
                                            <option value="{{ $key }}" @if(in_array($key, old('permissions', []))) selected @endif>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <!-- صلاحيات كمدير -->
                            @php
                                $permissionsmanager = [
                                    'supplier' => 'رؤية الموردين', 'dashboard' => 'رؤية الاحصائيات', 'pos' => 'رؤية الفواتير',
                                    'store' => 'رؤية السيارات', 'cat' => 'رؤية الاقسام', 'unit' => 'رؤية وحدات القياس',
                                    'product' => 'رؤية المنتجات', 'stock_limit' => 'رؤية كشف النواقص', 'customer' => 'رؤية العملاء',
                                    'seller' => 'رؤية المناديب', 'admin' => 'رؤية الادمن', 'setting' => 'رؤية اعدادات البرنامج',
                                    'storage' => 'رؤية الخزن', 'requests' => 'رؤية الطلبات ', 'notification' => 'رؤية الاشعارات',
                                    'tracking' => 'رؤية خريطة المناديب', 'regions' => 'رؤية المناطق', 'reports' => 'رؤية التقارير',
                                    'stock' => 'رؤية الرحلات', 'visit' => 'رؤية قسم الزيارات', 'rating' => 'رؤية قسم تقييم المناديب',
                                           'install'=>'التحصيلات',
 'sectionsalary' => 'رؤية قسم المرتبات', 'accounts' => 'رؤية قسم الحسابات', 'sales' => 'رؤية قسم انشاء مبيعات'
               ,'production'=>'إنتاج وتصنيع','hr'=>'إدارة الموارد البشرية' ,'attendance'=>'الحضور والأنصراف'];
                            @endphp
                            <div class="col-12" id="manager-permissions-box" style="display: none;">
                                <label class="input-label">الصلاحيات كمدير <span class="text-danger">*</span></label>
                                <div class="container">
                                    @foreach ($permissionsmanager as $key => $label)
                                        @if ($loop->index % 3 === 0)<div class="row">@endif
                                        <div class="col-12 col-sm-4">
                                            <div class="form-group">
                                                <label class="input-label">{{ $label }}</label>
                                                <input type="checkbox" name="{{ $key }}" value="1" @if(old($key, $seller->$key ?? 0) == 1) checked @endif>
                                            </div>
                                        </div>
                                        @if ($loop->iteration % 3 === 0 || $loop->last)</div>@endif
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-submit">حفظ البيانات</button>
                            </div>
                        </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script_2')
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.full.min.js"></script>
<script>
    $(document).ready(function () {
        // تهيئة select2 لجميع الحقول المتعددة والمفردة
        $('.select2-multiple').select2({
            placeholder: 'اختر ...',
            width: '100%'
        });

        $('.select2-single').select2({
            placeholder: 'اختر ...',
            minimumResultsForSearch: Infinity,
            width: '100%'
        });

        // دالة لتبديل عرض الحقول الخاصة بالمديرين والمدير العام
        function toggleManagerFields(type) {
            const isManager = type === 'manager' || type === 'bigmanager';

            // إظهار أو إخفاء الحقول
            $('#admin-select-box').toggle(isManager);
            $('#manager-permissions-box').toggle(isManager);
            $('#customers-box').toggle(!isManager);
            $('#cats-box').toggle(!isManager);
            $('#regions-box').toggle(!isManager);

            // جلب المستخدمين إذا كان النوع مدير أو مدير المدير
            if (isManager) {
                $.ajax({
                    url: '{{ route("admin.seller.getAvailableAdmins") }}',
                    type: 'GET',
                    data: { type },
                    success: function (data) {
                        const select = $('select[name="admins[]"]');
                        select.empty();
                        if (data.length === 0) {
                            select.append('<option disabled>لا يوجد مستخدمين متاحين</option>');
                        } else {
                            data.forEach(function (admin) {
                                const fullName = admin.f_name + ' ' + admin.l_name;
                                select.append(`<option value="${admin.id}">${fullName}</option>`);
                            });
                        }
                    },
                    error: function () {
                        console.error("فشل تحميل المستخدمين.");
                    }
                });
            } else {
                $('select[name="admins[]"]').empty();
            }
        }

        // التفعيل عند تحميل الصفحة بقيمة سابقة
        const initialType = $('#type-select').val();
        toggleManagerFields(initialType);

        // التفعيل عند تغيير القيمة
        $('#type-select').on('change', function () {
            const selectedType = $(this).val();
            toggleManagerFields(selectedType);
        });
    });
</script>
@endpush
