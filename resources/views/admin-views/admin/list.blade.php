@extends('layouts.admin.app')

@section('title',\App\CPU\translate('admin_list'))

@push('css_or_js')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="{{asset('public/assets/admin')}}/css/custom.css"/>
    <style>
        .role-pill {
            display: inline-block;
            font-size: .72rem;
            font-weight: 600;
            color: #14395c;
            background: #f1f7fc;
            border: 1px solid #dceaf6;
            border-radius: 99px;
            padding: .1rem .55rem;
            margin: .1rem .1rem 0 0;
        }

        /* بلا دور = لا يرى شيئًا، وهي حالة صامتة تستحق أن تُرى. */
        .role-pill.is-none  { background: #fdecec; color: #b3261e; border-color: #f6d3d1; }
        .role-pill.is-super { background: #e6f7ef; color: #0f7a4d; border-color: #bfe8d4; }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="row align-items-center mb-3">
            <div class="col-sm mb-2 mb-sm-0">
                <h1 class="page-header-title d-flex align-items-center g-2px text-capitalize"><i
                        class="tio-filter-list"></i> {{\App\CPU\translate('admin_list')}}
                    <span class="badge badge-soft-dark ml-2">{{$admins->total()}}</span>
                </h1>
            </div>
        </div>
        <!-- End Page Header -->
        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <!-- Card -->
                <div class="card">
                    <!-- Header -->
                    <div class="card-header">
                        <div class="row justify-content-between align-items-center flex-grow-1">
                            <div class="col-12 col-sm-12">
                                <a href="{{route('admin.admin.add')}}" class="btn btn-primary float-right"><i
                                        class="tio-add-circle"></i> {{\App\CPU\translate('add_new_admin')}}
                                </a>
                            </div>
                        </div>
                    </div>
                    <!-- End Header -->

                    <!-- Table -->
                    <div class="table-responsive datatable-custom">
                        <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                            <thead class="thead-light">
                            <tr>
                                <th>{{\App\CPU\translate('#')}}</th>
                                <th>{{\App\CPU\translate('name')}}</th>
                                <th>{{\App\CPU\translate('email')}}</th>
                                <th>الأدوار</th>
                                <th>{{\App\CPU\translate('action')}}</th>
                            </tr>
                            </thead>

                            <tbody id="set-rows">
                            @foreach($admins as $key=>$admin)
                                <tr>
                                    <td>{{ $key+1 }}</td>
                                    <td>
                                        {{ $admin->f_name . ' ' . $admin->l_name }}
                                    </td>
                                    <td>
                                        {{ $admin->email }}
                                    </td>
                                    <td>
                                        {{-- الدور هو ما يحدّد ما يراه المستخدم، وهو أولى
                                             بعمود من إحداثيات لا تُقرأ هنا. --}}
                                        @if($admin->is_super)
                                            <span class="role-pill is-super">سوبر أدمن</span>
                                        @else
                                            @forelse($admin->roles as $role)
                                                <span class="role-pill">{{ $role->label }}</span>
                                            @empty
                                                <span class="role-pill is-none">بلا دور</span>
                                            @endforelse
                                        @endif
                                    </td>
                                    <td>
                                        {{-- <a class="btn btn-white mr-1" href="{{route('admin.admin.view',[$admin['id']])}}"><span class="tio-visible"></span></a> --}}
                                        <a class="btn btn-white mr-1"
                                            href="{{route('admin.admin.edit',[$admin['id']])}}">
                                            <span class="tio-edit"></span>
                                        </a>
                                        <a class="btn btn-white mr-1" href="javascript:"
                                            onclick="form_alert('admin-{{$admin['id']}}','Want to delete this admin?')"><span class="tio-delete"></span>
                                        </a>
                                        <form action="{{route('admin.admin.delete',[$admin['id']])}}"
                                                method="post" id="admin-{{$admin['id']}}">
                                            @csrf @method('delete')
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>

                        <div class="page-area">
                            <table>
                                <tfoot class="border-top">
                                {!! $admins->links() !!}
                                </tfoot>
                            </table>
                        </div>
                        @if(count($admins)==0)
                            <div class="text-center p-4">
                                <img class="mb-3 w-one-cl" src="{{asset('public/assets/admin')}}/svg/illustrations/sorry.svg" alt="{{\App\CPU\translate('Image Description')}}">
                                <p class="mb-0">{{ \App\CPU\translate('No_data_to_show')}}</p>
                            </div>
                        @endif
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
@endsection

@push('script_2')
    <script src={{asset("public/assets/admin/js/global.js")}}></script>
@endpush
