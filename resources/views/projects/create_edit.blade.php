@extends('layouts.master')

@section('title')
    {{ isset($project) ? 'تعديل مشروع' : 'إضافة مشروع جديد' }} - ناينوكس
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
                <h4 class="content-title mb-0 my-auto">المشاريع</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ isset($project) ? 'تعديل مشروع' : 'إضافة مشروع جديد' }}</span>
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
                    <form action="{{ isset($project->id) ? route('projects.update', $project->id) : route('projects.store') }}" method="post" autocomplete="off">                        {{ csrf_field() }}
                        @if (isset($project->id))
                            {{ method_field('patch') }} {{-- أو 'PUT' --}}
                        @endif

                        <div class="row">
                            {{-- Project Name --}}
                            <div class="col-lg-6 form-group">
                                <label for="project_name">اسم المشروع <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="project_name" name="project_name"
                                    value="{{ old('project_name', $project->project_name ?? '') }}" required>
                            </div>

                            {{-- Client --}}
                            <div class="col-lg-6 form-group">
                                <label for="client_id">العميل <span class="text-danger">*</span></label>
                                <select name="client_id" id="client_id" class="form-control SlectBox" required>
                                    <option value="">-- اختر العميل --</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" {{ (old('client_id', $project->client_id ?? '') == $client->id) ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Manager --}}
                            <div class="col-lg-6 form-group">
                                <label for="manager_id">مدير المشروع</label>
                                <select name="manager_id" id="manager_id" class="form-control SlectBox">
                                    <option value="">-- لا يوجد مدير --</option>
                                    @foreach ($managers as $user)
                                        <option value="{{ $user->id }}" {{ (old('manager_id', $project->manager_id ?? '') == $user->id) ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Location --}}
                            <div class="col-lg-6 form-group">
                                <label for="location">الموقع</label>
                                <input type="text" class="form-control" id="location" name="location"
                                    value="{{ old('location', $project->location ?? '') }}">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Start Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="start_date">تاريخ البدء <span class="text-danger">*</span></label>
                                <input class="form-control fc-datepicker" name="start_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('start_date', $project->start_date ? $project->start_date->format('Y-m-d') : '') }}" required>
                            </div>

                            {{-- End Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="end_date">تاريخ الانتهاء المتوقع <span class="text-danger">*</span></label>
                                <input class="form-control fc-datepicker" name="end_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('end_date', $project->end_date ? $project->end_date->format('Y-m-d') : '') }}" required>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Actual Start Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="actual_start_date">تاريخ البدء الفعلي</label>
                                <input class="form-control fc-datepicker" name="actual_start_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('actual_start_date', $project->actual_start_date ? $project->actual_start_date->format('Y-m-d') : '') }}">
                            </div>

                            {{-- Actual End Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="actual_end_date">تاريخ الانتهاء الفعلي</label>
                                <input class="form-control fc-datepicker" name="actual_end_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('actual_end_date', $project->actual_end_date ? $project->actual_end_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Budget --}}
                            <div class="col-lg-6 form-group">
                                <label for="budget">الميزانية المخصصة <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="budget" name="budget"
                                    value="{{ old('budget', $project->budget ?? '') }}" required min="0">
                            </div>

                            {{-- Contract Value --}}
                            <div class="col-lg-6 form-group">
                                <label for="contract_value">قيمة العقد</label>
                                <input type="number" step="0.01" class="form-control" id="contract_value" name="contract_value"
                                    value="{{ old('contract_value', $project->contract_value ?? '') }}" min="0">
                            </div>
                        </div>
                            
                        {{-- Current Spend and Total Income (Read-only if project exists) --}}
                        @if (isset($project))
                        <div class="row">
                            <div class="col-lg-6 form-group">
                                <label for="current_spend">المصروفات الحالية</label>
                                <input type="text" class="form-control" id="current_spend" value="{{ number_format($project->current_spend, 2) }}" readonly>
                            </div>
                            <div class="col-lg-6 form-group">
                                <label for="total_income">إجمالي الإيرادات</label>
                                <input type="text" class="form-control" id="total_income" 
                                value="{{ number_format($project->total_income, 2) }}" readonly>
                            </div>
                        </div>
                        @endif
                            
                        {{-- Status --}}
                        <div class="form-group">
                            <label for="status">الحالة <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control SlectBox" required>
                                <option value="planning" {{ (old('status', $project->status ?? '') == 'planning') ? 'selected' : '' }}>قيد التخطيط</option>
                                <option value="in_progress" {{ (old('status', $project->status ?? '') == 'in_progress') ? 'selected' : '' }}>قيد التنفيذ</option>
                                <option value="on_hold" {{ (old('status', $project->status ?? '') == 'on_hold') ? 'selected' : '' }}>معلق</option>
                                <option value="completed" {{ (old('status', $project->status ?? '') == 'completed') ? 'selected' : '' }}>مكتمل</option>
                                <option value="cancelled" {{ (old('status', $project->status ?? '') == 'cancelled') ? 'selected' : '' }}>ملغى</option>
                                <option value="archived" {{ (old('status', $project->status ?? '') == 'archived') ? 'selected' : '' }}>مؤرشف</option>
                            </select>
                        </div>

                        {{-- Description --}}
                        <div class="form-group">
                            <label for="description">الوصف</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $project->description ?? '') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <button type="submit" class="btn btn-primary">{{ isset($project) ? 'تحديث المشروع' : 'إضافة المشروع' }}</button>
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
            $('.fc-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                showOtherMonths: true,
                selectOtherMonths: true
            });
        });
    </script>
@endsection