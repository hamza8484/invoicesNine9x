@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', $timesheet->exists ? 'تعديل سجل دوام - ناينوكس' : 'إضافة سجل دوام جديد - ناينوكس')

@section('css')
    <!-- Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <!-- Internal Jquery-ui css -->
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
    <!-- Internal Timepicker css -->
    <link href="{{URL::asset('assets/plugins/timepicker/jquery.timepicker.css')}}" rel="stylesheet" />
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">جداول الدوام</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ $timesheet->exists ? 'تعديل سجل دوام' : 'إضافة سجل دوام جديد' }}</span>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (session()->has('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <strong>{{ session()->get('success') }}</strong>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>{{ session()->get('error') }}</strong>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ $timesheet->exists ? route('timesheets.update', $timesheet->id) : route('timesheets.store') }}" method="post" autocomplete="off">
                        @csrf
                        @if($timesheet->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="user_id">الموظف <span class="text-danger">*</span></label>
                                    <select name="user_id" id="user_id" class="form-control select2 @error('user_id') is-invalid @enderror">
                                        <option value="">اختر الموظف</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id', $timesheet->user_id) == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="project_id">المشروع <span class="text-danger">*</span></label>
                                    <select name="project_id" id="project_id" class="form-control select2 @error('project_id') is-invalid @enderror">
                                        <option value="">اختر المشروع</option>
                                        @foreach($projects as $project)
                                            <option value="{{ $project->id }}" {{ old('project_id', $timesheet->project_id) == $project->id ? 'selected' : '' }}>
                                                {{ $project->project_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('project_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="task_id">المهمة (اختياري)</label>
                                    <select name="task_id" id="task_id" class="form-control select2 @error('task_id') is-invalid @enderror">
                                        <option value="">اختر المهمة</option>
                                        @foreach($tasks as $task)
                                            <option value="{{ $task->id }}" {{ old('task_id', $timesheet->task_id) == $task->id ? 'selected' : '' }}>
                                                {{ $task->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('task_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="work_date">تاريخ العمل <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control fc-datepicker @error('work_date') is-invalid @enderror" id="work_date" name="work_date"
                                        value="{{ old('work_date', $timesheet->work_date ? $timesheet->work_date->format('Y-m-d') : date('Y-m-d')) }}"
                                        placeholder="YYYY-MM-DD">
                                    @error('work_date')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="start_time">وقت البدء <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control timepicker @error('start_time') is-invalid @enderror" id="start_time" name="start_time"
                                        value="{{ old('start_time', $timesheet->start_time ? $timesheet->start_time->format('H:i') : '09:00') }}"
                                        placeholder="HH:MM">
                                    @error('start_time')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="end_time">وقت النهاية <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control timepicker @error('end_time') is-invalid @enderror" id="end_time" name="end_time"
                                        value="{{ old('end_time', $timesheet->end_time ? $timesheet->end_time->format('H:i') : '17:00') }}"
                                        placeholder="HH:MM">
                                    @error('end_time')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes">ملاحظات (اختياري)</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $timesheet->notes) }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary">{{ $timesheet->exists ? 'تحديث سجل الدوام' : 'حفظ سجل الدوام' }}</button>
                            <a href="{{ route('timesheets.index') }}" class="btn btn-secondary mr-2">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <!-- Internal Select2 js -->
    <script src="{{URL::asset('assets/plugins/select2/js/select2.min.js')}}"></script>
    <!-- Internal Form-validation js -->
    <script src="{{URL::asset('assets/js/form-validation.js')}}"></script>
    <!-- Internal Jquery-ui js -->
    <script src="{{URL::asset('assets/plugins/jquery-ui/ui/widgets/datepicker.js')}}"></script>
    <!-- Internal Timepicker js -->
    <script src="{{URL::asset('assets/plugins/timepicker/jquery.timepicker.js')}}"></script>
    <script>
        $(function() {
            $('.select2').select2({
                placeholder: 'اختر...',
                width: '100%',
                dir: "rtl" // إذا كانت الواجهة بالعربية
            });

            // تهيئة Datepicker
            $('.fc-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                // يمكنك إضافة خيارات أخرى هنا مثل minDate, maxDate, etc.
            });

            // تهيئة Timepicker
            $('.timepicker').timepicker({
                timeFormat: 'H:i', // 24-hour format
                interval: 15,      // 15-minute intervals
                minTime: '00:00',
                maxTime: '23:45',
                defaultTime: '09:00', // Default start time
                startTime: '00:00',
                dynamic: false,
                dropdown: true,
                scrollbar: true
            });

            // تعيين تاريخ اليوم كقيمة افتراضية عند الإضافة إذا لم يكن هناك قيمة
            if (!$('#work_date').val()) {
                $('#work_date').val(new Date().toISOString().slice(0,10));
            }
        });
    </script>
@endsection
