{{-- رفع الملفات وإضافة الروابط: مشتركة بين الإنشاء والتعديل. --}}
<div class="row">
    <div class="col-12 col-md-6">
        <div class="form-group">
            <label class="input-label" for="doc-files">{{ \App\CPU\translate('رفع ملفات جديدة') }}</label>
            <input type="file" id="doc-files" name="attachments[]"
                   class="form-control @error('attachments.*') is-invalid @enderror" multiple>
            <small class="form-text text-muted">{{ \App\CPU\translate('صور أو ملفات PDF.') }}</small>
            @error('attachments.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="form-group mb-0">
            <label class="input-label">{{ \App\CPU\translate('إضافة روابط جديدة') }}</label>

            <div id="links-wrapper">
                <div class="link-row">
                    <input type="url" name="links[]" class="form-control"
                           placeholder="https://example.com">
                </div>
            </div>

            <button type="button" id="add-link" class="btn btn-sm btn-outline-secondary">
                <i class="tio-add"></i> {{ \App\CPU\translate('رابط آخر') }}
            </button>
        </div>
    </div>
</div>
