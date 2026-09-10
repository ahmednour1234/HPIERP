{{-- resources/views/admin-views/documents/_sellers.blade.php --}}
{{-- مشترك بين شاشتي الإنشاء والتعديل؛ edit.blade.php لا يستخدم _form. --}}

@php
  $assigned = collect(old('sellers', isset($document) ? $document->sellers->pluck('id')->all() : []))
      ->map(fn ($v) => (int) $v)->all();
@endphp

<div class="mb-4">
  <label class="form-label font-weight-bold">المناديب المسند لهم</label>

  <select name="sellers[]"
          class="form-control @error('sellers.*') is-invalid @enderror"
          multiple
          size="8">
    @foreach($sellers as $seller)
      <option value="{{ $seller->id }}" @selected(in_array((int) $seller->id, $assigned, true))>
        {{ trim($seller->f_name . ' ' . $seller->l_name) }}
      </option>
    @endforeach
  </select>

  <small class="form-text text-muted">
    اختر مندوبًا أو أكثر (Ctrl للتحديد المتعدد). اتركه فارغًا لتكون الوثيقة عامة يراها كل المناديب.
  </small>
  @error('sellers.*')
    <div class="invalid-feedback d-block">{{ $message }}</div>
  @enderror
</div>
