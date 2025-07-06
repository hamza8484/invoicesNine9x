@extends('layouts.master')

@section('title')
    {{ isset($task) ? 'تعديل مهمة' : 'إضافة مهمة جديدة' }} لمشروع {{ $project->project_name }} - ناينوكس
@stop

@section('css')
    <link href="{{ URL::asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('assets/plugins/jquery-ui/jquery-ui.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('assets/plugins/pickadate/themes/default.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('assets/plugins/pickadate/themes/default.date.css') }}">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المهام</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ isset($task) ? 'تعديل مهمة' : 'إضافة مهمة جديدة' }} لمشروع: {{ $project->project_name }}</span>
            </div>
        </div>
    </div>
    @endsection

@section('content')

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
                    <form action="{{ isset($task) ? route('projects.tasks.update', [$project->id, $task->id]) : route('projects.tasks.store', $project->id) }}" method="post" autocomplete="off">
                        {{ csrf_field() }}
                        @if (isset($task))
                            {{ method_field('patch') }} {{-- أو 'PUT' --}}
                        @endif

                        <div class="row">
                            {{-- Task Title --}}
                            <div class="col-lg-6 form-group">
                                <label for="title">عنوان المهمة <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="title" name="title"
                                    value="{{ old('title', $task->title ?? '') }}" required>
                            </div>

                            {{-- Assigned To --}}
                            <div class="col-lg-6 form-group">
                                <label for="assigned_to">المسؤول عن المهمة</label>
                                <select name="assigned_to" id="assigned_to" class="form-control SlectBox">
                                    <option value="">غير معين</option>
                                    @foreach ($assignableUsers as $user)
                                        <option value="{{ $user->id }}" {{ (old('assigned_to', $task->assigned_to ?? '') == $user->id) ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->role }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Status --}}
                            <div class="col-lg-6 form-group">
                                <label for="status">الحالة <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control SlectBox" required>
                                    <option value="pending" {{ (old('status', $task->status ?? '') == 'pending') ? 'selected' : '' }}>معلقة</option>
                                    <option value="in_progress" {{ (old('status', $task->status ?? '') == 'in_progress') ? 'selected' : '' }}>قيد التقدم</option>
                                    <option value="completed" {{ (old('status', $task->status ?? '') == 'completed') ? 'selected' : '' }}>مكتملة</option>
                                    <option value="delayed" {{ (old('status', $task->status ?? '') == 'delayed') ? 'selected' : '' }}>متأخرة</option>
                                </select>
                            </div>

                            {{-- Priority --}}
                            <div class="col-lg-6 form-group">
                                <label for="priority">الأولوية <span class="text-danger">*</span></label>
                                <select name="priority" id="priority" class="form-control SlectBox" required>
                                    <option value="low" {{ (old('priority', $task->priority ?? '') == 'low') ? 'selected' : '' }}>منخفضة</option>
                                    <option value="medium" {{ (old('priority', $task->priority ?? '') == 'medium') ? 'selected' : '' }}>متوسطة</option>
                                    <option value="high" {{ (old('priority', $task->priority ?? '') == 'high') ? 'selected' : '' }}>عالية</option>
                                    <option value="urgent" {{ (old('priority', $task->priority ?? '') == 'urgent') ? 'selected' : '' }}>عاجلة</option>
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Start Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="start_date">تاريخ البدء</label>
                                <input class="form-control fc-datepicker" name="start_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('start_date', isset($task->start_date) ? $task->start_date->format('Y-m-d') : '') }}">
                            </div>

                            {{-- End Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="end_date">تاريخ الانتهاء</label>
                                <input class="form-control fc-datepicker" name="end_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('end_date', isset($task->end_date) ? $task->end_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>

                        {{-- Description --}}
                        <div class="form-group">
                            <label for="description">الوصف</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $task->description ?? '') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <button type="submit" class="btn btn-primary">{{ isset($task) ? 'تحديث المهمة' : 'إضافة المهمة' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    <script src="{{ URL::asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script>
        $(function() {
            $('.SlectBox').select2({
                minimumResultsForSearch: Infinity
            });
        });
    </script>
    <script src="{{ URL::asset('assets/plugins/sumoselect/jquery.sumoselect.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/jquery-ui/ui/widgets/datepicker.js') }}"></script>
    <script>
        $(function() {
            // It is important that the name of the input must be date as the class name
            $('.fc-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                showOtherMonths: true,
                selectOtherMonths: true
            });
        });
    </script>
@endsection