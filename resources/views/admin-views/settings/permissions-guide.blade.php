@extends('layouts.admin.app')

@section('title', 'دليل الصلاحيات')

@push('css_or_js')
<style>
    .pg { --pg-navy:#11245a; --pg-ink:#1f2d3d; --pg-muted:#6b7a90;
          --pg-line:#e6edf5; --pg-soft:#f6f9fc; }

    .pg-head { margin-bottom:1.1rem; }
    .pg-head h1 {
        display:flex; align-items:center; gap:.55rem; margin:0;
        font-size:1.3rem; font-weight:800; color:var(--pg-navy);
    }
    .pg-head small { color:var(--pg-muted); }

    .pg-card {
        border:1px solid var(--pg-line); border-radius:14px; background:#fff;
        box-shadow:0 10px 26px rgba(15,23,42,.06); margin-bottom:1.1rem; overflow:hidden;
    }
    .pg-card > h2 {
        display:flex; align-items:center; gap:.5rem; margin:0;
        padding:.95rem 1.25rem; font-size:.95rem; font-weight:800;
        color:var(--pg-navy); background:var(--pg-soft);
        border-bottom:1px solid var(--pg-line);
    }
    .pg-body { padding:1.1rem 1.25rem; }

    .pg-note {
        border-inline-start:3px solid #f0ad4e; background:#fffaf0;
        padding:.7rem .9rem; border-radius:8px; margin-bottom:1rem;
        font-size:.86rem; line-height:1.9; color:#5c4813;
    }

    .pg-table { width:100%; border-collapse:collapse; font-size:.85rem; }
    .pg-table th {
        text-align:start; font-size:.76rem; font-weight:700; color:var(--pg-navy);
        padding:.6rem .7rem; background:var(--pg-soft); border-bottom:1px solid var(--pg-line);
        white-space:nowrap;
    }
    .pg-table td {
        padding:.6rem .7rem; border-bottom:1px solid #f1f5f9;
        vertical-align:top; color:var(--pg-ink);
    }
    .pg-table tr:last-child td { border-bottom:0; }

    .pg-chip {
        display:inline-block; background:#eef3fb; color:var(--pg-navy);
        border-radius:6px; padding:.1rem .45rem; margin:.1rem .15rem .1rem 0;
        font-size:.73rem; font-weight:700; font-family:monospace; direction:ltr;
    }
    .pg-chip--warn { background:#fdf0e6; color:#8a4b0f; }
    .pg-chip--ok   { background:#e8f6ef; color:#1b7f5a; }

    .pg-path { font-family:monospace; direction:ltr; font-size:.78rem; color:var(--pg-muted); }

    .pg-steps { margin:0; padding-inline-start:1.2rem; font-size:.86rem; line-height:2; }
    .pg-steps code {
        background:#eef3fb; color:var(--pg-navy); border-radius:4px;
        padding:.05rem .3rem; font-size:.8rem;
    }

    .pg-scroll { overflow-x:auto; }

    @media (max-width: 575.98px) { .pg-body { padding:.9rem; } }
</style>
@endpush

@section('content')
<div class="content container-fluid pg">

    <div class="pg-head">
        <h1><i class="tio-shield"></i> دليل الصلاحيات</h1>
        <small>ما الذي يفتحه كل قسم، ولماذا يُمنع حسابٌ من شاشة يظنّ أنها من حقّه.</small>
    </div>

    {{-- ===== لماذا يُمنع حساب ===== --}}
    <div class="pg-card">
        <h2><i class="tio-help-outlined"></i> حسابٌ يُمنع من شاشة: ماذا تفحص؟</h2>
        <div class="pg-body">
            <ol class="pg-steps">
                <li>
                    <strong>القسم غير الذي تظنّه.</strong> الشاشة تتبع القسم المذكور في
                    الجدول أدناه، لا الاسم الظاهر في القائمة. مثالٌ شائع:
                    «كشف المنتجات المباعة» مساره <span class="pg-path">admin/product/…</span>
                    لكنه من قسم <span class="pg-chip">reports</span> لا
                    <span class="pg-chip">products</span>.
                </li>
                <li>
                    <strong>الصلاحية موجودة في النظام وغير معطاة للدور.</strong> افتح
                    <a href="{{ route('admin.roles.index') }}">الأدوار</a> وراجع دور الحساب.
                </li>
                <li>
                    <strong>الدور مُولَّد من الأعمدة القديمة.</strong> الأدوار المسمّاة
                    <span class="pg-chip">legacy-admin-&lt;id&gt;</span> وُلِّدت من أعمدة
                    الصلاحيات القديمة وقت الترحيل: ما كان العمود فيه صفرًا لم يُمنح.
                    استبدلها بدورٍ جاهز أو أضف القسم الناقص يدويًّا.
                </li>
                <li>
                    <strong>العمود <code>is_super</code>.</strong> من يحمله يتجاوز الأدوار
                    كلّها؛ وغيابه لا يعني نقص صلاحية بل أن الأدوار وحدها هي الحَكَم.
                </li>
            </ol>
        </div>
    </div>

    {{-- ===== المسارات المضلِّلة ===== --}}
    <div class="pg-card">
        <h2><i class="tio-warning-outlined"></i> شاشات اسمها لا يدلّ على قسمها</h2>
        <div class="pg-body">
            <div class="pg-note">
                هذه أكثر مصادر الالتباس: المسار يبدأ ببادئةٍ وقسمُه شيء آخر، فيُمنح
                الحساب صلاحية الاسم الظاهر ويبقى ممنوعًا.
            </div>

            <div class="pg-scroll">
                <table class="pg-table">
                    <thead>
                        <tr><th>الشاشة</th><th>المسار</th><th>القسم المطلوب</th></tr>
                    </thead>
                    <tbody>
                        @foreach([
                            ['الرحلات / أذون الصرف', 'admin/storages/…',               'storages'],
                            ['كشف المنتجات المباعة', 'admin/product/getreportProducts','reports'],
                            ['تقرير الصلاحية',        'admin/product/listreportexpire', 'reports'],
                            ['الفواتير والمرتجعات',   'admin/pos/orders · refunds',     'invoices'],
                            ['حجوزات المناديب',       'admin/pos/reservations',         'requests'],
                            ['الأقساط',               'admin/pos/installments',         'installments'],
                            ['الرواتب',               'admin/admin/salaries',           'salaries'],
                            ['تحويلات المناديب',      'admin/admin/TransactionSeller',  'deposits'],
                            ['خريطة المناديب',        'admin/admin/showmap',            'tracking'],
                            ['تطوير المناديب',        'admin/admin/developsellers',     'hr'],
                        ] as [$screen, $path, $group])
                            <tr>
                                <td>{{ $screen }}</td>
                                <td><span class="pg-path">{{ $path }}</span></td>
                                <td><span class="pg-chip pg-chip--warn">{{ $group }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== الأدوار الجاهزة ===== --}}
    <div class="pg-card">
        <h2><i class="tio-user-outlined"></i> الأدوار الجاهزة</h2>
        <div class="pg-body">
            <div class="pg-scroll">
                <table class="pg-table">
                    <thead>
                        <tr><th>الدور</th><th>الاسم</th><th class="text-center">الصلاحيات</th><th>الأقسام</th></tr>
                    </thead>
                    <tbody>
                    @foreach($roles as $role)
                        @continue(str_starts_with($role->name, 'legacy-admin-'))
                        <tr>
                            <td><span class="pg-chip">{{ $role->name }}</span></td>
                            <td>{{ $role->label }}</td>
                            <td class="text-center">{{ $role->permissions_count }}</td>
                            <td>
                                @php($sections = $role->permissions->pluck('group')->unique()->sort()->values())
                                @forelse($sections as $section)
                                    <span class="pg-chip">{{ $section }}</span>
                                @empty
                                    <span class="text-muted">كل شيء</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== الحسابات ===== --}}
    <div class="pg-card">
        <h2><i class="tio-group-equal"></i> الحسابات وما تملكه</h2>
        <div class="pg-body">
            <div class="pg-scroll">
                <table class="pg-table">
                    <thead>
                        <tr><th>#</th><th>الحساب</th><th>الأدوار</th><th class="text-center">يتجاوز كل شيء</th></tr>
                    </thead>
                    <tbody>
                    @foreach($admins as $admin)
                        <tr>
                            <td>{{ $admin->id }}</td>
                            <td>
                                {{ trim($admin->f_name . ' ' . $admin->l_name) ?: '—' }}
                                <div class="pg-path">{{ $admin->email }}</div>
                            </td>
                            <td>
                                @forelse($admin->roles as $role)
                                    <span class="pg-chip {{ str_starts_with($role->name, 'legacy-admin-') ? 'pg-chip--warn' : '' }}">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-muted">بلا دور</span>
                                @endforelse
                            </td>
                            <td class="text-center">
                                @if($admin->is_super)
                                    <span class="pg-chip pg-chip--ok">is_super</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== كل الأقسام ===== --}}
    <div class="pg-card">
        <h2><i class="tio-dashboard-outlined"></i> الأقسام وإجراءاتها ({{ count($groups) }})</h2>
        <div class="pg-body">
            <div class="pg-scroll">
                <table class="pg-table">
                    <thead>
                        <tr><th>القسم</th><th>الاسم</th><th>الإجراءات</th></tr>
                    </thead>
                    <tbody>
                    @foreach($groups as $key => $group)
                        <tr>
                            <td><span class="pg-chip">{{ $key }}</span></td>
                            <td>{{ $group['label'] }}</td>
                            <td>
                                @foreach($group['actions'] as $action => $label)
                                    <span class="pg-chip">{{ $key }}.{{ $action }}</span>
                                @endforeach
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== الأوامر ===== --}}
    <div class="pg-card">
        <h2><i class="tio-terminal"></i> أوامر الصيانة</h2>
        <div class="pg-body">
            <ol class="pg-steps">
                <li>
                    <code>php artisan db:seed --class=RolesPermissionsSeeder</code>
                    — يضيف الصلاحيات الجديدة ويُنشئ الأدوار الجاهزة. لا يمسّ دورًا
                    عُدِّل بعد إنشائه، فتشغيله مرّتين آمن.
                </li>
                <li>
                    <code>php artisan permissions:super &lt;email&gt;</code>
                    — يمنح <code>is_super</code>. و<code>--revoke</code> يسحبه،
                    و<code>--list</code> يعرض من يحمله.
                </li>
            </ol>
        </div>
    </div>
</div>
@endsection
