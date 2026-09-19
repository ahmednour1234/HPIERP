@extends('layouts.admin.app')

@section('title', 'إسناد الأدوار')

@push('css_or_js')
@include('admin-views.roles._tokens')
<style>
    .assign-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1rem;
    }

    .assign-card {
        display: flex;
        flex-direction: column;
        background: #fff;
        border: 1px solid var(--hpi-line);
        border-radius: 14px;
        overflow: hidden;
        transition: border-color .15s ease, box-shadow .15s ease;
    }

    .assign-card:hover { border-color: var(--hpi-blue); box-shadow: 0 10px 24px rgba(20,57,92,.08); }

    /* من لا دور له لا يرى شيئًا: حالة صامتة تستحق أن تُرى من نظرة. */
    .assign-card.is-orphan { border-color: #f0c9c6; }
    .assign-card.is-orphan .a-head { background: #fdf3f2; }

    .assign-card.is-super { border-color: #bfe8d4; }
    .assign-card.is-super .a-head { background: #eefaf4; }

    .a-head {
        display: flex;
        align-items: center;
        gap: .7rem;
        padding: .9rem 1.1rem;
        border-bottom: 1px solid var(--hpi-line);
        background: #f9fcfe;
    }

    .a-avatar {
        width: 40px;
        height: 40px;
        flex: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: .9rem;
        font-weight: 800;
        background: var(--hpi-navy);
        color: #fff;
    }

    .assign-card.is-super  .a-avatar { background: #0f7a4d; }
    .assign-card.is-orphan .a-avatar { background: #b3261e; }

    .a-name { font-size: .92rem; font-weight: 700; color: var(--hpi-ink); margin: 0; }

    .a-mail {
        font-size: .76rem;
        color: var(--hpi-muted);
        direction: ltr;
        text-align: start;
        word-break: break-all;
    }

    .a-body { padding: .9rem 1.1rem; flex: 1; }

    .a-label { font-size: .74rem; font-weight: 700; color: var(--hpi-muted); margin-bottom: .4rem; }

    .a-current { display: flex; flex-wrap: wrap; gap: .3rem; margin-bottom: .7rem; }

    .role-chip {
        font-size: .72rem;
        font-weight: 600;
        color: var(--hpi-navy);
        background: #f1f7fc;
        border: 1px solid #dceaf6;
        border-radius: 99px;
        padding: .15rem .55rem;
    }

    .role-chip.is-none {
        background: #fdecec;
        color: #b3261e;
        border-color: #f6d3d1;
    }

    .a-body select {
        width: 100%;
        border: 1px solid var(--hpi-line);
        border-radius: 10px;
        padding: .4rem .6rem;
        font-size: .84rem;
        min-height: 7.5rem;
    }

    .a-body select:focus {
        outline: 0;
        border-color: var(--hpi-blue);
        box-shadow: 0 0 0 .18rem rgba(142,197,239,.28);
    }

    .a-hint { font-size: .72rem; color: var(--hpi-muted); margin-top: .35rem; }

    .a-foot {
        padding: .7rem 1.1rem;
        border-top: 1px solid var(--hpi-line);
        background: #fbfdff;
    }

    .super-note {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        font-size: .8rem;
        color: #0f5c3c;
        background: #eefaf4;
        border: 1px solid #bfe8d4;
        border-radius: 10px;
        padding: .6rem .75rem;
    }

    .assign-search {
        position: relative;
        min-width: 200px;
    }

    .assign-search input {
        width: 100%;
        border: 1px solid rgba(255,255,255,.28);
        background: rgba(255,255,255,.12);
        color: #fff;
        border-radius: 99px;
        padding: .4rem .85rem .4rem 2rem;
        font-size: .84rem;
    }

    .assign-search input::placeholder { color: rgba(255,255,255,.6); }

    .assign-search input:focus { outline: 0; background: rgba(255,255,255,.2); }

    .assign-search i {
        position: absolute;
        inset-inline-start: .7rem;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255,255,255,.7);
        font-size: .9rem;
        pointer-events: none;
    }

    .assign-empty {
        grid-column: 1 / -1;
        padding: 2.5rem 1rem;
        text-align: center;
        color: var(--hpi-muted);
        background: #fff;
        border: 1px dashed var(--hpi-line);
        border-radius: 14px;
    }
</style>
@endpush

@section('content')
<div class="roles-wrap">

    @php($orphans = $admins->filter(fn ($a) => ! $a->is_super && $a->roles->isEmpty())->count())

    <div class="roles-hero">
        <div class="hero-text">
            <h1><i class="tio-users-switch mr-1"></i> إسناد الأدوار</h1>
            <p>
                {{ $admins->count() }} مستخدم
                @if($orphans)
                    &middot; <strong>{{ $orphans }}</strong> بلا دور لن يروا شيئًا
                @endif
            </p>
        </div>

        <div class="hero-actions">
            <div class="assign-search">
                <i class="tio-search"></i>
                <input type="search" id="adminSearch" placeholder="ابحث باسم أو بريد…" autocomplete="off">
            </div>

            <a href="{{ route('admin.roles.index') }}" class="btn btn-ghost">
                <i class="tio-back-ui mr-1"></i> الأدوار
            </a>
        </div>
    </div>

    <div class="assign-grid" id="assignGrid">
        @foreach($admins as $admin)
            @php($fullName = trim($admin->f_name . ' ' . $admin->l_name))
            @php($isOrphan = ! $admin->is_super && $admin->roles->isEmpty())

            <div class="assign-card {{ $admin->is_super ? 'is-super' : ($isOrphan ? 'is-orphan' : '') }}"
                 data-name="{{ $fullName }}" data-mail="{{ $admin->email }}">

                <div class="a-head">
                    <div class="a-avatar">
                        {{ mb_substr($fullName ?: $admin->email, 0, 1) }}
                    </div>
                    <div class="flex-grow-1" style="min-width:0;">
                        <p class="a-name">{{ $fullName ?: '—' }}</p>
                        <div class="a-mail">{{ $admin->email }}</div>
                    </div>
                </div>

                @if($admin->is_super)
                    <div class="a-body">
                        {{-- is_super يتجاوز الأدوار في الفحص، فإسنادها له بلا أثر. --}}
                        <div class="super-note">
                            <i class="tio-shield-outlined"></i>
                            <span>سوبر أدمن — يملك كل الصلاحيات، والأدوار لا تغيّر ذلك.</span>
                        </div>
                    </div>
                @else
                    {{-- النموذج حول البطاقة لا داخل صف جدول: <form> بين <tr>
                         و<td> لا يصحّ، والمتصفح ينقله خارج الجدول فيفقد حقوله. --}}
                    <form action="{{ route('admin.roles.assign.store', $admin) }}" method="POST">
                        @csrf

                        <div class="a-body">
                            <div class="a-label">الأدوار الحالية</div>

                            <div class="a-current">
                                @forelse($admin->roles as $role)
                                    <span class="role-chip">{{ $role->label }}</span>
                                @empty
                                    <span class="role-chip is-none">بلا دور — لا يرى شيئًا</span>
                                @endforelse
                            </div>

                            <div class="a-label">
                                <label for="roles-{{ $admin->id }}" class="mb-0">تعديل الأدوار</label>
                            </div>

                            <select name="roles[]" id="roles-{{ $admin->id }}" multiple>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}"
                                        @selected($admin->roles->contains('id', $role->id))>
                                        {{ $role->label }}
                                    </option>
                                @endforeach
                            </select>

                            <div class="a-hint">اضغط Ctrl لاختيار أكثر من دور.</div>
                        </div>

                        <div class="a-foot">
                            <button type="submit" class="btn btn-sm btn-primary px-3">
                                <i class="tio-save mr-1"></i> حفظ
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        @endforeach

        <div class="assign-empty" id="assignEmpty" hidden>لا مستخدم يطابق البحث.</div>
    </div>
</div>
@endsection

@push('script_2')
<script>
    (function () {
        var search = document.getElementById('adminSearch');
        var empty  = document.getElementById('assignEmpty');

        if (!search) { return; }

        var cards = Array.prototype.slice.call(document.querySelectorAll('.assign-card'));

        search.addEventListener('input', function () {
            var q = search.value.trim().toLowerCase();
            var shown = 0;

            cards.forEach(function (card) {
                var hit = !q
                    || card.dataset.name.toLowerCase().indexOf(q) !== -1
                    || card.dataset.mail.toLowerCase().indexOf(q) !== -1;

                card.hidden = !hit;
                if (hit) { shown++; }
            });

            if (empty) { empty.hidden = shown !== 0; }
        });
    })();
</script>
@endpush
