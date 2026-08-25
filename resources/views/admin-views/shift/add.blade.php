@extends('layouts.admin.app')

@section('title', \App\CPU\translate('إضافة شفت جديد'))

@push('css_or_js')
    <link rel="stylesheet" href="{{ asset('public/assets/admin') }}/css/custom.css" />
@endpush

@section('content')
    <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('admin.shift.store') }}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="name">{{ \App\CPU\translate('اسم الشفت') }}</label>
                                <input type="text" name="name" id="name" class="form-control"
                                       placeholder="{{ \App\CPU\translate('أدخل اسم الشفت') }}" required>
                                <input name="position" value="0" class="d-none">
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="kilometer">{{ \App\CPU\translate('المسافة بالكيلومتر') }}</label>
                                <input type="number" name="kilometer" id="kilometer" class="form-control" min="0" required>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="start">{{ \App\CPU\translate('وقت البداية') }}</label>
                                <input type="time" name="start" id="start" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="end">{{ \App\CPU\translate('وقت النهاية') }}</label>
                                <input type="time" name="end" id="end" class="form-control" required>
                            </div>
                        </div>

                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="breake">{{ \App\CPU\translate('مدة الاستراحة (بالدقائق)') }}</label>
                                <input type="number" name="breake" id="breake" class="form-control" required>
                            </div>
                        </div>

                        {{-- جديد: هل الشفت مقسم لأكثر من شفت؟ --}}
                        <!--<div class="col-12 col-sm-6 col-md-6 col-lg-6">-->
                        <!--    <div class="form-group form-check">-->
                        <!--        <input type="checkbox" name="is_divided" id="is_divided" class="form-check-input" value="1">-->
                        <!--        <label for="is_divided" class="form-check-label">-->
                        <!--            {{ \App\CPU\translate('هل الشفت مقسم لأكثر من شفت؟') }}-->
                        <!--        </label>-->
                        <!--    </div>-->
                        <!--</div>-->

                        <div id="divided_fields" class="w-100" style="display: none;">
                            <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label for="divisions_count">{{ \App\CPU\translate('عدد التقسيمات') }}</label>
                                    <input type="number" name="number_shifts" id="divisions_count" class="form-control" min="1">
                                </div>
                            </div>

                            <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                                <div class="form-group">
                                    <label for="division_hours">{{ \App\CPU\translate('عدد ساعات العمل في كل مرة') }}</label>
                                    <input type="number" name="hours_of_each_shift" id="division_hours" class="form-control" min="1">
                                </div>
                            </div>
                        </div>

                        {{-- جديد: أقصى مدة مسموح بالبصمة بعد انتهاء الشفت --}}
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6">
                            <div class="form-group">
                                <label for="max_checkout_after_shift">
                                    {{ \App\CPU\translate('أقصى مدة ممكن البصمة بعدها خروج بعد انتهاء الشيفت (بالدقائق)') }}
                                </label>
                                <input type="number" name="max" id="max_checkout_after_shift" class="form-control" min="0">
                            </div>
                        </div>

                        <input name="active" value="1" class="d-none">
                    </div>

                    <button type="submit" class="btn btn-primary col-12">
                        {{ \App\CPU\translate('حفظ') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection

    <script>
        $(document).ready(function () {
            function toggleDivided() {
                if ($('#is_divided').is(':checked')) {
                    $('#divided_fields').show();
                    $('#divisions_count, #division_hours').attr('required', true);
                } else {
                    $('#divided_fields').hide();
                    $('#divisions_count, #division_hours').removeAttr('required').val('');
                }
            }

            $('#is_divided').change(toggleDivided);
            toggleDivided();
        });
    </script>
