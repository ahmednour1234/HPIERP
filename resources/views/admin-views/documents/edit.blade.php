{{-- resources/views/admin-views/documents/edit.blade.php --}}
@extends('layouts.admin.app')

@section('title', 'تعديل المستند')

@section('content')
<Style>
    .h1, .h2, .h3, .h4, .h5, .h6, .h1, .h2, .h3, .h4, .h5, .h6 {
    color: #fff;
}
</Style>
<div class="container my-5 pt-5" style="padding-right:180px;">
  <div class="row justify-content-center">
    <div class="col-md-8">
      <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
          <h5 class="mb-0"><i class="tio-edit mr-2"></i>تعديل المستند</h5>
        </div>
        <div class="card-body">
          <form action="{{ route('admin.documents.update', $document) }}"
                method="POST"
                enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- الاسم --}}
            <div class="mb-4">
              <label class="form-label font-weight-bold">الاسم</label>
              <input type="text"
                     name="name"
                     value="{{ old('name', $document->name) }}"
                     class="form-control @error('name') is-invalid @enderror"
                     placeholder="أدخل اسم المستند"
                     required>
              @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- الوصف --}}
            <div class="mb-4">
              <label class="form-label font-weight-bold">الوصف</label>
              <textarea name="description"
                        class="form-control @error('description') is-invalid @enderror"
                        rows="3"
                        placeholder="أدخل وصفًا مختصرًا">{{ old('description', $document->description) }}</textarea>
              @error('description')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- المرفقات الحالية --}}
            @if($document->attachments->count())
              <div class="mb-4">
                <label class="form-label font-weight-bold">المرفقات الحالية</label>
                <div class="row g-3">
                  @foreach($document->attachments as $att)
                    <div class="col-4 text-center">
                      <div class="position-relative">
                        @if($att->type === 'image')
                          <img src="{{ $att->url }}"
                               alt="attachment"
                               class="img-fluid rounded cursor-pointer"
                               data-bs-toggle="modal"
                               data-bs-target="#modal-{{ $att->id }}">
                        @else
                          <i class="tio-file-pdf-outlined text-danger"
                             style="font-size:4rem; cursor:pointer;"
                             data-bs-toggle="modal"
                             data-bs-target="#modal-{{ $att->id }}"></i>
                        @endif
                        <div class="form-check position-absolute"
                             style="top:5px; right:5px;">
                          <input type="checkbox"
                                 class="form-check-input"
                                 name="remove_attachments[]"
                                 value="{{ $att->id }}"
                                 id="remove-{{ $att->id }}">
                          <label class="form-check-label text-danger"
                                 for="remove-{{ $att->id }}"
                                 style="font-size:.8rem;">
                            إزالة
                          </label>
                        </div>
                      </div>

                      {{-- Modal --}}
                      <div class="modal fade" id="modal-{{ $att->id }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-lg">
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title">عرض المرفق</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                             @if($att->type == 'image')
  <!-- صورة -->
  <img src="{{ asset($att->url) }}"
       class="img-fluid rounded"
       style="cursor:pointer"
       data-bs-toggle="modal"
       data-bs-target="#modal-{{ $att->id }}">
@elseif($att->type === 'pdf')
  <!-- PDF مضمّن -->
  <div class="border rounded overflow-hidden" style="height:200px; cursor:pointer"
       data-bs-toggle="modal"
       data-bs-target="#modal-{{ $att->id }}">
    <embed src="{{ asset($att->url) }}"
           type="application/pdf"
           width="100%"
           height="100%">
  </div>
@elseif($att->type === 'link')
  <!-- رابط خارجي -->
  <a href="{{ $att->url }}" target="_blank" class="btn btn-sm btn-outline-primary">
    فتح الرابط
  </a>
@endif

                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            @endif

            {{-- رفع ملفات جديدة --}}
            <div class="mb-4">
              <label class="form-label font-weight-bold">رفع ملفات جديدة</label>
              <input type="file"
                     name="attachments[]"
                     class="form-control @error('attachments.*') is-invalid @enderror"
                     multiple>
              @error('attachments.*')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>

            {{-- إضافة روابط جديدة --}}
            <div class="mb-4">
              <label class="form-label font-weight-bold">إضافة روابط جديدة</label>
              <div id="links-wrapper">
                <input type="url"
                       name="links[]"
                       class="form-control mb-2"
                       placeholder="https://example.com">
              </div>
              <button type="button" id="add-link" class="btn btn-outline-secondary btn-sm">
                <i class="tio-add"></i> رابط آخر
              </button>
            </div>

            {{-- الأزرار --}}
            <div class="d-flex justify-content-end">
              <button type="submit" class="btn btn-primary me-2">
                <i class="tio-save"></i> حفظ التعديلات
              </button>
              <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">
                <i class="tio-back-ui"></i> العودة
              </a>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  document.getElementById('add-link').addEventListener('click', function() {
    const wrapper = document.getElementById('links-wrapper');
    const input = document.createElement('input');
    input.type = 'url';
    input.name = 'links[]';
    input.className = 'form-control mb-2';
    input.placeholder = 'https://example.com';
    wrapper.appendChild(input);
  });
</script>
