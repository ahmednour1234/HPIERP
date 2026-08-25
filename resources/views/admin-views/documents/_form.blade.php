{{-- resources/views/admin-views/documents/_form.blade.php --}}
@csrf

<div class="mb-3">
  <label class="form-label">الاسم</label>
  <input type="text"
         name="name"
         value="{{ old('name', $document->name ?? '') }}"
         class="form-control @error('name') is-invalid @enderror"
         required>
  @error('name')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">الوصف</label>
  <textarea name="description"
            class="form-control @error('description') is-invalid @enderror"
            rows="3">{{ old('description', $document->description ?? '') }}</textarea>
  @error('description')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

@if(!empty($document->attachments))
  <div class="mb-3">
    <label class="form-label">المرفقات الحالية</label>
    <div class="form-check">
      @foreach($document->attachments as $att)
        <div class="mb-2">
          <input class="form-check-input"
                 type="checkbox"
                 name="remove_attachments[]"
                 value="{{ $att->id }}"
                 id="att-{{ $att->id }}">
          <label class="form-check-label" for="att-{{ $att->id }}">
            [{{ strtoupper($att->type) }}]
            @if($att->type === 'link')
              <a href="{{ $att->url }}" target="_blank">{{ $att->url }}</a>
            @else
              <a href="{{ $att->url }}" target="_blank">{{ basename($att->url) }}</a>
            @endif
          </label>
        </div>
      @endforeach
    </div>
  </div>
@endif

<div class="mb-3">
  <label class="form-label">رفع ملفات جديدة (PDF/صور)</label>
  <input type="file"
         name="attachments[]"
         class="form-control @error('attachments.*') is-invalid @enderror"
         multiple>
  @error('attachments.*')
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label class="form-label">إضافة روابط جديدة</label>
  <div id="links-wrapper">
    <input type="url"
           name="links[]"
           class="form-control mb-2 @error('links.*') is-invalid @enderror"
           placeholder="https://example.com">
  </div>
  <button type="button" id="add-link" class="btn btn-sm btn-outline-secondary">
    إضافة رابط آخر
  </button>
  @error('links.*')
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>

<script>
  document.getElementById('add-link').addEventListener('click', function() {
    var input = document.createElement('input');
    input.type = 'url';
    input.name = 'links[]';
    input.className = 'form-control mb-2';
    input.placeholder = 'https://example.com';
    document.getElementById('links-wrapper').append(input);
  });
</script>
