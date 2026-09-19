{{--
    ألوان ومسافات مشتركة بين صفحات الأدوار الثلاث.

    كانت مكرّرة في كل ملف، فتعديل لون واحد كان يتطلب ثلاث تعديلات
    وينتهي باختلاف بينها.
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

    /* ---------- لوحة ---------- */

    .roles-panel {
        background: #fff;
        border: 1px solid var(--hpi-line);
        border-radius: 14px;
        overflow: hidden;
        margin-bottom: 1.25rem;
    }

    .roles-panel > .head {
        padding: .9rem 1.2rem;
        border-bottom: 1px solid var(--hpi-line);
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: .75rem;
    }

    .roles-panel > .head h2 {
        font-size: .98rem;
        font-weight: 700;
        margin: 0;
        color: var(--hpi-navy);
    }

    .roles-panel > .body { padding: 1.2rem; }

    @media (max-width: 575.98px) {
        .roles-wrap { padding: 1rem .9rem 2rem; }
        .roles-hero .hero-actions { width: 100%; }
        .roles-hero .hero-actions .btn { flex: 1; }
    }
</style>
