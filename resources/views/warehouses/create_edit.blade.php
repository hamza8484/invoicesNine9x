@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', $warehouse->exists ? 'تعديل مستودع - ناينوكس' : 'إضافة مستودع جديد - ناينوكس')

@section('css')
    <!-- Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <!--Internal Sumoselect css-->
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css')}}">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المخزون</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ $warehouse->exists ? 'تعديل مستودع' : 'إضافة مستودع جديد' }}</span>
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
                    <form action="{{ $warehouse->exists ? route('warehouses.update', $warehouse->id) : route('warehouses.store') }}" method="post" autocomplete="off">
                        @csrf
                        @if($warehouse->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">اسم المستودع <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                        value="{{ old('name', $warehouse->name) }}">
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">رمز المستودع (اختياري)</label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                                        value="{{ old('code', $warehouse->code) }}">
                                    @error('code')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="location">الموقع/العنوان (اختياري)</label>
                                    <input type="text" class="form-control @error('location') is-invalid @enderror" id="location" name="location"
                                        value="{{ old('location', $warehouse->location) }}">
                                    @error('location')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="is_active">حالة المستودع</label>
                                    <div>
                                        <input type="checkbox" id="is_active" name="is_active" data-toggle="toggle" data-on="مفعل" data-off="غير مفعل"
                                            {{ old('is_active', $warehouse->is_active) ? 'checked' : '' }}>
                                    </div>
                                    @error('is_active')
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
                                    <label for="description">الوصف/ملاحظات (اختياري)</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3">{{ old('description', $warehouse->description) }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary">{{ $warehouse->exists ? 'تحديث المستودع' : 'حفظ المستودع' }}</button>
                            <a href="{{ route('warehouses.index') }}" class="btn btn-secondary mr-2">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <!--Internal  Select2 js -->
    <script src="{{URL::asset('assets/plugins/select2/js/select2.min.js')}}"></script>
    <!-- Internal Form-validation js -->
    <script src="{{URL::asset('assets/js/form-validation.js')}}"></script>
    <!-- For Toggle Switch (if using Bootstrap Toggle or similar) -->
    {{-- <link href="https://gitcdn.github.io/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet"> --}}
    {{-- <script src="https://gitcdn.github.io/bootstrap-toggle/2.2.2/js/bootstrap-toggle.min.js"></script> --}}
    <script>
        $(function() {
            $('.select2').select2({
                placeholder: 'اختر...',
                width: '100%',
                dir: "rtl" // إذا كانت الواجهة بالعربية
            });

            // تهيئة Bootstrap Toggle إذا كنت تستخدمه
            // $('#is_active').bootstrapToggle();
        });
    </script>
@endsection
