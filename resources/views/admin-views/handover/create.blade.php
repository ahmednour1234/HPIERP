@extends('layouts.admin.app')

@section('title', 'تسليم جديد')

@push('css_or_js')
    @include('layouts.admin.partials._page_polish')
@endpush

@section('content')
<div class="content container-fluid">

    <div class="mb-4">
        <h2 class="h4 mb-1">تسليم عهدة مندوب</h2>
        <small class="text-muted">
            التسجيل وحده لا ينقل شيئًا: الترحيل والجرد خطوتان تاليتان.
        </small>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.handover.store') }}">
                @csrf

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label class="input-label">صاحب العهدة (من انتهت خدمته)</label>
                        <select name="from_seller_id" class="form-control" required>
                            <option value="">— اختر —</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected(old('from_seller_id') == $seller->id)>
                                    {{ trim($seller->f_name . ' ' . $seller->l_name) ?: $seller->email }}
                                    @if($seller->mandob_code) ({{ $seller->mandob_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('from_seller_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>

                    <div class="form-group col-md-6">
                        <label class="input-label">المستلم</label>
                        <select name="to_seller_id" class="form-control" required>
                            <option value="">— اختر —</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}" @selected(old('to_seller_id') == $seller->id)>
                                    {{ trim($seller->f_name . ' ' . $seller->l_name) ?: $seller->email }}
                                    @if($seller->mandob_code) ({{ $seller->mandob_code }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('to_seller_id')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label class="input-label">تاريخ البدء</label>
                        <input type="date" name="started_at" class="form-control"
                               value="{{ old('started_at', now()->format('Y-m-d')) }}">
                    </div>

                    <div class="form-group col-md-8">
                        <label class="input-label">ملاحظة</label>
                        <input type="text" name="note" class="form-control"
                               maxlength="2000" value="{{ old('note') }}">
                    </div>
                </div>

                <div class="d-flex justify-content-end" style="gap:.5rem;">
                    <a href="{{ route('admin.handover.index') }}" class="btn btn-secondary">إلغاء</a>
                    <button type="submit" class="btn btn-primary">تسجيل التسليم</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
