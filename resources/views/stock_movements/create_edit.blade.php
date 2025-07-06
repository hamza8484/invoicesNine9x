@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', $stockMovement->exists ? 'تعديل حركة مخزون - ناينوكس' : 'إضافة حركة مخزون جديدة - ناينوكس')

@section('css')
    <!-- Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <!--Internal Sumoselect css-->
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css')}}">
    <!-- Internal Jquery-ui css -->
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المخزون</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ $stockMovement->exists ? 'تعديل حركة مخزون' : 'إضافة حركة مخزون جديدة' }}</span>
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
                    <form action="{{ $stockMovement->exists ? route('stock_movements.update', $stockMovement->id) : route('stock_movements.store') }}" method="post" autocomplete="off">
                        @csrf
                        @if($stockMovement->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="material_id">المادة <span class="text-danger">*</span></label>
                                    <select name="material_id" id="material_id" class="form-control select2 @error('material_id') is-invalid @enderror">
                                        <option value="">اختر المادة</option>
                                        @foreach($materials as $material)
                                            <option value="{{ $material->id }}" {{ old('material_id', $stockMovement->material_id) == $material->id ? 'selected' : '' }}>
                                                {{ $material->name }} ({{ $material->code ?? '' }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('material_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="warehouse_id">المستودع <span class="text-danger">*</span></label>
                                    <select name="warehouse_id" id="warehouse_id" class="form-control select2 @error('warehouse_id') is-invalid @enderror">
                                        <option value="">اختر المستودع</option>
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}" {{ old('warehouse_id', $stockMovement->warehouse_id) == $warehouse->id ? 'selected' : '' }}>
                                                {{ $warehouse->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('warehouse_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="transaction_type">نوع الحركة <span class="text-danger">*</span></label>
                                    <select name="transaction_type" id="transaction_type" class="form-control select2 @error('transaction_type') is-invalid @enderror">
                                        <option value="">اختر نوع الحركة</option>
                                        @foreach($transactionTypes as $key => $value)
                                            <option value="{{ $key }}" {{ old('transaction_type', $stockMovement->transaction_type) == $key ? 'selected' : '' }}>
                                                {{ $value }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('transaction_type')
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
                                    <label for="quantity">الكمية <span class="text-danger">*</span></label>
                                    <input type="number" min="1" class="form-control @error('quantity') is-invalid @enderror" id="quantity" name="quantity"
                                        value="{{ old('quantity', $stockMovement->quantity) }}">
                                    @error('quantity')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="unit_cost">سعر الوحدة (اختياري)</label>
                                    <input type="number" step="0.01" min="0" class="form-control @error('unit_cost') is-invalid @enderror" id="unit_cost" name="unit_cost"
                                        value="{{ old('unit_cost', $stockMovement->unit_cost) }}">
                                    @error('unit_cost')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label for="transaction_date">تاريخ الحركة <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control fc-datepicker @error('transaction_date') is-invalid @enderror" id="transaction_date" name="transaction_date"
                                        value="{{ old('transaction_date', $stockMovement->transaction_date ? $stockMovement->transaction_date->format('Y-m-d') : date('Y-m-d')) }}"
                                        placeholder="YYYY-MM-DD">
                                    @error('transaction_date')
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
                                    <label for="reference_type">نوع المرجع (اختياري)</label>
                                    <input type="text" class="form-control @error('reference_type') is-invalid @enderror" id="reference_type" name="reference_type"
                                        value="{{ old('reference_type', $stockMovement->reference_type) }}" placeholder="مثال: App\Models\Invoice">
                                    @error('reference_type')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="reference_id">معرف المرجع (اختياري)</label>
                                    <input type="number" min="1" class="form-control @error('reference_id') is-invalid @enderror" id="reference_id" name="reference_id"
                                        value="{{ old('reference_id', $stockMovement->reference_id) }}">
                                    @error('reference_id')
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
                                    <label for="user_id">بواسطة المستخدم <span class="text-danger">*</span></label>
                                    <select name="user_id" id="user_id" class="form-control select2 @error('user_id') is-invalid @enderror">
                                        <option value="">اختر المستخدم</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ old('user_id', $stockMovement->user_id) == $user->id ? 'selected' : '' }}>
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
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="notes">ملاحظات (اختياري)</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $stockMovement->notes) }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary">{{ $stockMovement->exists ? 'تحديث حركة المخزون' : 'حفظ حركة المخزون' }}</button>
                            <a href="{{ route('stock_movements.index') }}" class="btn btn-secondary mr-2">إلغاء</a>
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
    <!-- Internal Jquery-ui js -->
    <script src="{{URL::asset('assets/plugins/jquery-ui/ui/widgets/datepicker.js')}}"></script>
    <script>
        $(function() {
            $('.select2').select2({
                placeholder: 'اختر...',
                width: '100%',
                dir: "rtl" // إذا كانت الواجهة بالعربية
            });

            // تهيئة Datepicker
            $('#transaction_date').datepicker({
                dateFormat: 'yy-mm-dd',
                // يمكنك إضافة خيارات أخرى هنا
            });

            // تعيين تاريخ اليوم كقيمة افتراضية عند الإضافة إذا لم يكن هناك قيمة
            if (!$('#transaction_date').val()) {
                $('#transaction_date').val(new Date().toISOString().slice(0,10));
            }
        });
    </script>
@endsection
