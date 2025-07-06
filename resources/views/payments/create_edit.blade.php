@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', $payment->exists ? 'تعديل دفعة - ناينوكس' : 'إضافة دفعة جديدة - ناينوكس') {{-- استخدام $payment->exists --}}

@section('css')
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/fileuploads/css/fileupload.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{URL::asset('assets/plugins/fancyuploder/fancy_fileupload.css')}}" rel="stylesheet" />
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css')}}">
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/telephoneinput/telephoneinput-rtl.css')}}">
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/jquery.sumoselect/sumoselect.css')}}" rel="stylesheet">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المدفوعات</h4>
                {{-- استخدام $payment->exists --}}
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ $payment->exists ? 'تعديل دفعة' : 'إضافة دفعة جديدة' }}</span>
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
                    {{-- استخدام $payment->exists لتحديد المسار والطريقة --}}
                    <form action="{{ $payment->exists ? route('payments.update', $payment->id) : route('payments.store') }}" method="post" autocomplete="off">
                        @csrf
                        @if($payment->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="invoice_id">الفاتورة <span class="text-danger">*</span></label>
                                    <select name="invoice_id" id="invoice_id" class="form-control select2 @error('invoice_id') is-invalid @enderror">
                                        <option value="">اختر الفاتورة</option>
                                        @foreach($invoices as $invoice)
                                            <option value="{{ $invoice->id }}"
                                                {{-- الشرط للتحديد بناءً على دفعة موجودة --}}
                                                {{ ($payment->invoice_id == $invoice->id) ? 'selected' : '' }}
                                                {{-- الشرط لتحديد الفاتورة من الـ URL عند الإنشاء (فقط إذا كانت الدفعة جديدة) --}}
                                                {{ ($payment->exists == false && isset($selectedInvoiceId) && $selectedInvoiceId == $invoice->id) ? 'selected' : '' }}>
                                                {{ $invoice->invoice_number }} (المبلغ المستحق: {{ number_format($invoice->due_amount, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('invoice_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_date">تاريخ الدفع <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control @error('payment_date') is-invalid @enderror" id="payment_date" name="payment_date"
                                        value="{{ old('payment_date', $payment->payment_date ? $payment->payment_date->format('Y-m-d') : date('Y-m-d')) }}">
                                        {{-- الحل للمشكلة 1: تحقق إذا كان payment_date موجودًا قبل التنسيق --}}
                                    @error('payment_date')
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
                                    <label for="amount">المبلغ <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount"
                                        value="{{ old('amount', $payment->amount) }}">
                                        {{-- الحل للمشكلة 3: إزالة ?? '' --}}
                                    @error('amount')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_method">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-control select2 @error('payment_method') is-invalid @enderror">
                                        <option value="">اختر طريقة الدفع</option>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method }}"
                                                {{ ($payment->payment_method == $method) ? 'selected' : '' }}
                                                {{ old('payment_method') == $method ? 'selected' : '' }}>
                                                {{ ucwords(str_replace('_', ' ', $method)) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('payment_method')
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
                                    <label for="notes">ملاحظات</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3">{{ old('notes', $payment->notes) }}</textarea>
                                    {{-- الحل للمشكلة 3: إزالة ?? '' --}}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary">{{ $payment->exists ? 'تحديث الدفعة' : 'حفظ الدفعة' }}</button>
                            <a href="{{ route('payments.index') }}" class="btn btn-secondary mr-2">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{URL::asset('assets/plugins/select2/js/select2.min.js')}}"></script>
    <script src="{{URL::asset('assets/js/form-validation.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/jquery-ui/ui/widgets/datepicker.js')}}"></script>
    <script>
        $(function() {
            $('.select2').select2({
                placeholder: 'اختر...',
                width: '100%',
                dir: "rtl" // إذا كانت الواجهة بالعربية
            });

            // تهيئة Datepicker
            $('#payment_date').datepicker({
                dateFormat: 'yy-mm-dd',
                // يمكنك إضافة خيارات أخرى هنا مثل minDate, maxDate, etc.
            });

            // تحديد تاريخ اليوم كقيمة افتراضية عند الإضافة إذا لم يكن هناك قيمة
            // هذا الجزء مهم ليظهر التاريخ الافتراضي في حقل التاريخ
            if (!$("#payment_date").val()) { // تحقق مما إذا كان الحقل فارغًا
                var today = new Date();
                var dd = String(today.getDate()).padStart(2, '0');
                var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
                var yyyy = today.getFullYear();
                today = yyyy + '-' + mm + '-' + dd;
                $("#payment_date").val(today);
            }
        });
    </script>
@endsection