@extends('layouts.admin.app')

@section('title', 'إسناد الأدوار')

@section('content')
<div class="container my-5 pt-5" style="padding-right:180px;">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-header-title mb-0"><i class="tio-users-switch"></i> إسناد الأدوار</h1>
        <a href="{{ route('admin.roles.index') }}" class="btn btn-outline-secondary">
            <i class="tio-back-ui mr-1"></i> الأدوار
        </a>
    </div>

    <div class="card shadow-sm">
        <table class="table table-hover mb-0 align-middle">
            <thead class="thead-light">
                <tr>
                    <th>المستخدم</th>
                    <th>البريد</th>
                    <th style="min-width:22rem;">الأدوار</th>
                    <th>حفظ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($admins as $admin)
                    <tr>
                        <form action="{{ route('admin.roles.assign.store', $admin) }}" method="POST">
                            @csrf
                            <td>
                                {{ trim($admin->f_name . ' ' . $admin->l_name) }}
                                @if($admin->is_super)
                                    <span class="badge badge-soft-success mr-1">سوبر أدمن</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $admin->email }}</td>
                            <td>
                                @if($admin->is_super)
                                    {{-- is_super يتجاوز الأدوار في الفحص، فإسنادها له بلا أثر. --}}
                                    <span class="text-muted small">يملك كل الصلاحيات بحكم كونه سوبر أدمن</span>
                                @else
                                    <select name="roles[]" class="form-control" multiple
                                            data-placeholder="بدون دور">
                                        @foreach($roles as $role)
                                            <option value="{{ $role->id }}"
                                                @selected($admin->roles->contains('id', $role->id))>
                                                {{ $role->label }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </td>
                            <td>
                                @unless($admin->is_super)
                                    <button type="submit" class="btn btn-sm btn-primary">حفظ</button>
                                @endunless
                            </td>
                        </form>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="alert alert-info mt-3 mb-0">
        المستخدم بلا دور لا يرى شيئًا في اللوحة. أسند له دورًا واحدًا على الأقل.
    </div>
</div>
@endsection
