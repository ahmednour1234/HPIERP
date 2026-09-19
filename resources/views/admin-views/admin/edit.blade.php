@extends('layouts.admin.app')

@section('title',\App\CPU\translate('update_admin'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
    @include('admin-views.roles._tokens')
    @include('admin-views.admin._form_styles')
@endpush

@section('content')
<div class="roles-wrap">

    <div class="roles-hero">
        <div class="hero-text">
            <h1><i class="tio-edit mr-1"></i> {{\App\CPU\translate('update_admin')}}</h1>
            <p>{{ trim($admin->f_name . ' ' . $admin->l_name) }} &middot;
                <span dir="ltr">{{ $admin->email }}</span></p>
        </div>

        <div class="hero-actions">
            <a href="{{ route('admin.admin.list') }}" class="btn btn-ghost">
                <i class="tio-back-ui mr-1"></i> قائمة المستخدمين
            </a>
        </div>
    </div>

    <form action="{{route('admin.admin.update',[$admin->id])}}" method="post" id="product_form"
          enctype="multipart/form-data">
        @csrf

        @include('admin-views.admin._form_fields', [
            'admin'   => $admin,
            'roles'   => $roles,
            'sellers' => $sellers,
        ])

        <div class="save-bar">
            <button type="submit" class="btn btn-primary px-4">
                <i class="tio-save mr-1"></i> {{\App\CPU\translate('update')}}
            </button>
            <a href="{{ route('admin.admin.list') }}" class="btn btn-outline-secondary px-4">إلغاء</a>
        </div>
    </form>
</div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
    @include('admin-views.admin._form_script')
@endpush
