{{-- resources/views/admin-views/documents/edit.blade.php --}}
@extends('layouts.admin.app')

@section('title', \App\CPU\translate('تعديل المستند'))

@push('css_or_js')
@include('admin-views.documents._form_styles')
@endpush

@section('content')
<div class="content container-fluid">

    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title d-flex align-items-center g-2px">
                <i class="tio-edit"></i>
                <span>{{ \App\CPU\translate('تعديل المستند') }}</span>
            </h1>
        </div>
        <div class="col-sm-auto">
            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">
                <i class="tio-back-ui mr-1"></i> {{ \App\CPU\translate('العودة') }}
            </a>
        </div>
    </div>

    <form action="{{ route('admin.documents.update', $document) }}"
          method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="card mb-3">
            <div class="card-header">
                <h2 class="h5 mb-0">{{ \App\CPU\translate('بيانات المستند') }}</h2>
            </div>

            <div class="card-body">
                @include('admin-views.documents._fields', ['document' => $document])
            </div>
        </div>

        @if($document->attachments->count())
            <div class="card mb-3">
                <div class="card-header">
                    <h2 class="h5 mb-0">
                        {{ \App\CPU\translate('المرفقات الحالية') }}
                        <span class="badge badge-soft-dark ml-1">{{ $document->attachments->count() }}</span>
                    </h2>
                </div>

                <div class="card-body">
                    <div class="att-grid">
                        @foreach($document->attachments as $att)
                            <label class="att-card" for="remove-{{ $att->id }}">
                                <span class="att-thumb">
                                    @if($att->type === 'image')
                                        {{-- src لا url: العمود يحمل صيغتين مختلفتين
                                             حسب الشاشة التي أنشأت المرفق. --}}
                                        <img src="{{ $att->src }}" alt="{{ \App\CPU\translate('مرفق') }}"
                                             loading="lazy">
                                    @elseif($att->type === 'pdf')
                                        <span class="ph is-pdf"><i class="tio-file-text"></i> PDF</span>
                                    @else
                                        <span class="ph is-link"><i class="tio-link"></i> {{ \App\CPU\translate('رابط') }}</span>
                                    @endif
                                </span>

                                {{-- الإزالة اختيار واضح على البطاقة كلها، لا مربّع
                                     صغير عائم فوق زاويتها. --}}
                                <span class="att-remove">
                                    <input type="checkbox" name="remove_attachments[]"
                                           value="{{ $att->id }}" id="remove-{{ $att->id }}">
                                    <span>{{ \App\CPU\translate('إزالة') }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    <small class="form-text text-muted mt-2">
                        {{ \App\CPU\translate('المرفقات المحدَّدة تُحذف عند الحفظ.') }}
                    </small>
                </div>
            </div>
        @endif

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
                <i class="tio-save mr-1"></i> {{ \App\CPU\translate('حفظ التعديلات') }}
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
