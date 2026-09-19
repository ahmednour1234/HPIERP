@extends('layouts.admin.app')

@section('title', 'الأدوار والصلاحيات')

@section('content')
<div class="container my-5 pt-5" style="padding-right:180px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-header-title mb-0">
            <i class="tio-user-switch"></i> الأدوار والصلاحيات
        </h1>
        <div class="d-flex" style="gap:.5rem;">
            <a href="{{ route('admin.roles.assign') }}" class="btn btn-outline-secondary">
                <i class="tio-users-switch mr-1"></i> إسناد الأدوار
            </a>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-primary">
                <i class="tio-add-circle mr-1"></i> دور جديد
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>الدور</th>
                    <th>المعرّف</th>
                    <th>الصلاحيات</th>
                    <th>المستخدمون</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($roles as $role)
                    <tr>
                        <td>{{ $role->id }}</td>
                        <td>
                            {{ $role->label }}
                            @if($role->is_locked)
                                <span class="badge badge-soft-dark mr-1">دور نظام</span>
                            @endif
                            @if($role->description)
                                <div class="text-muted small">{{ $role->description }}</div>
                            @endif
                        </td>
                        <td><code>{{ $role->name }}</code></td>
                        <td>
                            @if($role->is_locked)
                                <span class="badge badge-soft-success">كل الصلاحيات</span>
                            @else
                                {{ $role->permissions_count }}
                            @endif
                        </td>
                        <td>{{ $role->admins_count }}</td>
                        <td>
                            <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-warning">تعديل</a>

                            @unless($role->is_locked)
                                <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('حذف هذا الدور؟')">حذف</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">لا توجد أدوار</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
