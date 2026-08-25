@extends('layouts.admin.app')

@section('title', 'إضافة مادة جديدة')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
    <div class="card-header bg-info text-white">
    @php
        $typeArabic = match($material_type) {
            'raw' => 'المواد الخام',
            'primary_packaging' => 'مواد التغليف الأساسية',
            'secondary_packaging' => 'مواد التغليف الثانوية',
            default => 'نوع غير معروف'
        };
    @endphp

    <h5 class="mb-0">إضافة مادة جديدة ({{ $typeArabic }})</h5>
</div>
        <div class="card-body">
            <form action="{{ route('admin.materials.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="material_type" value="{{ $material_type }}">

                <div class="mb-3">
                    <label class="form-label">اسم المادة</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الوحدة</label>
                        <select name="unit_id" class="form-control" required>
                            <option value="">اختر وحدة</option>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->unit_type }} ({{ $unit->symbol }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">الضريبة</label>
                        <select name="tax_id" class="form-control">
                            <option value="">لا يوجد</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->name }} - {{ $tax->rate }}%</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">رفع ملف PDF</label>
                    <input type="file" name="pdf_file" class="form-control" accept=\"application/pdf\">
                </div>

                <button type="submit" class="btn btn-primary">حفظ المادة</button>
<a href="{{ route('admin.materials.byType', $material_type) }}" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>
@endsection
