@extends('layouts.admin.app')

@section('title', \App\CPU\translate('update_seller'))

@push('css_or_js')
<link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

<style>
    .form-card { border-radius: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden; margin-bottom: 1.5rem; }
    .form-card .card-header { background: #bee0ec; color: #333; padding: 1rem 1.5rem; font-size: 1.25rem; font-weight: 600; border-bottom: none; }
    .form-card .card-body { padding: 1.5rem; }
    .form-label { font-weight: 600; }
    .btn-primary { background: #2596be; border-color: #2596be; }
    .select2-container--default .select2-selection--multiple { border-radius: 0.5rem; min-height: 44px; }
    .select2-container--default .select2-selection--single { border-radius: 0.5rem; height: 44px; padding: 0.375rem 1rem; }
    .select2-container { width: 100% !important; }
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

<div class="content container-fluid" dir="rtl">
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="card form-card">
                <div class="card-header">
                    {{ \App\CPU\translate('تعديل بيانات المندوب') }} <i class="tio-edit me-2"></i>
                </div>

                <div class="card-body">
                    <form action="{{ route('admin.seller.update', [$seller->id]) }}" method="post" id="seller_form" enctype="multipart/form-data">
                        @csrf

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

                            <div class="col-12 text-center mt-4">
                                <button type="submit" class="btn btn-primary">{{ \App\CPU\translate('تحديث') }}</button>
                            </div>
                        </div> {{-- row --}}
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
