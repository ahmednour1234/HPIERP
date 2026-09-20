@extends('layouts.admin.app')

@section('title',\App\CPU\translate('add_new_account'))

@push('css_or_js')

@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
        <div class="mb-3">
            <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize">
                <i class="tio-add-circle-outlined"></i>
                <span>{{\App\CPU\translate('اضافة حساب جديد')}}</span>
            </h1>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.account.store')}}" method="post" >
                            @csrf
                            {{-- شبكة واحدة منتظمة: كانت الحقول موزّعة على
                                 صفَّين بأعمدة 4 و6 و6، فينزل الوصف وحده
                                 ويبقى الصف الأول ناقصًا. --}}
                            <div class="row">
                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label class="input-label" for="ac-storage">
                                            {{ \App\CPU\translate('الخزن') }}
                                            <span class="input-label-secondary text-danger">*</span>
                                        </label>
                                        <select id="ac-storage" name="storage_id" class="form-control" required>
                                            @foreach($storages as $item)
                                                <option value="{{ $item->id }}">{{ $item->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label class="input-label" for="ac-title">
                                            {{ \App\CPU\translate('عنوان الحساب') }}
                                            <span class="input-label-secondary text-danger">*</span>
                                        </label>
                                        <input type="text" id="ac-title" name="account" class="form-control"
                                               value="{{ old('account') }}"
                                               placeholder="{{ \App\CPU\translate('account_title') }}" required>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label class="input-label" for="ac-number">
                                            {{ \App\CPU\translate('رقم الحساب') }}
                                            <span class="input-label-secondary text-danger">*</span>
                                        </label>
                                        <input type="text" id="ac-number" name="account_number" class="form-control"
                                               value="{{ old('account_number') }}"
                                               placeholder="{{ \App\CPU\translate('account_number') }}" required>
                                    </div>
                                </div>

                                <div class="col-12 col-md-6 col-xl-4">
                                    <div class="form-group">
                                        <label class="input-label" for="ac-balance">
                                            {{ \App\CPU\translate('قيمة الحساب') }}
                                            <span class="input-label-secondary text-danger">*</span>
                                        </label>
                                        <input type="number" step="0.01" min="0" id="ac-balance" name="balance"
                                               class="form-control" value="{{ old('balance') }}"
                                               placeholder="{{ \App\CPU\translate('initial_balance') }}" required>
                                        <small class="form-text text-muted">
                                            {{ \App\CPU\translate('الرصيد عند فتح الحساب.') }}
                                        </small>
                                    </div>
                                </div>

                                <div class="col-12 col-xl-8">
                                    <div class="form-group">
                                        <label class="input-label" for="ac-desc">
                                            {{ \App\CPU\translate('وصف الحساب') }}
                                        </label>
                                        <input type="text" id="ac-desc" name="description" class="form-control"
                                               value="{{ old('description') }}"
                                               placeholder="{{ \App\CPU\translate('description') }}">
                                    </div>
                                </div>
                            </div>

                            <hr>
                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('حفظ')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

