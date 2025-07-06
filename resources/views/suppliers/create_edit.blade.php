@extends('layouts.master')

@section('title')
    {{ isset($supplier) ? 'تعديل مورد' : 'إضافة مورد جديد' }} - ناينوكس
@stop

@section('css')
    <link href="{{ URL::asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css') }}">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">الموردون</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ isset($supplier) ? 'تعديل مورد' : 'إضافة مورد جديد' }}</span>
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
                    <form action="{{ isset($supplier) ? route('suppliers.update', $supplier->id) : route('suppliers.store') }}" method="post" autocomplete="off">
                        {{ csrf_field() }}
                        @if (isset($supplier))
                            {{ method_field('patch') }} {{-- أو 'PUT' --}}
                        @endif

                        <div class="row">
                            {{-- Name --}}
                            <div class="col-lg-6 form-group">
                                <label for="name">اسم المورد <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ old('name', $supplier->name ?? '') }}" required>
                            </div>

                            {{-- VAT No --}}
                            <div class="col-lg-6 form-group">
                                <label for="vat_No">رقم ضريبة القيمة المضافة</label>
                                <input type="text" class="form-control" id="vat_No" name="vat_No"
                                    value="{{ old('vat_No', $supplier->vat_No ?? '') }}">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Company --}}
                            <div class="col-lg-6 form-group">
                                <label for="company">الشركة (إن وجدت)</label>
                                <input type="text" class="form-control" id="company" name="company"
                                    value="{{ old('company', $supplier->company ?? '') }}">
                            </div>

                            {{-- Contact Person --}}
                            <div class="col-lg-6 form-group">
                                <label for="contact_person">شخص الاتصال</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person"
                                    value="{{ old('contact_person', $supplier->contact_person ?? '') }}">
                            </div>
                        </div>

                        <div class="row">
                            {{-- Phone --}}
                            <div class="col-lg-6 form-group">
                                <label for="phone">الهاتف</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    value="{{ old('phone', $supplier->phone ?? '') }}">
                            </div>

                            {{-- Email --}}
                            <div class="col-lg-6 form-group">
                                <label for="email">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ old('email', $supplier->email ?? '') }}">
                            </div>
                        </div>

                        {{-- Address --}}
                        <div class="form-group">
                            <label for="address">العنوان</label>
                            <textarea class="form-control" id="address" name="address" rows="3">{{ old('address', $supplier->address ?? '') }}</textarea>
                        </div>

                        {{-- Category --}}
                        <div class="form-group">
                            <label for="category">الفئة</label>
                            <input type="text" class="form-control" id="category" name="category"
                                value="{{ old('category', $supplier->category ?? '') }}">
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <button type="submit" class="btn btn-primary">{{ isset($supplier) ? 'تحديث المورد' : 'إضافة المورد' }}</button>
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
@endsection