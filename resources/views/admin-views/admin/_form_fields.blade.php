{{--
    حقول المستخدم، مشتركة بين الإضافة والتعديل.

    $admin يكون null في الإضافة، فكل قيمة تعود إلى old() وحدها.
--}}

@php
    $isEdit    = (bool) ($admin?->exists);
    $isSuper   = (bool) ($admin->is_super ?? false);
    $chosen    = old('roles', $isEdit ? $admin->roles->pluck('id')->all() : []);
    $chosenSel = old('sellers', $isEdit
        ? \App\Models\AdminSeller::where('admin_id', $admin->id)->pluck('seller_id')->all()
        : []);
@endphp

<div class="roles-panel">
    <div class="head"><h2><i class="tio-user-outlined mr-1"></i> البيانات الأساسية</h2></div>

    <div class="body">
        <div class="row">
            <div class="col-12 col-sm-6 mb-3">
                <label class="field-label" for="f_name">
                    {{\App\CPU\translate('first_name')}} <span class="req">*</span>
                </label>
                <input type="text" id="f_name" name="f_name" class="form-control"
                       value="{{ old('f_name', $admin->f_name ?? '') }}"
                       placeholder="{{\App\CPU\translate('first_name')}}" required>
            </div>

            <div class="col-12 col-sm-6 mb-3">
                <label class="field-label" for="l_name">
                    {{\App\CPU\translate('last_name')}} <span class="req">*</span>
                </label>
                <input type="text" id="l_name" name="l_name" class="form-control"
                       value="{{ old('l_name', $admin->l_name ?? '') }}"
                       placeholder="{{\App\CPU\translate('last_name')}}" required>
            </div>

            <div class="col-12 col-sm-6 mb-3">
                <label class="field-label" for="email">
                    {{\App\CPU\translate('email')}} <span class="req">*</span>
                </label>
                <input type="email" id="email" name="email" class="form-control" dir="ltr"
                       value="{{ old('email', $admin->email ?? '') }}"
                       placeholder="{{\App\CPU\translate('Ex_:_ex@example.com')}}" required>
            </div>

            <div class="col-12 col-sm-6 mb-3">
                <label class="field-label" for="password">
                    {{\App\CPU\translate('password')}}
                    @unless($isEdit)<span class="req">*</span>@endunless
                </label>
                <input type="text" id="password" name="password" class="form-control" dir="ltr"
                       placeholder="{{\App\CPU\translate('password')}}"
                       {{ $isEdit ? '' : 'required' }}>
                <div class="field-hint">
                    {{ $isEdit ? 'اتركها فارغة للإبقاء على كلمة المرور الحالية.' : '٨ أحرف على الأقل.' }}
                </div>
            </div>

            <div class="col-12 col-sm-6 mb-3 mb-sm-0">
                <label class="field-label" for="longitude">{{\App\CPU\translate('Longitude')}}</label>
                <input type="text" id="longitude" name="longitude" class="form-control" dir="ltr"
                       value="{{ old('longitude', $admin->longitude ?? '') }}"
                       placeholder="{{\App\CPU\translate('longitude')}}">
            </div>

            <div class="col-12 col-sm-6">
                <label class="field-label" for="latitude">{{\App\CPU\translate('Latitude')}}</label>
                <input type="text" id="latitude" name="latitude" class="form-control" dir="ltr"
                       value="{{ old('latitude', $admin->latitude ?? '') }}"
                       placeholder="{{\App\CPU\translate('latitude')}}">
            </div>
        </div>
    </div>
</div>

<div class="roles-panel">
    <div class="head"><h2><i class="tio-group-equal mr-1"></i> {{ \App\CPU\translate('sellers') }}</h2></div>

    <div class="body">
        <label class="field-label" for="sellers">
            المناديب التابعون له <span class="req">*</span>
        </label>

        {{-- اللوحة ترقّي كل select[multiple] إلى bootstrap-select، فلا يُعطى
             ارتفاعًا هنا: يُخفى العنصر الأصلي ويبقى الارتفاع أثرًا قبل عمل JS. --}}
        <select name="sellers[]" id="sellers" class="form-control" multiple required
                data-placeholder="اختر المناديب">
            @foreach($sellers as $cat)
                <option value="{{ $cat->id }}"
                    @selected(in_array($cat->id, $chosenSel))>{{ $cat->email }}</option>
            @endforeach
        </select>

        <div class="field-hint">أرقام لوحته ومناديبه تُحسب من هذه القائمة. اضغط Ctrl لاختيار أكثر من مندوب.</div>
    </div>
</div>

<div class="roles-panel">
    <div class="head">
        <h2><i class="tio-user-switch mr-1"></i> الأدوار والصلاحيات</h2>

        @unless($isSuper)
            <a href="{{ route('admin.roles.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="tio-settings-outlined mr-1"></i> إدارة الأدوار
            </a>
        @endunless
    </div>

    <div class="body">
        @if($isSuper)
            {{-- is_super يتجاوز الأدوار في الفحص، فإسنادها له بلا أثر. --}}
            <div class="super-note">
                <i class="tio-shield-outlined"></i>
                <span>سوبر أدمن — يملك كل الصلاحيات، والأدوار لا تغيّر ذلك.</span>
            </div>
        @elseif($roles->isEmpty())
            <div class="no-roles">
                لا توجد أدوار بعد.
                <a href="{{ route('admin.roles.create') }}">أنشئ دورًا أولًا</a>
                وإلا لن يرى هذا المستخدم شيئًا.
            </div>
        @else
            {{-- الصلاحيات تُمنح بالأدوار. المربّعات القديمة كانت تكتب أعمدة
                 لم يعد يقرأها أحد، فتوهم بصلاحية لا تُمنح. --}}
            <div class="role-picker">
                @foreach($roles as $role)
                    <label class="role-opt {{ in_array($role->id, $chosen) ? 'is-on' : '' }}"
                           for="role-{{ $role->id }}">
                        <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                               id="role-{{ $role->id }}" @checked(in_array($role->id, $chosen))>
                        <span>
                            <span class="r-name d-block">{{ $role->label }}</span>
                            <span class="r-slug">{{ $role->name }}</span>
                            @if($role->description)
                                <span class="r-desc d-block mt-1">{{ $role->description }}</span>
                            @endif
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="roles-warn" id="noRoleWarn" hidden>
                <i class="tio-warning"></i>
                <span>بلا دور، لن يرى هذا المستخدم أي شيء في اللوحة.</span>
            </div>
        @endif
    </div>
</div>
