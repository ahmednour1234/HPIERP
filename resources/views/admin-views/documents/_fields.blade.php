{{-- الاسم والوصف والمناديب: مشتركة بين الإنشاء والتعديل. --}}
<div class="row">
    <div class="col-12 col-md-6">
        <div class="form-group">
            <label class="input-label" for="doc-name">
                {{ \App\CPU\translate('الاسم') }}
                <span class="input-label-secondary text-danger">*</span>
            </label>
            <input type="text" id="doc-name" name="name"
                   value="{{ old('name', $document->name ?? '') }}"
                   class="form-control @error('name') is-invalid @enderror"
                   placeholder="{{ \App\CPU\translate('أدخل اسم المستند') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="form-group">
            <label class="input-label" for="doc-desc">{{ \App\CPU\translate('الوصف') }}</label>
            <input type="text" id="doc-desc" name="description"
                   value="{{ old('description', $document->description ?? '') }}"
                   class="form-control @error('description') is-invalid @enderror"
                   placeholder="{{ \App\CPU\translate('أدخل وصفًا مختصرًا') }}">
            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
    </div>
</div>

@include('admin-views.documents._sellers')
