@extends('layouts.admin.app')

@section('title', \App\CPU\translate('update_seller'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<style>
    .seller-form { --sf-navy:#11245a; --sf-ink:#1f2d3d; --sf-muted:#6b7a90;
                   --sf-line:#e6edf5; --sf-soft:#f6f9fc; }

    .seller-form .form-card {
        border:1px solid var(--sf-line); border-radius:14px; background:#fff;
        box-shadow:0 10px 26px rgba(15,23,42,.06); overflow:hidden; margin-bottom:1.25rem;
    }

    .seller-form .form-card > .card-header {
        display:flex; align-items:center; justify-content:space-between;
        gap:1rem; padding:1.1rem 1.4rem; border-bottom:0;
        background:linear-gradient(135deg,#11245a,#1b3b7a); color:#fff;
        font-size:1.1rem; font-weight:800;
    }

    /* أقسام داخل البطاقة: كانت الحقول كلّها كتلةً واحدة، فالبحث عن
       حقلٍ بعينه يعني مسح الصفحة كلها بالعين. */
    .sf-section { padding:1.25rem 1.4rem; border-top:1px solid var(--sf-line); }
    .sf-section:first-of-type { border-top:0; }

    .sf-section-title {
        display:flex; align-items:center; gap:.5rem; margin:0 0 1rem;
        font-size:.9rem; font-weight:800; color:var(--sf-navy);
    }
    .sf-section-title i { color:#1b3b7a; }
    .sf-section-title span {
        flex:1; height:1px; background:var(--sf-line);
    }

    .seller-form .form-label,
    .seller-form .input-label {
        font-size:.82rem; font-weight:700; color:var(--sf-navy); margin-bottom:.4rem;
    }

    .seller-form .form-control,
    .seller-form select.form-control {
        border:1px solid var(--sf-line); border-radius:9px;
        min-height:42px; font-size:.88rem; color:var(--sf-ink);
    }
    .seller-form .form-control:focus {
        border-color:#8fb4e8; box-shadow:0 0 0 .18rem rgba(27,59,122,.1);
    }

    /* ---------- Select2 ---------- */

    .seller-form .select2-container { width:100% !important; }

    .select2-container--default .select2-selection--multiple,
    .select2-container--default .select2-selection--single {
        border:1px solid #e6edf5 !important; border-radius:9px !important;
        min-height:42px; padding:.2rem .45rem;
    }

    .select2-container--default.select2-container--focus .select2-selection--multiple,
    .select2-container--default.select2-container--open .select2-selection--single {
        border-color:#8fb4e8 !important; box-shadow:0 0 0 .18rem rgba(27,59,122,.1);
    }

    /* الوسم المختار: كان رماديًّا بحجم النصّ نفسه فيصعب تمييز
       المختار عن الكتابة. */
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background:#eef3fb; border:1px solid #d7e3f5; border-radius:7px;
        color:#11245a; font-size:.78rem; font-weight:700;
        padding:.12rem .5rem; margin:.2rem .2rem 0 0;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
        color:#7f93ad; margin-left:.3rem; margin-right:0;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
        color:#c0392b;
    }

    /* القائمة المنسدلة: كانت تخرج بعرض الصفحة وتُقصّ عناصرها عند
       الحافة، فلا يُقرأ المعروض. */
    .select2-container--open .select2-dropdown {
        border:1px solid #d7e3f5; border-radius:10px;
        box-shadow:0 14px 34px rgba(15,23,42,.14); overflow:hidden;
    }
    .select2-container .select2-results__option {
        font-size:.85rem; padding:.5rem .8rem;
        white-space:normal; word-break:break-word;
    }
    .select2-container--default .select2-results__option--highlighted[aria-selected] {
        background:#1b3b7a;
    }
    .select2-search--dropdown .select2-search__field {
        border:1px solid #e6edf5; border-radius:7px; padding:.4rem .6rem;
    }

    .sf-foot {
        display:flex; justify-content:flex-end; gap:.6rem;
        padding:1.1rem 1.4rem; background:var(--sf-soft);
        border-top:1px solid var(--sf-line);
    }
    .sf-foot .btn { border-radius:9px; font-weight:700; padding:.5rem 1.6rem; }

    @media (max-width: 767.98px) {
        .sf-section { padding:1rem; }
        .sf-foot { flex-direction:column-reverse; }
        .sf-foot .btn { width:100%; }
    }
</style>
@endpush

@section('content')
@php
    use Illuminate\Support\Facades\DB;

    // 1) أIDs المناطق المختارة
    $selectedRegionIds = DB::table('seller_regions')
        ->where('seller_id', $seller->id)
        ->pluck('region_id')
        ->toArray();

    // 2) أIDs المستخدمين (المندوبين) المرتبطين بالمدير الحالي عبر جدول admin_sellers
    //    SELECT seller_id FROM admin_sellers WHERE admin_id = $seller->id
    $selectedAdminSellerIds = DB::table('admin_sellers')
        ->where('admin_id', $seller->id)
        ->pluck('seller_id')
        ->toArray();

    // 3) جلب كل المندوبين لعرضهم داخل صندوق "اختر المستخدمين"
    //    (استثني المدير الحالي إن كان مسجل في sellers)
    // ملاحظة: غيّر اسم الجدول/الموديل حسب مشروعك. هنا بنعتمد جدول sellers مباشرة.
    $adminCandidates = DB::table('admins')
        ->select('id', 'f_name', 'l_name')
        ->when(isset($seller->id), fn($q) => $q->where('id', '!=', $seller->id))
        ->orderBy('id', 'desc')
        ->get();

    // 4) إذن المدير: مصفوفة الصلاحيات
    $permissionsmanager = [
        'supplier' => 'رؤية الموردين', 'dashboard' => 'رؤية الاحصائيات', 'pos' => 'رؤية الفواتير',
        'store' => 'رؤية السيارات', 'cat' => 'رؤية الاقسام', 'unit' => 'رؤية وحدات القياس',
        'product' => 'رؤية المنتجات', 'stock_limit' => 'رؤية كشف النواقص', 'customer' => 'رؤية العملاء',
        'seller' => 'رؤية المناديب', 'admin' => 'رؤية الادمن', 'setting' => 'رؤية اعدادات البرنامج',
        'storage' => 'رؤية الخزن', 'requests' => 'رؤية الطلبات ', 'notification' => 'رؤية الاشعارات',
        'tracking' => 'رؤية خريطة المناديب', 'regions' => 'رؤية المناطق', 'reports' => 'رؤية التقارير',        'sales'=>'إدارة المبيعات',

        'stock' => 'رؤية الرحلات', 'visit' => 'رؤية قسم الزيارات', 'rating' => 'رؤية قسم تقييم المناديب',
        'sectionsalary' => 'رؤية قسم المرتبات', 'accounts' => 'رؤية قسم الحسابات', 'sales' => 'رؤية قسم انشاء مبيعات'
               ,        'install'=>'التحصيلات',
'production'=>'إنتاج وتصنيع','hr'=>'إدارة الموارد البشرية' ,'attendance'=>'الحضور والأنصراف'];


    // 5) الشيفت: نخزنها كمصفوفة IDs (الحقل shift_id مخزن JSON)
    $oldShifts = old('shift_id', isset($seller) ? ($seller->shift_id ?? '[]') : '[]');
    $oldShiftsArray = is_array($oldShifts) ? $oldShifts : (json_decode($oldShifts, true) ?: []);
@endphp

<div class="content container-fluid seller-form" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card form-card">
                <div class="card-header">
                    {{ \App\CPU\translate('تعديل بيانات المندوب') }} <i class="tio-edit me-2"></i>
                </div>

                <div class="card-body">
                    <form action="{{ route('admin.seller.update', [$seller->id]) }}" method="post" id="seller_form" enctype="multipart/form-data">
                        @csrf

                        <div class="sf-section">
                        <h6 class="sf-section-title"><i class="tio-user"></i> البيانات الأساسية <span></span></h6>
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">{{ \App\CPU\translate('الاسم الاول') }} <span class="text-danger">*</span></label>
                                <input type="text" name="f_name" class="form-control" value="{{ old('f_name', $seller->f_name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ \App\CPU\translate('الاسم الاخير') }} <span class="text-danger">*</span></label>
                                <input type="text" name="l_name" class="form-control" value="{{ old('l_name', $seller->l_name) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ \App\CPU\translate('البريد الالكتروني') }} <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control" value="{{ old('email', $seller->email) }}" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ \App\CPU\translate('كلمة المرور') }}</label>
                                <input type="password" name="password" class="form-control" placeholder="{{ \App\CPU\translate('اتركه فارغًا إن لم ترد تغييره') }}">
                            </div>

                        </div>
                        </div>

                        <div class="sf-section">
                            <h6 class="sf-section-title"><i class="tio-poi"></i> نطاق العمل <span></span></h6>
                            <div class="row g-3">
                            {{-- Regions --}}
                            <div class="col-md-12 mb-3" id="regions-box">
                                <label class="form-label">{{ \App\CPU\translate('المناطق') }} <span class="text-danger">*</span></label>
                                <select name="reg[]" class="form-control select2-multiple" multiple required>
                                    @foreach($regions as $reg)
                                        <option value="{{ $reg->id }}" {{ in_array($reg->id, $selectedRegionIds) ? 'selected' : '' }}>
                                            {{ $reg->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Categories --}}
                            <div class="col-md-12 mb-3" id="cats-box">
                                <label class="form-label">{{ \App\CPU\translate('الأقسام') }} <span class="text-danger">*</span></label>
                                <select name="cats[]" class="form-control select2-multiple" multiple required>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}" @if($seller->cats->contains('cat_id', $cat->id)) selected @endif>
                                            {{ $cat->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Customers --}}
                            <div class="col-md-12 mb-3" id="customers-box">
                                <label class="form-label">{{ \App\CPU\translate('العملاء') }} <span class="text-danger">*</span></label>
                                <select name="customers[]" class="form-control select2-multiple" multiple >
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" @if($seller->customers->contains('customer_id', $customer->id)) selected @endif>
                                            {{ $customer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        </div>

                        <div class="sf-section">
                            <h6 class="sf-section-title"><i class="tio-truck"></i> العهدة والتشغيل <span></span></h6>
                            <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('الخزن') }} <span class="text-danger">*</span></label>
                                <select name="store_id" class="form-control select2-single" required>
                                    @foreach($storages as $storage)
                                        <option value="{{ $storage->id }}" @if($seller->storages->contains('storage_id', $storage->id)) selected @endif>
                                            {{ $storage->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('كود المندوب') }} <span class="text-danger">*</span></label>
                                <input type="text" name="mandob_code" class="form-control" value="{{ old('mandob_code', $seller->mandob_code) }}" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('المركبة') }} <span class="text-danger">*</span></label>
                                <select name="vehicle_code" class="form-control select2-single" required>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->store_id }}" @if($seller->vehicle_code == $vehicle->store_id) selected @endif>
                                            {{ $vehicle->store_name1 }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>
                        </div>

                        <div class="sf-section">
                            <h6 class="sf-section-title"><i class="tio-money"></i> الأجر والمستهدف <span></span></h6>
                            <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('نسبة المبيعات %') }}</label>
                                <input type="number" name="precent_of_sales" class="form-control" value="{{ old('precent_of_sales', $seller->precent_of_sales) }}" step="0.01">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('الاجازات') }}</label>
                                <input type="number" name="holidays" class="form-control" value="{{ old('holidays', $seller->holidays) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ \App\CPU\translate('عدد الزيارات المطلوبة') }}</label>
                                <input type="number" name="visitors" class="form-control" value="{{ old('visitors', $seller->visitors) }}">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">{{ \App\CPU\translate('الراتب') }}</label>
                                <input type="number" name="salary" class="form-control" value="{{ old('salary', $seller->salary) }}" step="0.01">
                            </div>

                            {{-- Shifts (JSON Array) --}}
                        </div>
                        </div>

                        <div class="sf-section">
                            <h6 class="sf-section-title"><i class="tio-shield"></i> الدور والصلاحيات <span></span></h6>
                            <div class="row g-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">اسم الشيفت <span class="text-danger">*</span></label>
                                    <select name="shift_id[]" class="form-control select2-multiple" multiple required>
                                        <option value="" hidden>-- اختر شيفت --</option>
                                        @foreach($shifts as $item)
                                            <option value="{{ $item->id }}" {{ in_array($item->id, $oldShiftsArray) ? 'selected' : '' }}>
                                                {{ $item->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- النوع --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>النوع <span class="text-danger">*</span></label>
                                    <select name="type" id="user-type" class="form-control select2-single" required>
                                        <option value="" disabled {{ old('type', $seller->type) ? '' : 'selected' }}>اختر النوع</option>
                                        <option value="mandob" {{ old('type', $seller->type) == 'mandob' ? 'selected' : '' }}>مندوب</option>
                                        <option value="manager" {{ old('type', $seller->type) == 'manager' ? 'selected' : '' }}>مدير</option>
                                        <option value="bigmanager" {{ old('type', $seller->type) == 'bigmanager' ? 'selected' : '' }}>مدير المدير</option>
                                    </select>
                                </div>
                            </div>

                            {{-- صندوق اختيار المستخدمين (admins[]) --}}
                            <div class="col-md-4" id="admin-select-box" style="{{ in_array(old('type', $seller->type), ['manager','bigmanager']) ? '' : 'display:none;' }}">
                                <div class="form-group">
                                    <label>اختر المستخدمين</label>
                                    <select name="admins[]" class="form-control select2-multiple" multiple>
                                        @forelse($adminCandidates as $cand)
                                            <option value="{{ $cand->id }}" {{ in_array($cand->id, old('admins', $selectedAdminSellerIds)) ? 'selected' : '' }}>
                                                {{ $cand->f_name }} {{ $cand->l_name }}
                                            </option>
                                        @empty
                                            <option disabled>لا يوجد مستخدمين متاحين</option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>

                            {{-- صلاحيات المدير --}}
                            <div class="col-12" id="manager-permissions-box" style="{{ in_array(old('type', $seller->type), ['manager','bigmanager']) ? '' : 'display:none;' }}">
                                <label class="input-label">الصلاحيات كمدير <span class="text-danger">*</span></label>
                                <div class="container">
                                    @foreach ($permissionsmanager as $key => $label)
                                        @if ($loop->index % 3 === 0)<div class="row">@endif
                                        <div class="col-12 col-sm-4">
                                            <div class="form-group">
                                                <label class="input-label">{{ $label }}</label>
                                                <input type="checkbox" name="{{ $key }}" value="1" {{ old($key, $seller->$key ?? 0) == 1 ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                        @if ($loop->iteration % 3 === 0 || $loop->last)</div>@endif
                                    @endforeach
                                </div>
                            </div>

                        </div> {{-- row --}}
                        </div> {{-- sf-section --}}

                        {{-- الحفظ في شريطٍ ثابت أسفل البطاقة: كان زرًّا
                             وسط الصفحة بعد آخر حقل فيضيع بين المحتوى. --}}
                        <div class="sf-foot">
                            <a href="{{ route('admin.seller.list') }}" class="btn btn-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('تحديث') }}</button>
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
    $(function () {
        // تفعيل Select2
        $('.select2-multiple').select2({ placeholder: '{{ \App\CPU\translate('اختر') }}', width: '100%' });
        $('.select2-single').select2({ minimumResultsForSearch: Infinity, width: '100%' });

        const $type = $('#user-type');
        const $adminBox = $('#admin-select-box');
        const $permBox  = $('#manager-permissions-box');
        const $adminsSelect = $('select[name="admins[]"]');

        const $customersBox = $('#customers-box');
        const $catsBox      = $('#cats-box');
        const $regionsBox   = $('#regions-box');

        function toggleByType(type) {
            const isManager = (type === 'manager' || type === 'bigmanager');

            if (isManager) {
                $adminBox.show();
                $permBox.show();
            } else {
                $adminBox.hide();
                $permBox.hide();
                // تفريغ اختيار المستخدمين لو رجع لمندوب
                $adminsSelect.val(null).trigger('change');
            }

            // إظهار/إخفاء حقول المندوب
            $customersBox.css('display', isManager ? 'none' : 'block');
            $catsBox.css('display', isManager ? 'none' : 'block');
            $regionsBox.css('display', isManager ? 'none' : 'block');
        }

        toggleByType($type.val());
        $type.on('change', function () { toggleByType($(this).val()); });
    });
</script>
@endpush
