@extends('layouts.admin.app')

@section('title', \App\CPU\translate('dashboard'))

@section('content')
    <div class="d-flex justify-content-center align-items-center vh-100 mb-5">
         <img class="navbar-brand"
                         onerror="this.src='{{ asset('public/assets/admin/img/160x160/logo2.png') }}'"
                         src="{{ asset('public/assets/admin/img/160x160/logo2.png') }}" alt="Logo">
                         </div>
@endsection
