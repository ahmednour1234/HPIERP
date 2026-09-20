{{-- resources/views/admin-views/documents/create.blade.php --}}
@extends('layouts.admin.app')

@section('title', \App\CPU\translate('إنشاء مستند'))

@push('css_or_js')
@include('admin-views.documents._form_styles')
@endpush

@section('content')
<div class="content container-fluid">

    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title d-flex align-items-center g-2px">
                <i class="tio-add-circle-outlined"></i>
                <span>{{ \App\CPU\translate('مستند جديد') }}</span>
            </h1>
        </div>
        <div class="col-sm-auto">
            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">
                <i class="tio-back-ui mr-1"></i> {{ \App\CPU\translate('العودة') }}
            </a>
        </div>
    </div>

    <form action="{{ route('admin.documents.store') }}"
          method="POST" enctype="multipart/form-data">
        @csrf

        <div class="card mb-3">
            <div class="card-header">
                <h2 class="h5 mb-0">{{ \App\CPU\translate('بيانات المستند') }}</h2>
            </div>
            <div class="card-body">
                @include('admin-views.documents._fields')
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">
                <h2 class="h5 mb-0">{{ \App\CPU\translate('إضافة مرفقات') }}</h2>
            </div>
            <div class="card-body">
                @include('admin-views.documents._attachments')
            </div>
        </div>

        <div class="d-flex" style="gap:.5rem;">
            <button type="submit" class="btn btn-primary px-4">
                <i class="tio-save mr-1"></i> {{ \App\CPU\translate('إنشاء') }}
            </button>
            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary px-4">
                {{ \App\CPU\translate('إلغاء') }}
            </a>
        </div>
    </form>
</div>
@endsection

@push('script_2')
@include('admin-views.documents._links_script')
@endpush
