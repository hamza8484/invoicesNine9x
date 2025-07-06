@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', $material->exists ? 'تعديل مادة - ناينوكس' : 'إضافة مادة جديدة - ناينوكس')

@section('css')
    <!-- Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <!--Internal Sumoselect css-->
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css')}}">
    <!-- Internal File Uploads css -->
    <link href="{{URL::asset('assets/plugins/fileuploads/css/fileupload.css')}}" rel="stylesheet" type="text/css" />
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المخزون</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ $material->exists ? 'تعديل مادة' : 'إضافة مادة جديدة' }}</span>
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
                    <form action="{{ $material->exists ? route('materials.update', $material->id) : route('materials.store') }}" method="post" autocomplete="off" enctype="multipart/form-data">
                        @csrf
                        @if($material->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="name">اسم المادة <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                                        value="{{ old('name', $material->name) }}">
                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="code">رمز المادة (اختياري)</label>
                                    <input type="text" class="form-control @error('code') is-invalid @enderror" id="code" name="code"
                                        value="{{ old('code', $material->code) }}">
                                    @error('code')
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
                                    <label for="unit_id">الوحدة <span class="text-danger">*</span></label>
                                    <select name="unit_id" id="unit_id" class="form-control select2 @error('unit_id') is-invalid @enderror">
                                        <option value="">اختر الوحدة</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit->id }}" {{ old('unit_id', $material->unit_id) == $unit->id ? 'selected' : '' }}>
                                                {{ $unit->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('unit_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="material_group_id">المجموعة (اختياري)</label>
                                    <select name="material_group_id" id="material_group_id" class="form-control select2 @error('material_group_id') is-invalid @enderror">
                                        <option value="">اختر المجموعة</option>
                                        @foreach($materialGroups as $group)
                                            <option value="{{ $group->id }}" {{ old('material_group_id', $material->material_group_id) == $group->id ? 'selected' : '' }}>
                                                {{ $group->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('material_group_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="tax_id">الضريبة (اختياري)</label>
                                    <select name="tax_id" id="tax_id" class="form-control select2 @error('tax_id') is-invalid @enderror">
                                        <option value="">اختر الضريبة</option>
                                        @foreach($taxes as $tax)
                                            <option value="{{ $tax->id }}" {{ old('tax_id', $material->tax_id) == $tax->id ? 'selected' : '' }}>
                                                {{ $tax->name }} ({{ number_format($tax->rate, 2) }}%)
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tax_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong><strong>{{ $message }}</strong></strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="purchase_price">سعر الشراء <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('purchase_price') is-invalid @enderror" id="purchase_price" name="purchase_price"
                                        value="{{ old('purchase_price', $material->purchase_price) }}">
                                    @error('purchase_price')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="sale_price">سعر البيع <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('sale_price') is-invalid @enderror" id="sale_price" name="sale_price"
                                        value="{{ old('sale_price', $material->sale_price) }}">
                                    @error('sale_price')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="stock_quantity">الكمية بالمخزون <span class="text-danger">*</span></label>
                                    <input type="number" min="0" class="form-control @error('stock_quantity') is-invalid @enderror" id="stock_quantity" name="stock_quantity"
                                        value="{{ old('stock_quantity', $material->stock_quantity) }}">
                                    @error('stock_quantity')
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
                                    <label for="image">صورة المادة (اختياري)</label>
                                    <input type="file" class="form-control-file @error('image') is-invalid @enderror" id="image" name="image" accept="image/*">
                                    <small class="form-text text-muted">الحد الأقصى: 2 ميجابايت (JPG, PNG, GIF).</small>
                                    @error('image')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    @if($material->image_url)
                                        <div class="mt-3">
                                            <h6>الصورة الحالية:</h6>
                                            <img src="{{ $material->image_url }}" alt="صورة المادة" width="150" class="img-thumbnail">
                                            <div class="form-check mt-2">
                                                <input class="form-check-input" type="checkbox" name="clear_image" id="clear_image" value="1">
                                                <label class="form-check-label" for="clear_image">
                                                    حذف الصورة الحالية
                                                </label>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="description">الوصف (اختياري)</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="5">{{ old('description', $material->description) }}</textarea>
                                    @error('description')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary">{{ $material->exists ? 'تحديث المادة' : 'حفظ المادة' }}</button>
                            <a href="{{ route('materials.index') }}" class="btn btn-secondary mr-2">إلغاء</a>
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
    <!-- Internal File Uploads js -->
    <script src="{{URL::asset('assets/plugins/fileuploads/js/fileupload.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/fileuploads/js/file-upload.js')}}"></script>
    <script>
        $(function() {
            $('.select2').select2({
                placeholder: 'اختر...',
                width: '100%',
                dir: "rtl" // إذا كانت الواجهة بالعربية
            });
        });
    </script>
@endsection
