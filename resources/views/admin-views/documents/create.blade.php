{{-- resources/views/admin-views/documents/create.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'إنشاء مستند')

@section('content')
<div class="card">
  <div class="card-header">
    <h5>مستند جديد</h5>
  </div>
  <div class="card-body">
    <form action="{{ route('admin.documents.store') }}"
          method="POST"
          enctype="multipart/form-data">
      @include('admin-views.documents._form')
      <button type="submit" class="btn btn-success">
        إنشاء
      </button>
      <a href="{{ route('admin.documents.index') }}" class="btn btn-secondary">
        إلغاء
      </a>
    </form>
  </div>
</div>
@endsection
