@extends('layouts.admin.app')

@section('title', 'تفاصيل المادة')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">تفاصيل المادة</h5>
        </div>
        <div class="card-body">
                @php
                // ترجمة نوع المادة إلى العربية
                $typeArabic = match($material->material_type) {
                    'raw' => 'المواد الخام',
                    'primary_packaging' => 'مواد التغليف الأساسية',
                    'secondary_packaging' => 'مواد التغليف الثانوية',
                    default => 'المواد'
                };
            @endphp

            <h5>{{ $material->name }}</h5>
            <p><strong>الوصف:</strong> {{ $material->description }}</p>
            <p><strong>النوع:</strong> {{ $typeArabic }}</p>
            <p><strong>الوحدة:</strong> {{ $material->unit->unit_type ?? '-' }}</p>
            <p><strong>الضريبة:</strong> {{ $material->tax->name ?? 'لا توجد' }}</p>
            <p><strong>الملف:</strong>
                @if ($material->pdf_file)
                    <a href="{{ asset('storage/materials/pdf/' . $material->pdf_file) }}" target="_blank">📄 عرض الملف</a>
                @else
                    -
                @endif
            </p>


            <hr>
            <h5>الدفعات (Batches)</h5>

            {{-- 🔍 نموذج بحث وترتيب --}}
            <form method="GET" class="mb-4">
                <div class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="batch_search" value="{{ request('batch_search') }}" class="form-control" placeholder="ابحث بالكود الفريد...">
                    </div>
                    <div class="col-md-3">
                        <select name="sort" class="form-control">
                            <option value="">ترتيب حسب</option>
                            <option value="quantity_asc" {{ request('sort') == 'quantity_asc' ? 'selected' : '' }}>الكمية ↑</option>
                            <option value="quantity_desc" {{ request('sort') == 'quantity_desc' ? 'selected' : '' }}>الكمية ↓</option>
                            <option value="expiry_asc" {{ request('sort') == 'expiry_asc' ? 'selected' : '' }}>تاريخ الانتهاء ↑</option>
                            <option value="expiry_desc" {{ request('sort') == 'expiry_desc' ? 'selected' : '' }}>تاريخ الانتهاء ↓</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">🔍 بحث</button>
                    </div>
                </div>
            </form>

            @php
                $batches = $material->batches();
                if(request('batch_search')) {
                    $batches->where('unique_code', 'like', '%'.request('batch_search').'%');
                }
                if(request('sort') == 'quantity_asc') {
                    $batches->orderBy('quantity', 'asc');
                } elseif(request('sort') == 'quantity_desc') {
                    $batches->orderBy('quantity', 'desc');
                } elseif(request('sort') == 'expiry_asc') {
                    $batches->orderBy('expiration_date', 'asc');
                } elseif(request('sort') == 'expiry_desc') {
                    $batches->orderBy('expiration_date', 'desc');
                }
                $batches = $batches->get();
            @endphp

            <div class="table-responsive">
                <table class="table table-bordered table-striped text-center align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>الكمية</th>
                            <th>تاريخ الانتهاء</th>
                            <th>الكود الفريد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($batches as $batch)
                            <tr>
                                <td>{{ $batch->quantity }} {{ $material->unit->symbol ?? '' }}</td>
                                <td>{{ $batch->expiration_date ?? '-' }}</td>
                                <td>{{ $batch->unique_code }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3">لا توجد دفعات.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <a href="{{ route('admin.materials.byType', $material->material_type) }}" class="btn btn-secondary mt-3">عودة</a>
        </div>
    </div>
</div>
@endsection
