{{--
    مظهر مشترك لصفحات اللوحة القياسية.

    معظم الشاشات مبنية على البنية نفسها: page-header-title ثم .card ثم
    جدول. تنسيقها واحدة واحدة يعني نسخ اللوحة في كل ملف واختلافها بمرور
    الوقت، فتُجمع هنا مرة واحدة وتنطبق على الجميع.

    لا يُستعمل !important إلا حيث يفرض القالب قيمته، وإلا تعذّر على صفحة
    أن تخالف عند الحاجة.
--}}
<style>
    :root {
        --hpi-navy:      #14395c;
        --hpi-navy-deep: #0d2840;
        --hpi-blue:      #8ec5ef;
        --hpi-ink:       #1c2b3a;
        --hpi-muted:     #7c8ea1;
        --hpi-line:      #e3ecf4;
    }

    /* ---------- عنوان الصفحة ---------- */

    .content > .mb-3 > .page-header-title,
    .content .page-header-title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--hpi-ink);
        gap: .5rem;
    }

    .content .page-header-title i { color: var(--hpi-navy); }

    .content .page-header-title .badge {
        font-size: .72rem;
        font-weight: 700;
        border-radius: 99px;
        padding: .2rem .6rem;
    }

    /* ---------- البطاقات ---------- */

    .content .card {
        border: 1px solid var(--hpi-line);
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(20, 57, 92, .04);
    }

    .content .card-header {
        border-bottom: 1px solid var(--hpi-line);
        background: #f9fcfe;
        border-top-left-radius: 14px;
        border-top-right-radius: 14px;
        padding: .9rem 1.15rem;
    }

    .content .card-footer {
        border-top: 1px solid var(--hpi-line);
        background: #fbfdff;
    }

    /* ---------- الحقول ---------- */

    .content .form-control,
    .content select.form-control,
    .content .custom-select {
        border: 1px solid var(--hpi-line);
        border-radius: 10px;
        font-size: .86rem;
        color: var(--hpi-ink);
    }

    .content .form-control:focus,
    .content select.form-control:focus,
    .content .custom-select:focus {
        border-color: var(--hpi-blue);
        box-shadow: 0 0 0 .18rem rgba(142, 197, 239, .25);
    }

    .content .input-label,
    .content .form-label,
    .content label {
        font-size: .8rem;
        font-weight: 700;
        color: var(--hpi-ink);
    }

    /* البحث المدمج: حوافه مستديرة كوحدة واحدة لا كصندوقين ملتصقين. */
    .content .input-group-merge {
        border: 1px solid var(--hpi-line);
        border-radius: 10px;
        overflow: hidden;
        background: #f6fafd;
    }

    .content .input-group-merge .form-control,
    .content .input-group-merge .input-group-text {
        border: 0;
        background: transparent;
    }

    .content .input-group-merge .btn { border-radius: 0; }

    /* ---------- الأزرار ---------- */

    .content .btn {
        border-radius: 9px;
        font-size: .84rem;
        font-weight: 600;
    }

    .content .btn-primary {
        background-color: var(--hpi-navy);
        border-color: var(--hpi-navy);
    }

    .content .btn-primary:hover,
    .content .btn-primary:focus {
        background-color: var(--hpi-navy-deep);
        border-color: var(--hpi-navy-deep);
    }

    .content .btn-sm { font-size: .78rem; padding: .25rem .6rem; }

    /* ---------- النماذج ---------- */

    .content .form-group { margin-bottom: 1.1rem; }

    .content .form-group > .input-label,
    .content .form-group > label {
        display: block;
        margin-bottom: .4rem;
    }

    /* النجمة علامة إلزام، فتُقرأ كعلامة لا كنصّ بحجم التسمية. */
    .content .input-label-secondary.text-danger {
        font-size: .9em;
        margin-inline-start: .15rem;
    }

    .content .form-control::placeholder { color: #a8b7c6; }

    /* الحقل الرقمي يُقرأ يسارًا حتى في صفحة عربية. */
    .content input[type="number"],
    .content input[type="date"],
    .content input[type="tel"],
    .content input[name*="amount"],
    .content input[name*="balance"],
    .content input[name*="price"] {
        direction: ltr;
        text-align: start;
    }

    /* شريط الحفظ: الفاصل ثم الزرّ، بدل <hr> وزرّ ملتصق بالحافة. */
    .content form > hr:last-of-type {
        margin: 1.4rem -1.25rem 1.1rem;
        border-top: 1px solid var(--hpi-line);
    }

    .content .card-body > form > .btn:last-child { min-width: 7rem; }

    /* ---------- الجداول ---------- */

    .content .table thead th,
    .content .table-thead-bordered thead th {
        background: #f6fafd;
        font-size: .76rem;
        font-weight: 700;
        color: var(--hpi-muted);
        border-top: 0;
        border-bottom: 1px solid var(--hpi-line);
        white-space: nowrap;
        padding: .7rem .75rem;
    }

    .content .table tbody td {
        border-top: 1px solid var(--hpi-line);
        font-size: .85rem;
        color: var(--hpi-ink);
        vertical-align: middle;
        padding: .65rem .75rem;
    }

    .content .table tbody tr:hover { background: #fbfdff; }

    /* الأرقام تُقرأ يسارًا حتى داخل صفحة عربية. */
    .content .table td .price,
    .content .table td .amount { direction: ltr; display: inline-block; }

    /* ---------- الشارات ---------- */

    .content .badge {
        font-size: .72rem;
        font-weight: 700;
        border-radius: 99px;
        padding: .2rem .55rem;
    }

    .content .badge-soft-dark {
        background: #eaf4fb;
        color: var(--hpi-navy);
    }

    /* ---------- الترقيم ---------- */

    .content .page-area,
    .content .pagination { margin-top: .5rem; }

    .content .page-item.active .page-link {
        background-color: var(--hpi-navy);
        border-color: var(--hpi-navy);
    }

    .content .page-link { color: var(--hpi-navy); border-radius: 8px; }
</style>
