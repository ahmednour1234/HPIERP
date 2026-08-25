@extends('layouts.admin.app')

@section('title', \App\CPU\translate('قائمة المصانع'))

@push('css_or_js')
    <style>
        .table thead {
            background-color: #bee0ec;
        }
        .custom-header {
            background: #bee0ec;
            border-radius: 6px;
            padding: 10px 20px;
            margin-bottom: 20px;
        }
        .btn-custom {
            background-color: #bee0ec;
            border-color: #bee0ec;
            color: #000;
        }
        .btn-custom:hover {
            background-color: #a6d3e4;
        }
    </style>
@endpush

@section('content')
<div class="container">
    <div class="custom-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0">قائمة المصانع</h4>
        <a href="{{ route('admin.factories.create') }}" class="btn btn-custom">➕ إضافة مصنع جديد</a>
    </div>

    {{-- ✅ نموذج بحث --}}
    <form method="GET" action="{{ route('admin.factories.index') }}" class="mb-3">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="ابحث بالاسم أو البريد">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-custom w-100">🔍 بحث</button>
            </div>
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-bordered text-center align-middle">
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الهاتف</th>
                    <th>البريد</th>
                    <th>العنوان</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($factories as $factory)
                    <tr>
                        <td>{{ $factory->name }}</td>
                        <td>{{ $factory->phone }}</td>
                        <td>{{ $factory->email }}</td>
                        <td>{{ $factory->address }}</td>
                        <td>
                            <span class="badge {{ $factory->active ? 'bg-success' : 'bg-danger' }}">
                                {{ $factory->active ? 'مفعل' : 'غير مفعل' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('admin.factories.show', $factory->id) }}" class="btn btn-info btn-sm">👁️ عرض</a>
                            <a href="{{ route('admin.factories.edit', $factory->id) }}" class="btn btn-primary btn-sm">✏️ تعديل</a>

                            <form action="{{ route('admin.factories.toggle-active', $factory->id) }}" method="POST" class="d-inline-block">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-warning btn-sm">🔁 تفعيل/إيقاف</button>
                            </form>

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">لا توجد مصانع</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
