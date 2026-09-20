@extends('layouts.admin.app')

@section('title', \App\CPU\translate('سجلات الحضور'))

@push('css_or_js')
    @include('admin-views.roles._tokens')
    <style>
        /* ---------- الفلاتر ---------- */

        .at-filters {
            display: grid;
            grid-template-columns: minmax(200px, 1.6fr) repeat(2, minmax(140px, 1fr)) auto;
            gap: .6rem;
            align-items: end;
        }

        .at-filters .f-label {
            font-size: .74rem;
            font-weight: 700;
            color: var(--hpi-muted);
            margin-bottom: .25rem;
            display: block;
        }

        .at-filters .form-control,
        .at-filters select {
            border: 1px solid var(--hpi-line);
            border-radius: 10px;
            padding: .45rem .7rem;
            font-size: .85rem;
            height: auto;
            width: 100%;
        }

        .at-filters .form-control:focus,
        .at-filters select:focus {
            outline: 0;
            border-color: var(--hpi-blue);
            box-shadow: 0 0 0 .18rem rgba(142,197,239,.28);
        }

        .at-filters .actions { display: flex; gap: .4rem; }

        @media (max-width: 991.98px) {
            .at-filters { grid-template-columns: repeat(2, 1fr); }
            .at-filters .actions { grid-column: 1 / -1; }
        }

        @media (max-width: 575.98px) {
            .at-filters { grid-template-columns: 1fr; }
        }

        /* ---------- بطاقات الملخّص ---------- */

        .at-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1rem;
            margin-bottom: 1.25rem;
        }

        .at-stat {
            background: #fff;
            border: 1px solid var(--hpi-line);
            border-radius: 14px;
            padding: 1rem 1.15rem;
            display: flex;
            align-items: center;
            gap: .85rem;
        }

        .at-stat .s-icon {
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

        .at-stat .s-icon.is-green { background: #e6f7ef; color: #0f7a4d; }
        .at-stat .s-icon.is-amber { background: #fef4e4; color: #a86412; }
        .at-stat .s-icon.is-red   { background: #fdecec; color: #b3261e; }

        .at-stat .s-label { font-size: .76rem; color: var(--hpi-muted); margin-bottom: .1rem; }

        .at-stat .s-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: var(--hpi-ink);
            line-height: 1.2;
            direction: ltr;
            text-align: start;
        }

        .at-stat .s-value .unit { font-size: .76rem; font-weight: 600; color: var(--hpi-muted); }

        .at-note {
            display: flex;
            align-items: flex-start;
            gap: .5rem;
            padding: .6rem .8rem;
            border-radius: 10px;
            background: #fef4e4;
            border: 1px solid #f3dcb4;
            color: #7a4a0e;
            font-size: .8rem;
            margin-bottom: 1.25rem;
        }

        /* ---------- الجدول ---------- */

        .at-table { width: 100%; margin: 0; }

        .at-table thead th {
            background: #f6fafd;
            font-size: .75rem;
            font-weight: 700;
            color: var(--hpi-muted);
            border: 0;
            border-bottom: 1px solid var(--hpi-line);
            padding: .65rem .7rem;
            white-space: nowrap;
        }

        .at-table tbody td {
            padding: .6rem .7rem;
            border-top: 1px solid var(--hpi-line);
            font-size: .84rem;
            vertical-align: middle;
            white-space: nowrap;
        }

        .at-table tbody tr:hover { background: #fbfdff; }

        .at-table .col-mail {
            direction: ltr;
            text-align: start;
            font-size: .78rem;
            color: var(--hpi-muted);
            max-width: 15rem;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .at-table .col-num { direction: ltr; text-align: start; font-variant-numeric: tabular-nums; }

        /* اسم المكان وحده يلتف؛ بقية الأعمدة سطر واحد. */
        .at-table .col-place {
            white-space: normal;
            max-width: 14rem;
            font-size: .8rem;
            color: var(--hpi-muted);
        }

        .at-time {
            display: inline-flex;
            align-items: center;
            gap: .25rem;
            font-size: .78rem;
            font-weight: 700;
            border-radius: 99px;
            padding: .1rem .5rem;
            direction: ltr;
        }

        .at-time.is-in  { background: #e6f7ef; color: #0f7a4d; }
        .at-time.is-out { background: #eaf4fb; color: var(--hpi-navy); }
        .at-time.is-open { background: #fef4e4; color: #a86412; }

        /* التأخير: صفر ليس خبرًا، فلا يُصبغ إلا ما تجاوزه. */
        .at-late {
            font-weight: 700;
            direction: ltr;
            font-variant-numeric: tabular-nums;
        }

        .at-late.is-late { color: #b3261e; }
        .at-late.is-fine { color: var(--hpi-muted); font-weight: 600; }

        .at-map {
            font-size: .74rem;
            font-weight: 700;
            border-radius: 99px;
            padding: .15rem .6rem;
            border: 1px solid var(--hpi-line);
            color: var(--hpi-navy);
            white-space: nowrap;
        }

        .at-map:hover { background: #eaf4fb; border-color: var(--hpi-blue); text-decoration: none; }

        .at-empty { padding: 3rem 1rem; text-align: center; color: var(--hpi-muted); }

        .at-pager {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            padding: .9rem 1.2rem;
            border-top: 1px solid var(--hpi-line);
        }
    </style>
@endpush

@section('content')
<div class="roles-wrap">

    <div class="roles-hero">
        <div class="hero-text">
            <h1><i class="tio-calendar-note mr-1"></i> {{ \App\CPU\translate('سجلات الحضور') }}</h1>
            <p>
                {{ number_format($attendances->total()) }} {{ \App\CPU\translate('سجل') }}
                &middot; {{ number_format($workingDays) }} {{ \App\CPU\translate('يوم عمل') }}
            </p>
        </div>
    </div>

    <div class="roles-panel">
        <div class="head" style="display:block;">
            <form action="{{ route('admin.attendance.index') }}" method="GET" class="at-filters">
                <div>
                    <label class="f-label" for="at-emp">{{ \App\CPU\translate('الموظف') }}</label>
                    <select name="employee_id" id="at-emp">
                        <option value="">{{ \App\CPU\translate('كل الموظفين') }}</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}"
                                @selected((string) request('employee_id') === (string) $employee->id)>
                                {{ $employee->email }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="f-label" for="at-from">{{ \App\CPU\translate('من تاريخ') }}</label>
                    <input type="date" id="at-from" name="start_date" class="form-control"
                           value="{{ request('start_date') }}">
                </div>

                <div>
                    <label class="f-label" for="at-to">{{ \App\CPU\translate('إلى تاريخ') }}</label>
                    <input type="date" id="at-to" name="end_date" class="form-control"
                           value="{{ request('end_date') }}">
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="tio-filter-list mr-1"></i> {{ \App\CPU\translate('فلترة') }}
                    </button>

                    @if(request()->hasAny(['employee_id', 'start_date', 'end_date']))
                        <a href="{{ route('admin.attendance.index') }}" class="btn btn-outline-secondary">
                            {{ \App\CPU\translate('reset') }}
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    {{-- الإجماليات تأتي محسوبة من المتحكّم على كامل نتيجة الفلتر.
         إعادة حسابها هنا من $attendances كانت تقصرها على الصفحة المعروضة. --}}
    <div class="at-stats">
        <div class="at-stat">
            <div class="s-icon is-green"><i class="tio-time"></i></div>
            <div>
                <div class="s-label">{{ \App\CPU\translate('إجمالي ساعات العمل الفعلية') }}</div>
                <div class="s-value">
                    {{ number_format($totalWorkedHours, 2) }} <span class="unit">{{ \App\CPU\translate('ساعة') }}</span>
                </div>
            </div>
        </div>

        <div class="at-stat">
            <div class="s-icon"><i class="tio-calendar-month"></i></div>
            <div>
                <div class="s-label">{{ \App\CPU\translate('إجمالي ساعات العمل المتوقعة') }}</div>
                <div class="s-value">
                    {{ number_format($totalExpectedHours, 2) }} <span class="unit">{{ \App\CPU\translate('ساعة') }}</span>
                </div>
            </div>
        </div>

        <div class="at-stat">
            <div class="s-icon"><i class="tio-today"></i></div>
            <div>
                <div class="s-label">{{ \App\CPU\translate('عدد أيام العمل') }}</div>
                <div class="s-value">{{ number_format($workingDays) }}</div>
            </div>
        </div>

        <div class="at-stat">
            <div class="s-icon {{ $totalTimeLate > 0 ? 'is-red' : '' }}"><i class="tio-alarm"></i></div>
            <div>
                <div class="s-label">{{ \App\CPU\translate('إجمالي مدة التأخير') }}</div>
                <div class="s-value">
                    {{ number_format($totalTimeLate) }} <span class="unit">{{ \App\CPU\translate('دقيقة') }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(($openShifts ?? 0) > 0)
        {{-- سجلات بلا خروج تُحتسب ساعاتها المتوقعة وساعات عملها صفر، فتبدو
             الفجوة بين المتوقع والفعلي أكبر من حقيقتها. --}}
        <div class="at-note">
            <i class="tio-warning"></i>
            <span>{{ $openShifts }} سجل بلا تسجيل خروج، تُحتسب ساعاته المتوقعة وساعات عمله صفر.</span>
        </div>
    @endif

    <div class="roles-panel">
        <div class="body p-0">
            <div class="table-responsive">
                <table class="at-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ \App\CPU\translate('الموظف') }}</th>
                            <th>{{ \App\CPU\translate('التاريخ') }}</th>
                            <th>{{ \App\CPU\translate('تسجيل الدخول') }}</th>
                            <th>{{ \App\CPU\translate('تسجيل الخروج') }}</th>
                            <th>{{ \App\CPU\translate('ساعات العمل الفعلية') }}</th>
                            <th>{{ \App\CPU\translate('ساعات العمل المتوقعة') }}</th>
                            <th>{{ \App\CPU\translate('مدة التأخير (بالدقائق)') }}</th>
                            <th>{{ \App\CPU\translate('الموقع') }}</th>
                            <th>{{ \App\CPU\translate('اسم المكان') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                {{-- ترقيم متصل عبر الصفحات: $loop->iteration يبدأ
                                     من 1 في كل صفحة فيتكرر الرقم نفسه. --}}
                                <td class="col-num">{{ $attendances->firstItem() + $loop->index }}</td>

                                <td class="col-mail" title="{{ $attendance->admins->email ?? '' }}">
                                    {{ $attendance->admins->email ?? '—' }}
                                </td>

                                <td class="col-num">
                                    {{ \Carbon\Carbon::parse($attendance->date)->format('Y-m-d') }}
                                </td>

                                <td>
                                    @if($attendance->check_in)
                                        <span class="at-time is-in">
                                            {{ \Carbon\Carbon::parse($attendance->check_in)->format('H:i:s') }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td>
                                    @if($attendance->check_out)
                                        <span class="at-time is-out">
                                            {{ \Carbon\Carbon::parse($attendance->check_out)->format('H:i:s') }}
                                        </span>
                                    @else
                                        <span class="at-time is-open">{{ \App\CPU\translate('لم ينصرف') }}</span>
                                    @endif
                                </td>

                                <td class="col-num">{{ $attendance->worked_hours ?? '—' }}</td>
                                <td class="col-num">{{ $attendance->expected_hours ?? '—' }}</td>

                                <td>
                                    @php($late = (int) ($attendance->time_late ?? 0))
                                    <span class="at-late {{ $late > 0 ? 'is-late' : 'is-fine' }}">
                                        {{ number_format($late) }}
                                    </span>
                                </td>

                                <td>
                                    @if($attendance->late && $attendance->lang)
                                        <a href="https://www.google.com/maps?q={{ $attendance->lang }},{{ $attendance->late }}"
                                           class="at-map" target="_blank" rel="noopener">
                                            <i class="tio-poi"></i> {{ \App\CPU\translate('عرض الموقع') }}
                                        </a>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td class="col-place">{{ $attendance->note ?: '—' }}</td>
                            </tr>
                        @empty
                            {{-- عشرة أعمدة: كان colspan=8 فينكمش الصف عن عرض الجدول. --}}
                            <tr>
                                <td colspan="10">
                                    <div class="at-empty">
                                        <i class="tio-calendar-note" style="font-size:2rem;opacity:.4"></i>
                                        <p class="mt-2 mb-0">{{ \App\CPU\translate('لا توجد سجلات') }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($attendances->hasPages())
                <div class="at-pager">
                    <span class="text-muted small">
                        {{ \App\CPU\translate('عرض') }} {{ $attendances->firstItem() }} -
                        {{ $attendances->lastItem() }}
                        {{ \App\CPU\translate('من أصل') }} {{ number_format($attendances->total()) }}
                    </span>

                    {!! $attendances->appends(request()->query())->links() !!}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('script_2')
    <script src="{{ asset('public/assets/admin/js/global.js') }}"></script>
@endpush
