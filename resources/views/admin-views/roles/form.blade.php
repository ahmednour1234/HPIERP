@extends('layouts.admin.app')

@section('title', $role->exists ? 'تعديل دور' : 'دور جديد')

@section('content')
<div class="container my-5 pt-5" style="padding-right:180px;">

    <h1 class="page-header-title mb-4">
        <i class="tio-user-switch"></i>
        {{ $role->exists ? 'تعديل الدور: ' . $role->label : 'دور جديد' }}
    </h1>

    <form action="{{ $role->exists ? route('admin.roles.update', $role) : route('admin.roles.store') }}"
          method="POST">
        @csrf
        @if($role->exists) @method('PUT') @endif

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold">اسم الدور</label>
                        <input type="text" name="label" class="form-control @error('label') is-invalid @enderror"
                               value="{{ old('label', $role->label) }}" placeholder="مثال: محاسب" required>
                        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold">المعرّف</label>
                        {{-- المعرّف مفتاح الدور في الكود، فلا يُغيَّر على دور نظام. --}}
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $role->name) }}" placeholder="accountant"
                               {{ $role->is_locked ? 'readonly' : '' }} required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <small class="form-text text-muted">حروف إنجليزية صغيرة وأرقام وشرطات.</small>
                    </div>

                    <div class="col-md-4 mb-3">
                        <label class="form-label font-weight-bold">الوصف</label>
                        <input type="text" name="description" class="form-control"
                               value="{{ old('description', $role->description) }}">
                    </div>
                </div>
            </div>
        </div>

        @if($role->is_locked)
            <div class="alert alert-info">
                هذا دور نظام يملك كل الصلاحيات، ولا تُعدَّل صلاحياته من هنا.
            </div>
        @else
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">الصلاحيات</h5>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="checkAll">تحديد الكل</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="uncheckAll">إلغاء الكل</button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        @foreach($groups as $group => $meta)
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="border rounded p-3 h-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <strong>{{ $meta['label'] }}</strong>
                                        <button type="button" class="btn btn-xs btn-link p-0 group-toggle"
                                                data-group="{{ $group }}">الكل</button>
                                    </div>

                                    @foreach($meta['actions'] as $action => $actionLabel)
                                        @php($name = $group . '.' . $action)
                                        <div class="form-check">
                                            <input class="form-check-input perm-box" type="checkbox"
                                                   name="permissions[]" value="{{ $name }}"
                                                   id="perm-{{ $name }}"
                                                   data-group="{{ $group }}"
                                                   @checked(in_array($name, old('permissions', $selected), true))>
                                            <label class="form-check-label" for="perm-{{ $name }}">
                                                {{ $actionLabel }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="d-flex" style="gap:.5rem;">
            <button type="submit" class="btn btn-primary px-4">حفظ</button>
            <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary px-4">إلغاء</a>
        </div>
    </form>
</div>

@push('script_2')
<script>
    (function () {
        var boxes = document.querySelectorAll('.perm-box');

        function setAll(on) {
            boxes.forEach(function (b) { b.checked = on; });
        }

        var all = document.getElementById('checkAll');
        var none = document.getElementById('uncheckAll');

        if (all)  { all.addEventListener('click', function () { setAll(true); }); }
        if (none) { none.addEventListener('click', function () { setAll(false); }); }

        // زر "الكل" داخل القسم يقلب حالة القسم وحده.
        document.querySelectorAll('.group-toggle').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var group = btn.dataset.group;
                var groupBoxes = document.querySelectorAll('.perm-box[data-group="' + group + '"]');
                var allOn = Array.prototype.every.call(groupBoxes, function (b) { return b.checked; });

                groupBoxes.forEach(function (b) { b.checked = !allOn; });
            });
        });
    })();
</script>
@endpush
@endsection
