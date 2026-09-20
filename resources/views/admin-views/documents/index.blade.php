{{-- resources/views/admin-views/documents/index.blade.php --}}
@extends('layouts.admin.app')

@section('title', \App\CPU\translate('المستندات'))

@push('css_or_js')
<style>
    /* أسماء المناديب قد تطول، فتلتف داخل خليتها وحدها. */
    .doc-sellers {
        display: flex;
        flex-wrap: wrap;
        gap: .25rem;
        max-width: 24rem;
    }

    .doc-tag {
        font-size: .72rem;
        font-weight: 600;
        border-radius: 99px;
        padding: .12rem .55rem;
        background: #eaf4fb;
        color: #14395c;
        border: 1px solid #dceaf6;
        white-space: nowrap;
    }

    /* «عامة» ليست حالة خطأ، فلا تُلوَّن كتحذير. */
    .doc-tag.is-all {
        background: #f1f4f7;
        color: #56687a;
        border-color: #e3ecf4;
    }

    .doc-count {
        display: inline-block;
        min-width: 1.6rem;
        font-weight: 700;
        text-align: center;
        direction: ltr;
    }

    .doc-actions { display: flex; gap: .3rem; white-space: nowrap; }

    .doc-empty { padding: 3rem 1rem; text-align: center; color: #7c8ea1; }
</style>
@endpush

@section('content')
{{-- كان المحتوى بلا غلاف .content، فيلتصق بحافة النافذة ويُقصّ العنوان
     تحت الشريط العلوي. --}}
<div class="content container-fluid">

    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title d-flex align-items-center g-2px">
                <i class="tio-folder"></i>
                <span>{{ \App\CPU\translate('المستندات') }}</span>
                <span class="badge badge-soft-dark ml-2">{{ $documents->total() }}</span>
            </h1>
        </div>
        <div class="col-sm-auto">
            <a href="{{ route('admin.documents.create') }}" class="btn btn-primary">
                <i class="tio-add-circle mr-1"></i> {{ \App\CPU\translate('إضافة مستند جديد') }}
            </a>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive datatable-custom">
            <table class="table table-borderless table-thead-bordered table-align-middle card-table mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ \App\CPU\translate('الاسم') }}</th>
                        <th>{{ \App\CPU\translate('المناديب') }}</th>
                        <th>{{ \App\CPU\translate('عدد المرفقات') }}</th>
                        <th>{{ \App\CPU\translate('الإجراءات') }}</th>
                    </tr>
                </thead>

                <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>{{ $doc->id }}</td>
                        <td>{{ $doc->name }}</td>

                        <td>
                            {{-- وثيقة بلا إسناد عامة يراها الجميع، وهي ليست حالة خطأ. --}}
                            <div class="doc-sellers">
                                @forelse($doc->sellers as $seller)
                                    <span class="doc-tag">
                                        {{ trim($seller->f_name . ' ' . $seller->l_name) }}
                                    </span>
                                @empty
                                    <span class="doc-tag is-all">{{ \App\CPU\translate('عامة — كل المناديب') }}</span>
                                @endforelse
                            </div>
                        </td>

                        <td><span class="doc-count">{{ $doc->attachments->count() }}</span></td>

                        <td>
                            {{-- ثلاثة أزرار بثلاثة ألوان صارخة: العرض والتعديل
                                 إجراءان عاديان، والحذف وحده هو الخطر. --}}
                            <div class="doc-actions">
                                <a href="{{ route('admin.documents.show', $doc) }}"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="tio-visible-outlined"></i> {{ \App\CPU\translate('عرض') }}
                                </a>

                                <a href="{{ route('admin.documents.edit', $doc) }}"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="tio-edit"></i> {{ \App\CPU\translate('تعديل') }}
                                </a>

                                <form action="{{ route('admin.documents.destroy', $doc) }}"
                                      method="POST" class="d-inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                            onclick="return confirm('حذف مستند «{{ $doc->name }}»؟')">
                                        <i class="tio-delete"></i> {{ \App\CPU\translate('حذف') }}
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            <div class="doc-empty">
                                <i class="tio-folder" style="font-size:2rem;opacity:.4"></i>
                                <p class="mt-2 mb-0">{{ \App\CPU\translate('لا توجد مستندات') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if($documents->hasPages())
            <div class="card-footer d-flex justify-content-center">
                {{ $documents->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
