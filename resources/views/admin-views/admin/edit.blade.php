@extends('layouts.admin.app')

@section('title',\App\CPU\translate('update_admin'))

@push('css_or_js')
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
@endpush

@section('content')
<div class="content container-fluid">
        <!-- Page Header -->
    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title text-capitalize"><i
                    class="tio-edit"></i> {{\App\CPU\translate('update_admin')}}
            </h1>
        </div>
    </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <div class="card">
                    <div class="card-body">
                        <form action="{{route('admin.admin.update',[$admin->id])}}" method="post" id="product_form"
                            enctype="multipart/form-data"  >
                            @csrf
                            <div class="row pl-2" >
                                <div class="col-12 col-sm-4">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('first_name')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="f_name" class="form-control" value="{{ $admin->f_name }}"  placeholder="{{\App\CPU\translate('first_name')}}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('last_name')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="l_name" class="form-control" value="{{ $admin->l_name }}"  placeholder="{{\App\CPU\translate('last_name')}}" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row pl-2" >
                                <div class="col-12 col-sm-4">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('email')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control" value="{{ $admin->email }}"  placeholder="{{\App\CPU\translate('Ex_:_ex@example.com')}}" required>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-4">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('password')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="password" class="form-control"  placeholder="{{\App\CPU\translate('password')}}">
                                    </div>
                                </div>
                                                                <div class="col-12 col-sm-4">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('longitude')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="longitude" class="form-control"  placeholder="{{\App\CPU\translate('longitude')}}">
                                    </div>
                                </div>

                                <div class="col-12 col-sm-4">
                                    <div class="form-group">
                                        <label class="input-label" >{{\App\CPU\translate('latitude')}} <span
                                            class="input-label-secondary text-danger">*</span></label>
                                        <input type="text" name="latitude" class="form-control"  placeholder="{{\App\CPU\translate('latitude')}}">
                                    </div>
                                </div>

                            </div>
                            <div class="col-12 col-sm-6">
            <div class="form-group">
                <label class="input-label">{{ \App\CPU\translate('sellers') }} <span class="input-label-secondary text-danger">*</span></label>
                <select name="sellers[]" class="form-control" multiple required>
                    <option value="" hidden>-- Choose sellers --</option>
                    @foreach($sellers as $cat)
<option value="{{ $cat->id }}" 
    @if($admin->sellers && $admin->sellers->contains('seller_id', $cat->id)) 
        selected 
    @endif>
    {{ $cat->email }}
</option>
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
        'sales'=>'إدارة المبيعات',
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
        'install'=>'التحصيلات'
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
                <input type="checkbox" name="{{ $key }}" value="1" @if(old($key, $admin->$key ?? 0) == 1) checked @endif>
            </div>
        </div>

        @if ($loop->iteration % 3 === 0 || $loop->last)
            </div>
        @endif
    @endforeach
</div>

                            <button type="submit" class="btn btn-primary">{{\App\CPU\translate('update')}}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection