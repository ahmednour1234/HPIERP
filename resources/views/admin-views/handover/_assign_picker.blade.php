{{--
    اختيار العملاء والمناطق والأقسام المنقولة. يستعمله تسليم العهدة
    وشاشة النقل المستقلة؛ النموذج المحيط عند كلٍّ منهما.

    $assign   : ناتج SellerAssignmentTransferService::listFor
    $fromName : اسم المنقول منه
    $toName   : اسم المنقول إليه
--}}
<div class="as-grid">
    @foreach(['customers' => 'العملاء', 'regions' => 'المناطق', 'categories' => 'الأقسام'] as $key => $title)
        <div class="as-col" data-group="{{ $key }}">
            <div class="as-head">
                <strong>{{ $title }}</strong>
                <span class="badge badge-soft-secondary">{{ $assign[$key]->count() }}</span>
                @if($assign[$key]->isNotEmpty())
                    <label class="as-all mb-0">
                        <input type="checkbox" class="as-check-all"> الكل
                    </label>
                @endif
            </div>

            @if($assign[$key]->isEmpty())
                <p class="text-muted small mb-0">لا يوجد.</p>
            @else
                @if($assign[$key]->count() > 8)
                    <input type="search" class="form-control form-control-sm mb-2 as-filter" placeholder="بحث…">
                @endif
                <div class="as-list">
                    @foreach($assign[$key] as $item)
                        <label class="as-item">
                            <input type="checkbox" name="{{ $key }}[]" value="{{ $item['id'] }}" class="as-check">
                            <span>{{ $item['name'] }}</span>
                            @if($item['to_has'])
                                <small class="text-success">مع {{ $toName }}</small>
                            @endif
                        </label>
                    @endforeach
                </div>
            @endif
        </div>
    @endforeach
</div>

<div class="d-flex justify-content-between align-items-center flex-wrap mt-3" style="gap:.75rem;">
    <label class="mb-0 d-inline-flex align-items-center" style="gap:.5rem; cursor:pointer;">
        <input type="checkbox" name="remove" value="1" checked>
        إزالة المنقول من {{ $fromName }} (نقل لا نسخ)
    </label>
    <button type="submit" class="btn btn-primary">نقل المحدَّد إلى {{ $toName }}</button>
</div>

@once
    @push('script_2')
    <style>
        .as-grid { display: grid; gap: 1rem; grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .as-col { border: 1px solid #e3ebf4; border-radius: 12px; padding: .85rem; min-width: 0; }
        .as-head { display: flex; align-items: center; gap: .5rem; margin-bottom: .6rem; }
        .as-all { margin-inline-start: auto; font-size: .85rem; cursor: pointer; display: inline-flex; gap: .35rem; align-items: center; }
        .as-list { max-height: 280px; overflow-y: auto; }
        .as-item { display: flex; align-items: center; gap: .5rem; padding: .3rem .25rem; margin: 0; cursor: pointer; border-radius: 6px; }
        .as-item:hover { background: #f4f8fc; }
        .as-item small { margin-inline-start: auto; white-space: nowrap; }
        @media (max-width: 991.98px) { .as-grid { grid-template-columns: 1fr; } }
    </style>
    <script>
        // «الكل» يحدّد الظاهر من العمود فقط، والبحث يصفّي القائمة.
        document.querySelectorAll('.as-col').forEach(function (col) {
            var all = col.querySelector('.as-check-all');
            var filter = col.querySelector('.as-filter');

            if (all) {
                all.addEventListener('change', function () {
                    col.querySelectorAll('.as-item').forEach(function (item) {
                        if (!item.hidden) item.querySelector('.as-check').checked = all.checked;
                    });
                });
            }

            if (filter) {
                filter.addEventListener('input', function () {
                    var q = filter.value.trim().toLowerCase();
                    col.querySelectorAll('.as-item').forEach(function (item) {
                        item.hidden = q !== '' && item.textContent.toLowerCase().indexOf(q) === -1;
                    });
                });
            }
        });
    </script>
    @endpush
@endonce
