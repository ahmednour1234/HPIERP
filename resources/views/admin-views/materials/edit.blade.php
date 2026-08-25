@extends('layouts.admin.app')

@section('title', 'تعديل المادة')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">تعديل المادة: {{ $material->name }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.materials.update', $material->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">الاسم</label>
                    <input type="text" name="name" value="{{ old('name', $material->name) }}" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">الوصف</label>
                    <textarea name="description" class="form-control">{{ old('description', $material->description) }}</textarea>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">الوحدة</label>
                        <select name="unit_id" class="form-control" required>
                            @foreach ($units as $unit)
                                <option value="{{ $unit->id }}" {{ $material->unit_id == $unit->id ? 'selected' : '' }}>
                                    {{ $unit->unit_type }} ({{ $unit->symbol }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">الضريبة</label>
                        <select name="tax_id" class="form-control">
                            <option value="">لا يوجد</option>
                            @foreach ($taxes as $tax)
                                <option value="{{ $tax->id }}" {{ $material->tax_id == $tax->id ? 'selected' : '' }}>
                                    {{ $tax->name }} - {{ $tax->rate }}%
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">استبدال ملف PDF (اختياري)</label>
                    <input type="file" name="pdf_file" class="form-control" accept="application/pdf">
                </div>

                <button type="submit" class="btn btn-primary">تحديث</button>
<a href="{{ route('admin.materials.byType', $material->material_type) }}" class="btn btn-secondary">إلغاء</a>
            </form>
        </div>
    </div>
</div>
@endsection
