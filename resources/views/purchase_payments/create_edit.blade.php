@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title')
    {{ $purchasePayment->exists ? 'تعديل دفعة شراء' : 'إضافة دفعة شراء جديدة' }} - ناينوكس
@stop

@section('css')
    {{-- أصول CSS من قالب ناينوكس، بما في ذلك Select2 و jQuery UI Datepicker --}}
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/fileuploads/css/fileupload.css')}}" rel="stylesheet" type="text/css" />
    <link href="{{URL::asset('assets/plugins/fancyuploder/fancy_fileupload.css')}}" rel="stylesheet" />
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css')}}">
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/telephoneinput/telephoneinput-rtl.css')}}">
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
    {{-- <link href="{{URL::asset('assets/plugins/jquery.sumoselect/sumoselect.css')}}" rel="stylesheet"> هذا قد يكون مكرراً أو غير ضروري إذا كان sumoselect-rtl.css كافياً --}}
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">مدفوعات الشراء</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ $purchasePayment->exists ? 'تعديل دفعة شراء' : 'إضافة دفعة شراء جديدة' }}</span>
            </div>
        </div>
    </div>
@endsection

@section('content')
    {{-- رسائل النجاح والفشل --}}
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
    {{-- رسائل الأخطاء من التحقق --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>عفواً!</strong> هناك بعض المشاكل في إدخالك.
            <ul class="mt-2 mb-0">
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
                    <form action="{{ $purchasePayment->exists ? route('purchase_payments.update', $purchasePayment->id) : route('purchase_payments.store') }}" method="post" autocomplete="off">
                        @csrf
                        @if($purchasePayment->exists)
                            @method('PUT')
                        @endif

                        <div class="row">
                            {{-- فاتورة الشراء --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="purchase_invoice_id">فاتورة الشراء <span class="text-danger">*</span></label>
                                    <select name="purchase_invoice_id" id="purchase_invoice_id" class="form-control select2 @error('purchase_invoice_id') is-invalid @enderror" required>
                                        <option value="">اختر فاتورة</option>
                                        @foreach($purchaseInvoices as $invoice)
                                            <option value="{{ $invoice->id }}"
                                                {{-- تحديد القيمة القديمة من old() أو من كائن الدفعة الحالي --}}
                                                {{ old('purchase_invoice_id', $purchasePayment->purchase_invoice_id) == $invoice->id ? 'selected' : '' }}
                                                {{-- تحديد القيمة من URL عند الإنشاء فقط --}}
                                                @if(!$purchasePayment->exists && isset($selectedInvoiceId) && $selectedInvoiceId == $invoice->id)
                                                    selected
                                                @endif
                                            >
                                                {{ $invoice->invoice_number }} (المورد: {{ $invoice->supplier->name ?? 'غير معروف' }}) (المستحق: {{ number_format($invoice->due_amount, 2) }})
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('purchase_invoice_id')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            {{-- تاريخ الدفع --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_date">تاريخ الدفع <span class="text-danger">*</span></label>
                                    {{-- استخدام fc-datepicker لتفعيل jQuery UI Datepicker --}}
                                    <input type="text" class="form-control fc-datepicker @error('payment_date') is-invalid @enderror" id="payment_date" name="payment_date"
                                        value="{{ old('payment_date', $purchasePayment->payment_date ? $purchasePayment->payment_date->format('Y-m-d') : \Carbon\Carbon::now()->format('Y-m-d')) }}" required>
                                    @error('payment_date')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- المبلغ --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="amount">المبلغ <span class="text-danger">*</span></label>
                                    <input type="number" name="amount" id="amount" class="form-control @error('amount') is-invalid @enderror"
                                        min="0.01" step="0.01" value="{{ old('amount', $purchasePayment->amount) }}" required>
                                    @error('amount')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                            {{-- طريقة الدفع --}}
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="payment_method">طريقة الدفع <span class="text-danger">*</span></label>
                                    <select name="payment_method" id="payment_method" class="form-control select2 @error('payment_method') is-invalid @enderror" required>
                                        <option value="">اختر طريقة دفع</option>
                                        @foreach($paymentMethods as $method)
                                            <option value="{{ $method }}"
                                                {{ old('payment_method', $purchasePayment->payment_method) == $method ? 'selected' : '' }}>
                                                {{ (new \App\PurchasePayment(['payment_method' => $method]))->payment_method_name }}
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
                            {{-- المرجع --}}
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="reference">المرجع (رقم الشيك/التحويل) (اختياري)</label>
                                    <input type="text" name="reference" id="reference" class="form-control @error('reference') is-invalid @enderror"
                                        value="{{ old('reference', $purchasePayment->reference) }}">
                                    @error('reference')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- ملاحظات --}}
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="notes">ملاحظات (اختياري)</label>
                                    <textarea name="notes" id="notes" rows="3" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $purchasePayment->notes) }}</textarea>
                                    @error('notes')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save ml-1"></i>
                                {{ $purchasePayment->exists ? 'تحديث الدفعة' : 'حفظ الدفعة' }}
                            </button>
                            <a href="{{ route('purchase_payments.index') }}" class="btn btn-secondary mr-2">
                                <i class="fas fa-times ml-1"></i> إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    {{-- أصول JS من قالب ناينوكس، بما في ذلك Select2 و jQuery UI Datepicker --}}
    <script src="{{URL::asset('assets/plugins/select2/js/select2.min.js')}}"></script>
    <script src="{{URL::asset('assets/js/form-validation.js')}}"></script> {{-- قد تحتاج هذا إذا كان لديك ملف تحقق من النموذج --}}
    <script src="{{URL::asset('assets/plugins/jquery-ui/ui/widgets/datepicker.js')}}"></script>
    <script>
        $(function() {
            // تهيئة Select2
            $('.select2').select2({
                placeholder: 'اختر...',
                width: '100%',
                dir: "rtl" // إذا كانت الواجهة بالعربية
            });

            // تهيئة Datepicker
            $('.fc-datepicker').datepicker({ // استخدم fc-datepicker كما في القالب الأصلي
                dateFormat: 'yy-mm-dd',
                // يمكنك إضافة خيارات أخرى هنا مثل minDate, maxDate, etc.
            });

            // تحديد تاريخ اليوم كقيمة افتراضية عند الإضافة إذا لم يكن هناك قيمة
            // هذا الجزء مهم ليظهر التاريخ الافتراضي في حقل التاريخ عند إنشاء دفعة جديدة
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
