{{-- أنماط نموذج المستخدم، مشتركة بين الإضافة والتعديل. --}}
<style>
    .field-label {
        font-size: .8rem;
        font-weight: 700;
        color: var(--hpi-ink);
        margin-bottom: .35rem;
        display: block;
    }

    .field-label .req { color: #b3261e; }

    .roles-wrap .form-control,
    .roles-wrap select {
        border-color: var(--hpi-line);
        border-radius: 10px;
        padding: .55rem .8rem;
        height: auto;
    }

    .roles-wrap .form-control:focus,
    .roles-wrap select:focus {
        border-color: var(--hpi-blue);
        box-shadow: 0 0 0 .18rem rgba(142,197,239,.28);
    }

    .field-hint { font-size: .74rem; color: var(--hpi-muted); margin-top: .3rem; }

    /* ---------- اختيار الدور ---------- */

    .role-picker {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
        gap: .7rem;
    }

    .role-opt {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        padding: .75rem .85rem;
        border: 1px solid var(--hpi-line);
        border-radius: 12px;
        cursor: pointer;
        margin: 0;
        transition: border-color .15s ease, background .15s ease;
    }

    .role-opt:hover { border-color: var(--hpi-blue); background: #fbfdff; }

    /* الاختيار يُقرأ من الحافّة لا من المربّع وحده. */
    .role-opt.is-on { border-color: var(--hpi-navy); background: #f4f9fd; }

    .role-opt input { margin-top: .15rem; width: 15px; height: 15px; accent-color: var(--hpi-navy); }

    .role-opt .r-name { font-size: .86rem; font-weight: 700; color: var(--hpi-ink); }

    .role-opt .r-desc {
        font-size: .74rem;
        color: var(--hpi-muted);
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .role-opt .r-slug {
        font-family: SFMono-Regular, Menlo, Consolas, monospace;
        font-size: .68rem;
        color: var(--hpi-muted);
        direction: ltr;
    }

    .no-roles {
        padding: 1rem;
        border: 1px dashed var(--hpi-line);
        border-radius: 12px;
        color: var(--hpi-muted);
        font-size: .84rem;
        text-align: center;
    }

    .roles-warn {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        margin-top: .8rem;
        padding: .6rem .75rem;
        border-radius: 10px;
        background: #fdf3f2;
        border: 1px solid #f0c9c6;
        color: #8c2019;
        font-size: .8rem;
    }

    .super-note {
        display: flex;
        align-items: flex-start;
        gap: .5rem;
        font-size: .84rem;
        color: #0f5c3c;
        background: #eefaf4;
        border: 1px solid #bfe8d4;
        border-radius: 10px;
        padding: .75rem .85rem;
    }

    /* ---------- شريط الحفظ ---------- */

    .save-bar {
        position: sticky;
        bottom: 0;
        z-index: 5;
        display: flex;
        align-items: center;
        gap: .5rem;
        padding: .85rem 1.2rem;
        background: rgba(255,255,255,.96);
        border: 1px solid var(--hpi-line);
        border-radius: 14px;
        box-shadow: 0 -6px 22px rgba(20,57,92,.08);
    }
</style>
