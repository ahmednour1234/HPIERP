<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ \App\CPU\translate('الاسم الاول') }} <span class="text-danger">*</span></label>
        <input type="text" name="f_name" class="form-control" value="{{ $seller->f_name }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ \App\CPU\translate('الاسم الاخير') }} <span class="text-danger">*</span></label>
        <input type="text" name="l_name" class="form-control" value="{{ $seller->l_name }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ \App\CPU\translate('البريد الالكتروني') }} <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="{{ $seller->email }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ \App\CPU\translate('كلمة المرور') }}</label>
        <input type="password" name="password" class="form-control">
    </div>
<!-- Regions -->
             <div class="col-md-12 mb-3" id="regions-box">
    <label class="form-label">{{ \App\CPU\translate('المناطق') }} <span class="text-danger">*</span></label>
    @php
        $selectedRegionIds = DB::table('seller_regions')
            ->where('seller_id', $seller->id)
            ->pluck('region_id')
            ->toArray();
    @endphp

    <select name="reg[]" class="form-control select2-multiple w-100" multiple>
        @foreach($regions as $reg)
            <option value="{{ $reg->id }}" {{ in_array($reg->id, $selectedRegionIds) ? 'selected' : '' }}>
                {{ $reg->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="col-md-12 mb-3"  id="cats-box">
    <label class="form-label">{{ \App\CPU\translate('الأقسام') }} <span class="text-danger">*</span></label>
    <select name="cats[]" class="form-control select2-multiple w-100" multiple >
        @foreach($categories as $cat)
            <option value="{{ $cat->id }}" @if($seller->cats->contains('cat_id', $cat->id)) selected @endif>
                {{ $cat->name }}
            </option>
        @endforeach
    </select>
</div>

<div class="col-md-12 mb-3" id="customers-box">
    <label class="form-label">{{ \App\CPU\translate('العملاء') }} <span class="text-danger">*</span></label>
    <select name="customers[]" class="form-control select2-multiple w-100" multiple >
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
                                        <option value="{{ $storage->id }}" @if($seller->storages->contains('storage_id', $storage->id)) selected @endif>{{ $storage->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('كود المندوب') }} <span class="text-danger">*</span></label>
                                <input type="text" name="mandob_code" class="form-control" value="{{ $seller->mandob_code }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('المركبة') }} <span class="text-danger">*</span></label>
                                <select name="vehicle_code" class="form-control select2-single" required>
                                    @foreach($vehicles as $vehicle)
                                        <option value="{{ $vehicle->store_id }}" @if($seller->vehicle_code == $vehicle->store_id) selected @endif>{{ $vehicle->store_name1 }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Other Fields -->
                           
                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('نسبة المبيعات %') }}</label>
                                <input type="number" name="precent_of_sales" class="form-control" value="{{ $seller->precent_of_sales }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">{{ \App\CPU\translate('الاجازات') }}</label>
                                <input type="number" name="holidays" class="form-control" value="{{ $seller->holidays }}">
                            </div>
                           
                             <div class="col-md-6">
                                <label class="form-label">{{ \App\CPU\translate('عدد الزيارات المطلوبة') }}</label>
                                <input type="number" name="visitors" class="form-control" value="{{ $seller->visitors }}">
                            </div>
                                <!-- مثال: -->
    <div class="col-md-6">
        <label class="form-label">{{ \App\CPU\translate('الراتب') }}</label>
        <input type="number" name="salary" class="form-control" value="{{ $seller->salary }}">
    </div>
        <div class="col-md-6">
                                <div class="form-group">
                                    <label class="input-label">اسم الشيفت <span class="text-danger">*</span></label>
                @php
    // لو بتعدل سجل، هتجيب القيمة الحالية من قاعدة البيانات
    $oldShifts = old('shift_id', isset($seller) ? $seller->shift_id : '[]');
    $oldShiftsArray = json_decode($oldShifts, true) ?: [];
@endphp

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
    
    <!-- النوع -->
    <div class="col-md-4">
        <div class="form-group">
            <label>النوع <span class="text-danger">*</span></label>
            <select name="type" id="user-type" class="form-control select2-single" required>
                <option value="" disabled>اختر النوع</option>
                <option value="mandob" {{ old('type', $seller->type) == 'mandob' ? 'selected' : '' }}>مندوب</option>
                <option value="manager" {{ old('type', $seller->type) == 'manager' ? 'selected' : '' }}>مدير</option>
                <option value="bigmanager" {{ old('type', $seller->type) == 'bigmanager' ? 'selected' : '' }}>مدير المدير</option>
            </select>
        </div>
    </div>
    <div class="col-md-4" id="admin-select-box" style="{{ in_array(old('type', $seller->type), ['manager', 'bigmanager']) ? '' : 'display: none;' }}">
        <div class="form-group">
            <label>اختر المستخدمين</label>
            <select name="admins[]" class="form-control select2-multiple" multiple></select>
        </div>
    </div>
    <!-- مربع صلاحيات المدير -->
    <div class="col-12" id="manager-permissions-box" style="display: none;">
        <label class="input-label">الصلاحيات كمدير <span class="text-danger">*</span></label>
        <div class="container">
            @php
                $permissionsmanager = [
                    'supplier' => 'رؤية الموردين', 'dashboard' => 'رؤية الاحصائيات', 'pos' => 'رؤية الفواتير',
                    'store' => 'رؤية السيارات', 'cat' => 'رؤية الاقسام', 'unit' => 'رؤية وحدات القياس',
                    'product' => 'رؤية المنتجات', 'stock_limit' => 'رؤية كشف النواقص', 'customer' => 'رؤية العملاء',
                    'seller' => 'رؤية المناديب', 'admin' => 'رؤية الادمن', 'setting' => 'رؤية اعدادات البرنامج',
                    'storage' => 'رؤية الخزن', 'requests' => 'رؤية الطلبات ', 'notification' => 'رؤية الاشعارات',
                    'tracking' => 'رؤية خريطة المناديب', 'regions' => 'رؤية المناطق', 'reports' => 'رؤية التقارير',
                    'stock' => 'رؤية الرحلات', 'visit' => 'رؤية قسم الزيارات', 'rating' => 'رؤية قسم تقييم المناديب',
                    'sectionsalary' => 'رؤية قسم المرتبات', 'accounts' => 'رؤية قسم الحسابات', 'sales' => 'رؤية قسم انشاء مبيعات'
               ,'production'=>'إنتاج وتصنيع','hr'=>'إدارة الموارد البشرية' ,'attendance'=>'الحضور والأنصراف'];
            @endphp
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
</div>
