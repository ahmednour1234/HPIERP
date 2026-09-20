{{-- resources/views/admin-views/documents/show.blade.php --}}
@extends('layouts.admin.app')

@section('title', \App\CPU\translate('تفاصيل المستند'))

@push('css_or_js')
<style>
    .doc-meta { font-size: .78rem; font-weight: 700; color: #7c8ea1; margin-bottom: .35rem; }

    .doc-value { font-size: .9rem; color: #1c2b3a; }

    .doc-tag {
        display: inline-block;
        font-size: .72rem;
        font-weight: 600;
        border-radius: 99px;
        padding: .12rem .55rem;
        margin: 0 0 .25rem .25rem;
        background: #eaf4fb;
        color: #14395c;
        border: 1px solid #dceaf6;
    }

    .doc-tag.is-all { background: #f1f4f7; color: #56687a; border-color: #e3ecf4; }

    /* ---------- المرفقات ---------- */

    .att-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
        gap: .9rem;
    }

    .att-card {
        border: 1px solid #e3ecf4;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .att-card:hover { border-color: #8ec5ef; box-shadow: 0 10px 22px rgba(20,57,92,.08); }

    /* كل المرفقات بارتفاع واحد: كان ملف PDF يُدرَج بإطار 600px داخل
       بطاقة مصغّرة، فيمدّ الصفحة ويُخفي ما بعده. */
    .att-thumb {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 150px;
        background: #f6fafd;
        cursor: pointer;
        overflow: hidden;
    }

    .att-thumb img { width: 100%; height: 100%; object-fit: cover; }

    .att-thumb .ph {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .3rem;
        color: #7c8ea1;
        font-size: .74rem;
        font-weight: 600;
    }

    .att-thumb .ph i { font-size: 2rem; }

    .att-thumb .ph.is-pdf i { color: #b3261e; }
    .att-thumb .ph.is-link i { color: #14395c; }

    .att-foot {
        display: flex;
        gap: .3rem;
        padding: .5rem .6rem;
        border-top: 1px solid #e3ecf4;
        background: #fbfdff;
    }

    .att-foot .btn { flex: 1; }

    .doc-empty { padding: 2.5rem 1rem; text-align: center; color: #7c8ea1; }

    /* الإطار يظهر في النافذة وحدها، حيث له مساحة فعلية. */
    .att-frame { width: 100%; height: 70vh; border: 0; }
</style>
@endpush

@section('content')
<div class="content container-fluid">

    <div class="row align-items-center mb-3">
        <div class="col-sm mb-2 mb-sm-0">
            <h1 class="page-header-title d-flex align-items-center g-2px">
                <i class="tio-folder"></i>
                <span>{{ $document->name }}</span>
            </h1>
        </div>
        <div class="col-sm-auto d-flex" style="gap:.4rem;">
            <a href="{{ route('admin.documents.edit', $document) }}" class="btn btn-primary">
                <i class="tio-edit mr-1"></i> {{ \App\CPU\translate('تعديل') }}
            </a>
            <a href="{{ route('admin.documents.index') }}" class="btn btn-outline-secondary">
                <i class="tio-back-ui mr-1"></i> {{ \App\CPU\translate('العودة') }}
            </a>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-6 mb-3 mb-md-0">
                    <div class="doc-meta">{{ \App\CPU\translate('الوصف') }}</div>
                    <div class="doc-value">
                        {{ $document->description ?: \App\CPU\translate('لا يوجد وصف.') }}
                    </div>
                </div>

                <div class="col-12 col-md-6">
                    <div class="doc-meta">{{ \App\CPU\translate('المناديب المسند لهم') }}</div>
                    <div>
                        {{-- وثيقة بلا إسناد عامة يراها الجميع، وهي ليست حالة خطأ. --}}
                        @forelse($document->sellers as $seller)
                            <span class="doc-tag">{{ trim($seller->f_name . ' ' . $seller->l_name) }}</span>
                        @empty
                            <span class="doc-tag is-all">{{ \App\CPU\translate('عامة — كل المناديب') }}</span>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2 class="h5 mb-0">
                {{ \App\CPU\translate('المرفقات') }}
                <span class="badge badge-soft-dark ml-1">{{ $document->attachments->count() }}</span>
            </h2>
        </div>

        <div class="card-body">
            @if($document->attachments->isEmpty())
                <div class="doc-empty">
                    <i class="tio-attachment" style="font-size:2rem;opacity:.4"></i>
                    <p class="mt-2 mb-0">{{ \App\CPU\translate('لا توجد مرفقات') }}</p>
                </div>
            @else
                <div class="att-grid">
                    @foreach($document->attachments as $att)
                        @php($src = $att->type === 'link' ? $att->url : asset($att->url))

                        <div class="att-card">
                            @if($att->type === 'image')
                                <div class="att-thumb" data-toggle="modal" data-target="#modal-{{ $att->id }}">
                                    <img src="{{ $src }}" alt="{{ \App\CPU\translate('مرفق') }}" loading="lazy">
                                </div>
                            @elseif($att->type === 'pdf')
                                <div class="att-thumb" data-toggle="modal" data-target="#modal-{{ $att->id }}">
                                    <span class="ph is-pdf">
                                        <i class="tio-file-text"></i> PDF
                                    </span>
                                </div>
                            @else
                                <a class="att-thumb" href="{{ $src }}" target="_blank" rel="noopener">
                                    <span class="ph is-link">
                                        <i class="tio-link"></i> {{ \App\CPU\translate('رابط') }}
                                    </span>
                                </a>
                            @endif

                            <div class="att-foot">
                                @if(in_array($att->type, ['image', 'pdf'], true))
                                    <button type="button" class="btn btn-sm btn-outline-primary"
                                            data-toggle="modal" data-target="#modal-{{ $att->id }}">
                                        <i class="tio-visible-outlined"></i> {{ \App\CPU\translate('عرض') }}
                                    </button>
                                @endif

                                <a href="{{ $src }}" target="_blank" rel="noopener"
                                   class="btn btn-sm btn-outline-secondary">
                                    <i class="tio-download"></i>
                                    {{ $att->type === 'link' ? \App\CPU\translate('فتح') : \App\CPU\translate('تحميل') }}
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- النوافذ خارج الشبكة حتى لا تتأثّر بتحويل البطاقة عند المرور. --}}
    @foreach($document->attachments as $att)
        @if(in_array($att->type, ['image', 'pdf'], true))
            <div class="modal fade" id="modal-{{ $att->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">{{ \App\CPU\translate('عرض المرفق') }}</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body text-center">
                            @if($att->type === 'image')
                                <img src="{{ asset($att->url) }}" class="img-fluid"
                                     alt="{{ \App\CPU\translate('مرفق') }}">
                            @else
                                <iframe src="{{ asset($att->url) }}#toolbar=1" class="att-frame"></iframe>
                            @endif
                        </div>
                        <div class="modal-footer">
                            <a href="{{ asset($att->url) }}" target="_blank" rel="noopener"
                               class="btn btn-sm btn-primary">
                                <i class="tio-download mr-1"></i> {{ \App\CPU\translate('تحميل الملف') }}
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-dismiss="modal">
                                {{ \App\CPU\translate('إغلاق') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
@endsection
