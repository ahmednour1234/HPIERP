@extends('layouts.admin.app')

@section('title',\App\CPU\translate('add_new_admin'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-add-circle-outlined"></i> {{\App\CPU\translate('add_new_admin')}}
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12  mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.admin.store')}}" method="post" id="product_form"
                            enctype="multipart/form-data" >
                            @csrf
                            <div class="row pl-2" >
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('first_name')}} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="f_name" class="form-control" value="{{ old('f_name') }}"  placeholder="{{\App\CPU\translate('first_name')}}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('last_name')}} <span
                                                class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="l_name" class="form-control" value="{{ old('l_name') }}"  placeholder="{{\App\CPU\translate('last_name')}}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row pl-2" >
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('email')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="{{ old('email') }}"  placeholder="{{\App\CPU\translate('Ex_:_ex@example.com')}}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('password')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="password" class="form-control" value="{{ old('password') }}"  placeholder="{{\App\CPU\translate('password')}}" required>
                                    </div>
                                </div>
                                                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('Longtitude')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="longitude" class="form-control" value="{{ old('longitude') }}"  placeholder="{{\App\CPU\translate('longitude')}}">
                                    </div>
                                </div>

                                <div class="col-12 col-sm-6">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('Latitude')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="latitude" class="form-control" value="{{ old('latitude') }}"  placeholder="{{\App\CPU\translate('latitude')}}" >
                                    </div>
                                </div>

                            </div>
                               <div class="col-12 col-sm-6">
            <div class="form-group">
                <label class="input-label">{{ \App\CPU\translate('sellers') }} <span class="input-label-secondary text-danger">*</span></label>
                <select type="text" name="sellers[]" class="form-control" multiple required>
                    <option value="" hidden>-- Choose sellers --</option>
                    @foreach($sellers as $key => $cat)
                    <option @if(isset(old('sellers')[$key]) && old('sellers')[$key] == $cat->id) selected @endif value="{{ $cat->id }}">{{ $cat->email }}</option>
                    @endforeach
                </select>
            </div>
        </div>
                  <label class="input-label">{{ \App\CPU\translate('الصلاحيات') }} <span class="input-label-secondary text-danger">*</span></label>

@php
    $permissions = [
        'supplier' => 'رؤية الموردين',
        'dashboard' => 'رؤية الاحصائيات',
        'pos' => 'رؤية الفواتير',
        'store' => 'رؤية السيارات',
        'cat' => 'رؤية الاقسام',
        'unit' => 'رؤية وحدات القياس',
        'product' => 'رؤية المنتجات',
        'stock_limit' => 'رؤية كشف النواقص',
        'customer' => 'رؤية العملاء',
        'seller' => 'رؤية المناديب',
        'admin' => 'رؤية الادمن',
        'setting' => 'رؤية اعدادات البرنامج',
        'storage' => 'رؤية الخزن',
        'requests' => 'رؤية الطلبات ',
        'notification' => 'رؤية الاشعارات',
        'tracking' => 'رؤية خريطة المناديب',
        'regions' => 'رؤية المناطق',
        'reports' => 'رؤية التقارير',
        'stock' => 'رؤية الرحلات',
        'visit' => 'رؤية قسم الزيارات',
        'rating' => 'رؤية قسم تقييم المناديب',
        'sectionsalary' => 'رؤية قسم المرتبات',
        'accounts' => 'رؤية قسم الحسابات',
        'sales' => 'رؤية قسم انشاء مبيعات'
               ,'production'=>'إنتاج وتصنيع','hr'=>'إدارة الموارد البشرية' ,'attendance'=>'الحضور والأنصراف'];
@endphp

<div class="container">
    @foreach ($permissions as $key => $label)
        @if ($loop->index % 3 === 0)
            <div class="row">
        @endif

        <div class="col-12 col-sm-4">
            <div class="form-group">
                <label class="input-label">{{ $label }}</label>
                <input type="checkbox" name="{{ $key }}" value="1" @if(old($key, $seller->$key ?? 0) == 1) checked @endif>
            </div>
        </div>

        @if ($loop->iteration % 3 === 0 || $loop->last)
            </div>
        @endif
    @endforeach
</div>

            
                            
                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('submit')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
