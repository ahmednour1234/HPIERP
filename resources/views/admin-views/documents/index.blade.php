{{-- resources/views/admin-views/documents/index.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'المستندات')

@section('content')
<div class="d-flex justify-content-between mb-3">
  <h1>المستندات</h1>
  <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">
    <i class="tio-add-circle"></i> إضافة مستند جديد
  </a>
</div>


<table class="table table-bordered table-hover">
  <thead class="thead-light">
    <tr>
      <th>#</th>
      <th>الاسم</th>
      <th>عدد المرفقات</th>
      <th>الإجراءات</th>
    </tr>
  </thead>
  <tbody>
    @forelse($documents as $doc)
      <tr>
        <td>{{ $doc->id }}</td>
        <td>{{ $doc->name }}</td>
        <td>{{ $doc->attachments->count() }}</td>
        <td>
          <a href="{{ route('admin.documents.show', $doc) }}"
             class="btn btn-sm btn-info">عرض</a>
          <a href="{{ route('admin.documents.edit', $doc) }}"
             class="btn btn-sm btn-warning">تعديل</a>
          <form action="{{ route('admin.documents.destroy', $doc) }}"
                method="POST" class="d-inline">
            @csrf @method('DELETE')
            <button type="submit"
                    class="btn btn-sm btn-danger"
                    onclick="return confirm('هل أنت متأكد من الحذف؟')">
              حذف
            </button>
          </form>
        </td>
      </tr>
    @empty
      <tr>
        <td colspan="4" class="text-center">لا توجد مستندات</td>
      </tr>
    @endforelse
  </tbody>
</table>

<div class="d-flex justify-content-center">
  {{ $documents->links() }}
</div>
@endsection
