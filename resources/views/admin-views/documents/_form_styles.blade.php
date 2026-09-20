{{-- أنماط نموذج المستند، مشتركة بين الإنشاء والتعديل. --}}
<style>
    .att-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
        gap: .8rem;
    }

    .att-card {
        display: block;
        margin: 0;
        border: 1px solid #e3ecf4;
        border-radius: 12px;
        overflow: hidden;
        background: #fff;
        cursor: pointer;
        transition: border-color .15s ease;
    }

    .att-card:hover { border-color: #8ec5ef; }

    /* البطاقة المحدَّدة للحذف تُقرأ من حافّتها لا من مربّع صغير. */
    .att-card:has(input:checked) {
        border-color: #e5484d;
        background: #fdf3f2;
    }

    /* ارتفاع واحد لكل المرفقات مهما اختلف نوعها. */
    .att-thumb {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 130px;
        background: #f6fafd;
        overflow: hidden;
    }

    .att-thumb img { width: 100%; height: 100%; object-fit: cover; }

    .att-thumb .ph {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: .25rem;
        color: #7c8ea1;
        font-size: .74rem;
        font-weight: 600;
    }

    .att-thumb .ph i { font-size: 1.9rem; }
    .att-thumb .ph.is-pdf i  { color: #b3261e; }
    .att-thumb .ph.is-link i { color: #14395c; }

    .att-remove {
        display: flex;
        align-items: center;
        gap: .4rem;
        padding: .5rem .7rem;
        border-top: 1px solid #e3ecf4;
        background: #fbfdff;
        font-size: .78rem;
        font-weight: 600;
        color: #b3261e;
    }

    .att-remove input { width: 15px; height: 15px; accent-color: #e5484d; }

    /* روابط جديدة: صفّ لكل رابط مع زرّ إزالته. */
    .link-row { display: flex; gap: .4rem; margin-bottom: .5rem; }
    .link-row .form-control { flex: 1; }
</style>
