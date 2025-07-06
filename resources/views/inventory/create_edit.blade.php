@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', $inventory->exists ? 'تعديل سجل مخزون - ناينوكس' : 'إضافة سجل مخزون جديد - ناينوكس')

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
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ $inventory->exists ? 'تعديل سجل مخزون' : 'إضافة سجل مخزون جديد' }}</span>
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
                    <form action="{{ $inventory->exists ? route('inventory.update', $inventory->id) : route('inventory.store') }}" method="post" autocomplete="off">
                        @csrf
                        @if($inventory->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="material_id">المادة <span class="text-danger">*</span></label>
                                    <select name="material_id" id="material_id" class="form-control select2 @error('material_id') is-invalid @enderror"
                                        {{ $inventory->exists ? 'disabled' : '' }}> {{-- تعطيل الاختيار عند التعديل لضمان عدم كسر القيد الفريد --}}
                                        <option value="">اختر المادة</option>
                                        @foreach($materials as $material)
                                            <option value="{{ $material->id }}" {{ old('material_id', $inventory->material_id) == $material->id ? 'selected' : '' }}>
                                                {{ $material->name }} ({{ $material->code ?? '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('material_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    @if($inventory->exists)
                                        {{-- أضف حقل مخفي لإرسال القيمة حتى لو كان الحقل معطلاً --}}
                                        <input type="hidden" name="material_id" value="{{ $inventory->material_id }}">
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="warehouse_id">المستودع <span class="text-danger">*</span></label>
                                    <select name="warehouse_id" id="warehouse_id" class="form-control select2 @error('warehouse_id') is-invalid @enderror"
                                        {{ $inventory->exists ? 'disabled' : '' }}> {{-- تعطيل الاختيار عند التعديل لضمان عدم كسر القيد الفريد --}}
                                        <option value="">اختر المستودع</option>
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $inventory->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                                {{ $warehouse->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('warehouse_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                    @if($inventory->exists)
                                        {{-- أضف حقل مخفي لإرسال القيمة حتى لو كان الحقل معطلاً --}}
                                        <input type="hidden" name="warehouse_id" value="{{ $inventory->warehouse_id }}">
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="quantity">الكمية <span class="text-danger">*</span></label>
                                    <input type="number" min="0" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity"
                                        value="{{ old('quantity', $inventory->quantity) }}">
                                    @error('quantity')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="cost_price">سعر التكلفة (اختياري)</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('cost_price') is-invalid @enderror" id="cost_price" name="cost_price"
                                        value="{{ old('cost_price', $inventory->cost_price) }}">
                                    @error('cost_price')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary">{{ $inventory->exists ? 'تحديث سجل المخزون' : 'حفظ سجل المخزون' }}</button>
                            <a href="{{ route('inventory.index') }}" class="btn btn-secondary mr-2">إلغاء</a>
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
