@extends('layouts.admin.app')

@section('title', $role->exists ? 'تعديل دور' : 'دور جديد')

@push('css_or_js')
@include('admin-views.roles._tokens')
<style>
    /* ---------- بيانات الدور ---------- */

    .field-label {
        font-size: .8rem;
        font-weight: 700;
        color: var(--hpi-ink);
        margin-bottom: .35rem;
        display: block;
    }

    .roles-panel .form-control {
        border-color: var(--hpi-line);
        border-radius: 10px;
        padding: .55rem .8rem;
        height: auto;
    }

    .roles-panel .form-control:focus {
        border-color: var(--hpi-blue);
        box-shadow: 0 0 0 .18rem rgba(142,197,239,.28);
    }

    .roles-panel .form-control[readonly] { background: #f3f6f9; color: var(--hpi-muted); }

    .field-hint { font-size: .74rem; color: var(--hpi-muted); margin-top: .3rem; }

    /* ---------- عدّاد الصلاحيات ---------- */

    .perm-counter {
        display: inline-flex;
        align-items: baseline;
        gap: .25rem;
        font-size: .8rem;
        font-weight: 700;
        color: var(--hpi-navy);
        background: #eaf4fb;
        border-radius: 99px;
        padding: .2rem .7rem;
        direction: ltr;
    }

    .perm-counter small { font-weight: 600; color: var(--hpi-muted); }

    .perm-search {
        position: relative;
        flex: 1;
        min-width: 180px;
        max-width: 280px;
    }

    .perm-search input {
        width: 100%;
        border: 1px solid var(--hpi-line);
        border-radius: 99px;
        padding: .4rem .85rem .4rem 2rem;
        font-size: .84rem;
    }

    .perm-search input:focus {
        outline: 0;
        border-color: var(--hpi-blue);
        box-shadow: 0 0 0 .18rem rgba(142,197,239,.28);
    }

    .perm-search i {
        position: absolute;
        inset-inline-start: .7rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--hpi-muted);
        font-size: .9rem;
        pointer-events: none;
    }

    /* ---------- شبكة الأقسام ---------- */

    .perm-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: .85rem;
    }

    .perm-group {
        border: 1px solid var(--hpi-line);
        border-radius: 12px;
        overflow: hidden;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    /* القسم المفعَّل كليًّا أو جزئيًّا يُميَّز بحافّته، فتُقرأ الحالة من نظرة. */
    .perm-group.is-partial { border-color: var(--hpi-blue); }
    .perm-group.is-full    { border-color: #4cc38a; box-shadow: 0 0 0 1px #4cc38a inset; }

    .perm-group .g-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: .5rem;
        padding: .6rem .8rem;
        background: #f6fafd;
        border-bottom: 1px solid var(--hpi-line);
    }

    .perm-group.is-full .g-head { background: #eefaf4; }

    .g-title { font-size: .86rem; font-weight: 700; color: var(--hpi-ink); }

    .g-count {
        font-size: .72rem;
        font-weight: 700;
        color: var(--hpi-muted);
        direction: ltr;
    }

    .perm-group.is-full .g-count { color: #0f7a4d; }

    .g-toggle {
        font-size: .72rem;
        font-weight: 700;
        color: var(--hpi-navy);
        background: transparent;
        border: 1px solid var(--hpi-line);
        border-radius: 99px;
        padding: .05rem .5rem;
        cursor: pointer;
    }

    .g-toggle:hover { background: #eaf4fb; border-color: var(--hpi-blue); }

    .perm-group .g-body { padding: .5rem .35rem; }

    /* كل صلاحية سطر كامل قابل للنقر، لا مربّع صغير بجواره نص. */
    .perm-row {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .3rem .5rem;
        border-radius: 8px;
        font-size: .83rem;
        color: var(--hpi-ink);
        cursor: pointer;
        margin: 0;
    }

    .perm-row:hover { background: #f6fafd; }

    .perm-row input { width: 15px; height: 15px; accent-color: var(--hpi-navy); cursor: pointer; }

    .perm-empty {
        grid-column: 1 / -1;
        padding: 2rem 1rem;
        text-align: center;
        color: var(--hpi-muted);
        font-size: .86rem;
    }

    /* ---------- شريط الحفظ ---------- */

    .save-bar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .85rem 1.2rem;
        background: rgba(255,255,255,.96);
        border: 1px solid var(--hpi-line);
        border-radius: 14px;
        box-shadow: 0 -6px 22px rgba(20,57,92,.08);
    }

    .save-bar .spacer { flex: 1; }

    .locked-note {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        padding: 1rem 1.2rem;
        border-radius: 12px;
        background: #eefaf4;
        border: 1px solid #bfe8d4;
        color: #0f5c3c;
        font-size: .86rem;
    }

    .locked-note i { font-size: 1.1rem; }
</style>
@endpush

@section('content')
<div class="roles-wrap">

    <div class="roles-hero">
        <div class="hero-text">
            <h1>
                <i class="tio-user-switch mr-1"></i>
                {{ $role->exists ? 'تعديل الدور' : 'دور جديد' }}
            </h1>
            <p>
                {{ $role->exists
                    ? $role->label . ' — اختر ما يفتحه هذا الدور من أقسام'
                    : 'سمِّ الدور ثم اختر ما يفتحه من أقسام' }}
            </p>
        </div>

        <div class="hero-actions">
            <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">
                <i class="tio-back-ui mr-1"></i> رجوع للأدوار
            </a>
        </div>
    </div>

    <form action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
          method="POST">
        @csrf
        @if($role->exists) @method('PUT') @endif

        <div class="roles-panel">
            <div class="head"><h2><i class="tio-info-outined mr-1"></i> بيانات الدور</h2></div>

            <div class="body">
                <div class="row">
                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="field-label" for="role-label">اسم الدور</label>
                        <input type="text" id="role-label" name="label"
                               class="form-control @error('label') is-invalid @enderror"
                               value="{{ old('label', $role->label) }}" placeholder="مثال: محاسب" required>
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="field-hint">الاسم الظاهر في القوائم.</div>
                    </div>

                    <div class="col-md-4 mb-3 mb-md-0">
                        <label class="field-label" for="role-name">المعرّف</label>
                        {{-- المعرّف مفتاح الدور في الكود، فلا يُغيَّر على دور نظام. --}}
                        <input type="text" id="role-name" name="name" dir="ltr"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $role->name) }}" placeholder="accountant"
                               {{ $role->is_locked ? 'readonly' : '' }} required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="field-hint">
                            {{ $role->is_locked
                                ? 'دور نظام — المعرّف ثابت لأن الكود يعتمد عليه.'
                                : 'حروف إنجليزية صغيرة وأرقام وشرطات.' }}
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="field-label" for="role-desc">الوصف</label>
                        <input type="text" id="role-desc" name="description" class="form-control"
                               value="{{ old('description', $role->description) }}"
                               placeholder="اختياري">
                        <div class="field-hint">سطر يوضّح متى يُستعمل هذا الدور.</div>
                    </div>
                </div>
            </div>
        </div>

        @if($role->is_locked)
            <div class="locked-note mb-3">
                <i class="tio-shield-outlined"></i>
                <div>
                    <strong>دور نظام.</strong>
                    يملك كل الصلاحيات تلقائيًّا، وأي قسم يُضاف لاحقًا يفتحه دون تعديل،
                    فلا تُختار صلاحياته من هنا.
                </div>
            </div>
        @else
            <div class="roles-panel">
                <div class="head">
                    <h2><i class="tio-checkmark-square-outlined mr-1"></i> الصلاحيات</h2>

                    <div class="d-flex align-items-center flex-wrap" style="gap:.5rem;">
                        <span class="perm-counter">
                            <span id="permCount">0</span>
                            <small>/ {{ $total_perms }}</small>
                        </span>

                        <div class="perm-search">
                            <i class="tio-search"></i>
                            <input type="search" id="permSearch" placeholder="ابحث عن قسم…"
                                   autocomplete="off">
                        </div>

                        <button type="button" class="btn btn-sm btn-outline-primary" id="checkAll">تحديد الكل</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheckAll">إلغاء الكل</button>
                    </div>
                </div>

                <div class="body">
                    <div class="perm-grid" id="permGrid">
                        @foreach($groups as $group => $meta)
                            <div class="perm-group" data-group="{{ $group }}"
                                 data-label="{{ $meta['label'] }}">
                                <div class="g-head">
                                    <span class="g-title">{{ $meta['label'] }}</span>

                                    <span class="d-flex align-items-center" style="gap:.4rem;">
                                        <span class="g-count">0/{{ count($meta['actions']) }}</span>
                                        <button type="button" class="g-toggle" data-group="{{ $group }}">الكل</button>
                                    </span>
                                </div>

                                <div class="g-body">
                                    @foreach($meta['actions'] as $action => $actionLabel)
                                        @php($name = $group . '.' . $action)
                                        <label class="perm-row" for="perm-{{ $name }}">
                                            <input class="perm-box" type="checkbox"
                                                   name="permissions[]" value="{{ $name }}"
                                                   id="perm-{{ $name }}"
                                                   data-group="{{ $group }}"
                                                   @checked(in_array($name, old('permissions', $selected), true))>
                                            <span>{{ $actionLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        <div class="perm-empty" id="permEmpty" hidden>لا قسم يطابق البحث.</div>
                    </div>
                </div>
            </div>
        @endif

        <div class="save-bar">
            <button type="submit" class="btn btn-primary px-4">
                <i class="tio-save mr-1"></i> حفظ
            </button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary px-4">إلغاء</a>

            <span class="spacer"></span>

            @unless($role->is_locked)
                <span class="text-muted small d-none d-md-inline" id="saveHint"></span>
            @endunless
        </div>
    </form>
</div>
@endsection

@push('script_2')
<script>
    (function () {
        var boxes = Array.prototype.slice.call(document.querySelectorAll('.perm-box'));

        if (!boxes.length) { return; }

        var counter = document.getElementById('permCount');
        var hint    = document.getElementById('saveHint');
        var groups  = Array.prototype.slice.call(document.querySelectorAll('.perm-group'));

        /* العدّادات وحالة كل قسم تُشتق من الصناديق نفسها، فلا تتناقض معها. */
        function refresh() {
            var total = 0;

            groups.forEach(function (group) {
                var inGroup = group.querySelectorAll('.perm-box');
                var on = 0;

                Array.prototype.forEach.call(inGroup, function (b) { if (b.checked) { on++; } });

                total += on;

                group.querySelector('.g-count').textContent = on + '/' + inGroup.length;
                group.classList.toggle('is-full', on === inGroup.length && on > 0);
                group.classList.toggle('is-partial', on > 0 && on < inGroup.length);
            });

            if (counter) { counter.textContent = total; }

            if (hint) {
                hint.textContent = total === 0
                    ? 'دور بلا صلاحيات لن يفتح شيئًا.'
                    : '';
            }
        }

        boxes.forEach(function (b) { b.addEventListener('change', refresh); });

        function setAll(on) {
            /* الأقسام المخفيّة بالبحث لا تُمس، وإلا غيّر الزر ما لا يراه المستخدم. */
            groups.forEach(function (group) {
                if (group.hidden) { return; }

                Array.prototype.forEach.call(
                    group.querySelectorAll('.perm-box'),
                    function (b) { b.checked = on; }
                );
            });

            refresh();
        }

        var all  = document.getElementById('checkAll');
        var none = document.getElementById('uncheckAll');

        if (all)  { all.addEventListener('click', function () { setAll(true); }); }
        if (none) { none.addEventListener('click', function () { setAll(false); }); }

        document.querySelectorAll('.g-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var inGroup = document.querySelectorAll(
                    '.perm-box[data-group="' + btn.dataset.group + '"]'
                );
                var allOn = Array.prototype.every.call(inGroup, function (b) { return b.checked; });

                Array.prototype.forEach.call(inGroup, function (b) { b.checked = !allOn; });
                refresh();
            });
        });

        /* بحث بالاسم العربي أو بالمعرّف: 41 قسمًا أطول من أن تُمسح بالعين. */
        var search = document.getElementById('permSearch');
        var empty  = document.getElementById('permEmpty');

        if (search) {
            search.addEventListener('input', function () {
                var q = search.value.trim().toLowerCase();
                var shown = 0;

                groups.forEach(function (group) {
                    var hit = !q
                        || group.dataset.label.toLowerCase().indexOf(q) !== -1
                        || group.dataset.group.toLowerCase().indexOf(q) !== -1;

                    group.hidden = !hit;
                    if (hit) { shown++; }
                });

                if (empty) { empty.hidden = shown !== 0; }
            });
        }

        refresh();
    })();
</script>
@endpush
