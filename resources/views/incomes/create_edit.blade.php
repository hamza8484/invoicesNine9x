@extends('layouts.master')

@section('title')
    {{ isset($income) ? 'تعديل إيراد' : 'إضافة إيراد جديد' }} - ناينوكس
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
                <h4 class="content-title mb-0 my-auto">الإيرادات</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ isset($income) ? 'تعديل إيراد' : 'إضافة إيراد جديد' }}</span>
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
                    <form action="{{ isset($income) ? route('incomes.update', $income->id) : route('incomes.store') }}" method="post" autocomplete="off">
                        {{ csrf_field() }}
                        @if (isset($income))
                            {{ method_field('patch') }} {{-- أو 'PUT' --}}
                        @endif

                        <div class="row">
                            {{-- Project --}}
                            <div class="col-lg-6 form-group">
                                <label for="project_id">المشروع <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-control SlectBox" required>
                                    <option value="">-- اختر المشروع --</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" {{ (old('project_id', $income->project_id ?? '') == $project->id) ? 'selected' : '' }}>
                                            {{ $project->project_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Amount --}}
                            <div class="col-lg-6 form-group">
                                <label for="amount">المبلغ <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount"
                                    value="{{ old('amount', $income->amount ?? '') }}" required min="0.01">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Income Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="income_date">تاريخ الإيراد <span class="text-danger">*</span></label>
                                <input class="form-control fc-datepicker" name="income_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('income_date', isset($income->income_date) ? $income->income_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                            </div>

                            {{-- Source --}}
                            <div class="col-lg-6 form-group">
                                <label for="source">المصدر</label>
                                <input type="text" class="form-control" id="source" name="source"
                                    value="{{ old('source', $income->source ?? '') }}">
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $income->notes ?? '') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <button type="submit" class="btn btn-primary">{{ isset($income) ? 'تحديث الإيراد' : 'إضافة الإيراد' }}</button>
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