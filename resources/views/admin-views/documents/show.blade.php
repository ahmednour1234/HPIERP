{{-- resources/views/admin-views/documents/show.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'تفاصيل المستند')

@section('content')
<style>
   h5{
    color: #fff;
}
</style>
<div class="container my-5">
  <div class="card shadow-sm">
    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
      <h5 class="mb-0">{{ $document->name }}</h5>
      <div>
        <a href="{{ route('admin.documents.edit', $document) }}" 
           class="btn btn-sm btn-warning me-2">
          <i class="tio-edit"></i> تعديل
        </a>
        <a href="{{ route('admin.documents.index') }}" 
           class="btn btn-sm btn-light">
          <i class="tio-back-ui"></i> العودة
        </a>
      </div>
    </div>
    <div class="card-body">
      {{-- الوصف --}}
      <div class="mb-4">
        <h6 class="fw-semibold">الوصف:</h6>
        <p class="text-muted">{{ $document->description ?: 'لا يوجد وصف.' }}</p>
      </div>

      {{-- المناديب المسند لهم --}}
      <div class="mb-4">
        <h6 class="fw-semibold">المناديب المسند لهم:</h6>
        @if($document->sellers->isEmpty())
          <p class="text-muted mb-0">وثيقة عامة — يراها كل المناديب.</p>
        @else
          @foreach($document->sellers as $seller)
            <span class="badge bg-secondary">{{ trim($seller->f_name . ' ' . $seller->l_name) }}</span>
          @endforeach
        @endif
      </div>

      {{-- المرفقات --}}
<div>
  <h6 class="fw-semibold mb-3">المرفقات:</h6>

  @if($document->attachments->isEmpty())
    <p class="text-center text-muted mb-0">لا توجد مرفقات</p>
  @else
    <div class="row g-3">
      @foreach($document->attachments as $att)
        <div class="col-6 col-md-4 col-lg-3 text-center">
          <div class="position-relative border rounded p-2 bg-white">
            {{-- Thumbnail --}}
            @if($att->type === 'image')
              <img src="{{ asset($att->url) }}"
                   class="img-fluid rounded cursor-pointer"
                   style="max-height:150px;"
                   data-bs-toggle="modal"
                   data-bs-target="#modal-{{ $att->id }}">
            @elseif($att->type == 'pdf')
                 <iframe src="{{ asset($att->url) }}#toolbar=1"
                            width="100%" height="600px" frameborder="0">
                    </iframe>
                    <a href="{{ asset($att->url) }}" target="_blank"
                       class="btn btn-sm btn-primary mt-3">
                      تحميل الملف
                    </a>
            @else {{-- link --}}
              <div class="d-flex align-items-center justify-content-center"
                   style="height:150px;">
                <a href="{{ $att->url }}" target="_blank"
                   class="btn btn-outline-primary">
                  فتح الرابط
                </a>
              </div>
            @endif
          </div>
        </div>

        {{-- Modal --}}
        @if(in_array($att->type, ['image', 'pdf']))
          <div class="modal fade" id="modal-{{ $att->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-xl">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">عرض المرفق</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                  @if($att->type == 'image')
                    <img src="{{ asset($att->url) }}" class="img-fluid">
                  @elseif($att->type == 'pdf')
                    <iframe src="{{ asset($att->url) }}#toolbar=1"
                            width="100%" height="600px" frameborder="0">
                    </iframe>
                    <a href="{{ asset($att->url) }}" target="_blank"
                       class="btn btn-sm btn-primary mt-3">
                      تحميل الملف
                    </a>
                  @endif
                </div>
              </div>
            </div>
          </div>
        @endif
      @endforeach
    </div>
  @endif
</div>
    </div>
  </div>
</div>
@endsection
