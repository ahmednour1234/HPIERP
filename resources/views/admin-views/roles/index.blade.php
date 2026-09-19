@extends('layouts.admin.app')

@section('title', 'الأدوار والصلاحيات')

@push('css_or_js')
<style>
    :root {
        --hpi-navy:      #14395c;
        --hpi-navy-deep: #0d2840;
        --hpi-blue:      #8ec5ef;
        --hpi-ink:       #1c2b3a;
        --hpi-muted:     #7c8ea1;
        --hpi-line:      #e3ecf4;
    }

    .roles-wrap { padding: 1.25rem 1.5rem 2.5rem; }

    /* ---------- الترويسة ---------- */

    .roles-hero {
        position: relative;
        overflow: hidden;
        border-radius: 18px;
        padding: clamp(20px, 2.6vw, 32px);
        margin-bottom: 1.5rem;
        color: #fff;
        background: linear-gradient(135deg, var(--hpi-navy-deep) 0%, var(--hpi-navy) 55%, #1e5280 100%);
        box-shadow: 0 18px 40px rgba(20, 57, 92, .22);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .roles-hero::after {
        content: '';
        position: absolute;
        inset-inline-end: -140px;
        bottom: -220px;
        width: 420px;
        height: 420px;
        border-radius: 50%;
        border: 1.5px solid rgba(142, 197, 239, .20);
        pointer-events: none;
    }

    .roles-hero .hero-text { position: relative; z-index: 1; }

    .roles-hero h1 {
        font-size: clamp(1.25rem, 2vw, 1.7rem);
        font-weight: 800;
        margin: 0 0 .3rem;
        color: #fff;
    }

    .roles-hero p { margin: 0; color: rgba(255,255,255,.76); font-size: .9rem; }

    .roles-hero .hero-actions {
        position: relative;
        z-index: 1;
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .roles-hero .btn-ghost {
        background: rgba(255,255,255,.12);
        border: 1px solid rgba(255,255,255,.28);
        color: #fff;
        font-weight: 600;
    }

    .roles-hero .btn-ghost:hover { background: rgba(255,255,255,.22); color: #fff; }

    .roles-hero .btn-solid {
        background: #fff;
        border: 1px solid #fff;
        color: var(--hpi-navy);
        font-weight: 700;
    }

    .roles-hero .btn-solid:hover { background: #eaf4fb; color: var(--hpi-navy-deep); }

    /* ---------- البطاقات ---------- */

    .role-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1rem;
    }

    .role-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid var(--hpi-line);
        border-radius: 14px;
        overflow: hidden;
        transition: transform .15s ease, box-shadow .15s ease, border-color .15s ease;
    }

    .role-card:hover {
        transform: translateY(-2px);
        border-color: var(--hpi-blue);
        box-shadow: 0 12px 26px rgba(20,57,92,.10);
    }

    /* شريط علوي يميّز دور النظام عن الأدوار المولَّدة دون قراءة نص. */
    .role-card .accent { height: 3px; background: var(--hpi-blue); }
    .role-card.is-system .accent { background: linear-gradient(90deg, #0f7a4d, #4cc38a); }
    .role-card.is-legacy .accent { background: #d8e3ed; }

    .role-card .body { padding: 1.1rem 1.2rem; flex: 1; }

    .role-head { display: flex; align-items: flex-start; gap: .75rem; margin-bottom: .55rem; }

    .role-avatar {
        width: 42px;
        height: 42px;
        flex: none;
        border-radius: 11px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        background: #eaf4fb;
        color: var(--hpi-navy);
    }

    .role-card.is-system .role-avatar { background: #e6f7ef; color: #0f7a4d; }
    .role-card.is-legacy .role-avatar { background: #f3f6f9; color: var(--hpi-muted); }

    .role-title { font-size: 1rem; font-weight: 700; color: var(--hpi-ink); margin: 0 0 .15rem; }

    .role-slug {
        display: inline-block;
        font-family: SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .72rem;
        color: var(--hpi-muted);
        background: #f6fafd;
        border: 1px solid var(--hpi-line);
        border-radius: 6px;
        padding: .05rem .4rem;
        direction: ltr;
    }

    .role-desc {
        font-size: .82rem;
        color: var(--hpi-muted);
        line-height: 1.6;
        margin: 0 0 .85rem;
        /* الوصف سطران على الأكثر، وإلا تفاوتت ارتفاعات البطاقات. */
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        min-height: 2.6em;
    }

    .role-meta { display: flex; gap: 1.25rem; margin-bottom: .85rem; }

    .meta-label { font-size: .72rem; color: var(--hpi-muted); margin-bottom: .1rem; }

    .meta-value {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--hpi-ink);
        line-height: 1.2;
        direction: ltr;
        text-align: start;
    }

    .meta-value small { font-size: .72rem; font-weight: 600; color: var(--hpi-muted); }

    /* شريط تغطية: نسبة ما يملكه الدور من الصلاحيات كلها. */
    .cover-bar {
        height: 5px;
        border-radius: 99px;
        background: #eef4f9;
        overflow: hidden;
        margin-bottom: .85rem;
    }

    .cover-bar span {
        display: block;
        height: 100%;
        border-radius: 99px;
        background: linear-gradient(90deg, var(--hpi-navy), var(--hpi-blue));
    }

    .role-card.is-system .cover-bar span { background: linear-gradient(90deg, #0f7a4d, #4cc38a); }

    .chips { display: flex; flex-wrap: wrap; gap: .3rem; }

    .chip {
        font-size: .72rem;
        font-weight: 600;
        color: var(--hpi-navy);
        background: #f1f7fc;
        border: 1px solid #dceaf6;
        border-radius: 99px;
        padding: .15rem .55rem;
    }

    .chip.is-more { background: #fff; color: var(--hpi-muted); border-style: dashed; }
    .chip.is-all  { background: #e6f7ef; color: #0f7a4d; border-color: #bfe8d4; }
    .chip.is-none { background: #fdecec; color: #b3261e; border-color: #f6d3d1; }

    .role-card .foot {
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .7rem 1.2rem;
        border-top: 1px solid var(--hpi-line);
        background: #fbfdff;
    }

    .badge-system {
        font-size: .7rem;
        font-weight: 700;
        color: #0f7a4d;
        background: #e6f7ef;
        border-radius: 99px;
        padding: .15rem .55rem;
    }

    .roles-empty {
        grid-column: 1 / -1;
        padding: 3rem 1rem;
        text-align: center;
        color: var(--hpi-muted);
        background: #fff;
        border: 1px dashed var(--hpi-line);
        border-radius: 14px;
    }

    @media (max-width: 575.98px) {
        .roles-wrap { padding: 1rem .9rem 2rem; }
        .roles-hero .hero-actions { width: 100%; }
        .roles-hero .hero-actions .btn { flex: 1; }
    }
</style>
@endpush

@section('content')
<div class="roles-wrap">

    <div class="roles-hero">
        <div class="hero-text">
            <h1><i class="tio-user-switch mr-1"></i> الأدوار والصلاحيات</h1>
            <p>
                {{ $roles->count() }} دور &middot;
                {{ $total_perms }} صلاحية موزّعة على {{ $total_groups }} قسم
            </p>
        </div>

        <div class="hero-actions">
            <a href="{{ route('admin.roles.assign') }}" class="btn btn-ghost">
                <i class="tio-users-switch mr-1"></i> إسناد الأدوار
            </a>
            <a href="{{ route('admin.roles.create') }}" class="btn btn-solid">
                <i class="tio-add-circle mr-1"></i> دور جديد
            </a>
        </div>
    </div>

    <div class="role-grid">
        @forelse($roles as $role)
            @php
                $isLegacy = str_starts_with($role->name, 'legacy-admin-');
                $count    = $role->is_locked ? $total_perms : $role->permissions_count;
                $percent  = $total_perms ? round($count / $total_perms * 100) : 0;
            @endphp

            <div class="role-card {{ $role->is_locked ? 'is-system' : ($isLegacy ? 'is-legacy' : '') }}">
                <div class="accent"></div>

                <div class="body">
                    <div class="role-head">
                        <div class="role-avatar">
                            <i class="tio-{{ $role->is_locked ? 'shield-outlined' : ($isLegacy ? 'history' : 'user-switch') }}"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h2 class="role-title">{{ $role->label }}</h2>
                            <span class="role-slug">{{ $role->name }}</span>
                        </div>
                    </div>

                    <p class="role-desc">{{ $role->description ?: 'بلا وصف.' }}</p>

                    <div class="role-meta">
                        <div>
                            <div class="meta-label">الصلاحيات</div>
                            <div class="meta-value">
                                {{ $count }} <small>/ {{ $total_perms }}</small>
                            </div>
                        </div>
                        <div>
                            <div class="meta-label">المستخدمون</div>
                            <div class="meta-value">{{ $role->admins_count }}</div>
                        </div>
                    </div>

                    <div class="cover-bar" title="{{ $percent }}٪ من الصلاحيات">
                        <span style="width: {{ max($percent, $count ? 3 : 0) }}%"></span>
                    </div>

                    {{-- الأقسام المغطّاة: العدد وحده لا يقول أي أبواب يفتحها الدور. --}}
                    <div class="chips">
                        @if($role->is_locked)
                            <span class="chip is-all">كل الأقسام</span>
                        @elseif($role->section_labels->isEmpty())
                            <span class="chip is-none">بلا صلاحيات</span>
                        @else
                            @foreach($role->section_labels->take(5) as $label)
                                <span class="chip">{{ $label }}</span>
                            @endforeach

                            @if($role->section_labels->count() > 5)
                                <span class="chip is-more">+{{ $role->section_labels->count() - 5 }}</span>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="foot">
                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">
                        <i class="tio-edit mr-1"></i> تعديل
                    </a>

                    @unless($role->is_locked)
                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    onclick="return confirm('حذف دور «{{ $role->label }}»؟')">
                                <i class="tio-delete mr-1"></i> حذف
                            </button>
                        </form>
                    @else
                        <span class="badge-system mr-auto">دور نظام — لا يُحذف</span>
                    @endunless
                </div>
            </div>
        @empty
            <div class="roles-empty">
                <i class="tio-user-switch" style="font-size:2rem;opacity:.4"></i>
                <p class="mt-2 mb-3">لا توجد أدوار بعد.</p>
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm">إنشاء أول دور</a>
            </div>
        @endforelse
    </div>
</div>
@endsection
