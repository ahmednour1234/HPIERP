@extends('layouts.admin.app')

@section('title', 'قائمة المواد')

@section('content')
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <div>
             @php
    $typeArabic = match($type) {
        'raw' => 'المواد الخام',
        'primary_packaging' => 'مواد التغليف الأساسية',
        'secondary_packaging' => 'مواد التغليف الثانوية',
        default => 'المواد',
    };
@endphp

<h5 class="mb-0 text-dark">
    🗂️ {{ $typeArabic }}
</h5>

                <small class="text-muted">عدد السجلات: {{ $materials->total() }}</small>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('admin.materials.create', ['type' => $type]) }}" class="btn btn-sm btn-success">
                    ➕ إضافة مادة
                </a>
                <!--<button onclick="window.print()" class="btn btn-sm btn-secondary">-->
                <!--    🖨️ طباعة-->
                <!--</button>-->
            </div>
        </div>

        <div class="card-body">
            {{-- ✅ نموذج بحث --}}
            <form method="GET" action="{{ route('admin.materials.byType', $type) }}" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-6">
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="ابحث بالاسم...">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">🔍 بحث</button>
                    </div>
                </div>
            </form>

            {{-- ✅ جدول عرض المواد --}}
            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-info">
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>الوحدة</th>
                            <th>ملف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($materials as $material)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $material->name }}</td>
                                <td>{{ $material->unit->unit_type ?? '-' }}</td>
                                <td>
                                    @if ($material->pdf_file)
                                        <a href="{{ asset('storage/app/public/materials/pdf/' . $material->pdf_file) }}" target="_blank">📄 عرض</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.materials.show', $material->id) }}" class="btn btn-sm btn-outline-info">👁️ عرض</a>
                                    <a href="{{ route('admin.materials.edit', $material->id) }}" class="btn btn-sm btn-outline-primary">✏️ تعديل</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">لا توجد مواد حالياً من هذا النوع.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ✅ ترقيم الصفحات --}}
            <div class="mt-3">
                {{ $materials->appends(['search' => request('search')])->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
