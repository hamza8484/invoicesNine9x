@extends('layouts.master')

@section('title')
    {{ isset($contract) ? 'تعديل عقد' : 'إضافة عقد جديد' }} - ناينوكس
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
                <h4 class="content-title mb-0 my-auto">العقود</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ isset($contract) ? 'تعديل عقد' : 'إضافة عقد جديد' }}</span>
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
                    <form action="{{ isset($contract) ? route('contracts.update', $contract->id) : route('contracts.store') }}" method="post" autocomplete="off">
                        {{ csrf_field() }}
                        @if (isset($contract))
                            {{ method_field('patch') }} {{-- أو 'PUT' --}}
                        @endif

                        <div class="row">
                            {{-- Contract Number --}}
                            <div class="col-lg-6 form-group">
                                <label for="contract_number">رقم العقد <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="contract_number" name="contract_number"
                                    value="{{ old('contract_number', $contract->contract_number ?? '') }}" required>
                            </div>

                            {{-- Project --}}
                            <div class="col-lg-6 form-group">
                                <label for="project_id">المشروع <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-control SlectBox" required>
                                    <option value="">-- اختر المشروع --</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" {{ (old('project_id', $contract->project_id ?? '') == $project->id) ? 'selected' : '' }}>
                                            {{ $project->project_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Client --}}
                            <div class="col-lg-6 form-group">
                                <label for="client_id">العميل <span class="text-danger">*</span></label>
                                <select name="client_id" id="client_id" class="form-control SlectBox" required>
                                    <option value="">-- اختر العميل --</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" {{ (old('client_id', $contract->client_id ?? '') == $client->id) ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Total Amount --}}
                            <div class="col-lg-6 form-group">
                                <label for="total_amount">القيمة الكلية للعقد <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="total_amount" name="total_amount"
                                    value="{{ old('total_amount', $contract->total_amount ?? '') }}" required min="0">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Start Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="start_date">تاريخ البدء <span class="text-danger">*</span></label>
                                <input class="form-control fc-datepicker" name="start_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('start_date', isset($contract->start_date) ? $contract->start_date->format('Y-m-d') : '') }}" required>
                            </div>

                            {{-- End Date --}}
                            <div class="col-lg-6 form-group">
                                <label for="end_date">تاريخ الانتهاء</label>
                                <input class="form-control fc-datepicker" name="end_date" placeholder="YYYY-MM-DD"
                                    type="text" value="{{ old('end_date', isset($contract->end_date) ? $contract->end_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Status --}}
                            <div class="col-lg-12 form-group">
                                <label for="status">الحالة <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-control SlectBox" required>
                                    <option value="active" {{ (old('status', $contract->status ?? '') == 'active') ? 'selected' : '' }}>نشط</option>
                                    <option value="expired" {{ (old('status', $contract->status ?? '') == 'expired') ? 'selected' : '' }}>منتهي الصلاحية</option>
                                    <option value="terminated" {{ (old('status', $contract->status ?? '') == 'terminated') ? 'selected' : '' }}>تم إنهاؤه</option>
                                </select>
                            </div>
                        </div>

                        {{-- Notes --}}
                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $contract->notes ?? '') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <button type="submit" class="btn btn-primary">{{ isset($contract) ? 'تحديث العقد' : 'إضافة العقد' }}</button>
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