@extends('layouts.master')


@section('content')
<div class="container py-4">
    <h1 class="mb-4 h3">مدفوعات فواتير الشراء</h1>

    {{-- رسائل الفلاش --}}
    @if (session('success'))
        <div class="alert alert-success">
            <strong>نجاح!</strong> {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">
            <strong>خطأ!</strong> {{ session('error') }}
        </div>
    @endif

    {{-- فلاتر البحث --}}
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('purchase_payments.index') }}" method="GET">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="purchase_invoice_id">رقم فاتورة الشراء</label>
                        <select name="purchase_invoice_id" id="purchase_invoice_id" class="form-control">
                            <option value="">كل الفواتير</option>
                            @foreach($purchaseInvoices as $invoice)
                                <option value="{{ $invoice->id }}" {{ request('purchase_invoice_id') == $invoice->id ? 'selected' : '' }}>
                                    {{ $invoice->invoice_number }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="payment_method">طريقة الدفع</label>
                        <select name="payment_method" id="payment_method" class="form-control">
                            <option value="">كل الطرق</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" {{ request('payment_method') == $method ? 'selected' : '' }}>
                                    {{ (new \App\PurchasePayment(['payment_method' => $method]))->payment_method_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label for="start_date">من تاريخ الدفع</label>
                        <input type="date" name="start_date" id="start_date" value="{{ request('start_date') }}" class="form-control">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="end_date">إلى تاريخ الدفع</label>
                        <input type="date" name="end_date" id="end_date" value="{{ request('end_date') }}" class="form-control">
                    </div>
                </div>
                <div class="form-group d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter ml-1"></i> فلترة
                    </button>
                    <a href="{{ route('purchase_payments.index') }}" class="btn btn-secondary ml-2">
                        <i class="fas fa-redo ml-1"></i> مسح الفلاتر
                    </a>
                </div>
            </form>
        </div>
    </div>

    {{-- زر إضافة --}}
    <div class="mb-3 text-right">
        <a href="{{ route('purchase_payments.create') }}" class="btn btn-success">
            <i class="fas fa-plus ml-1"></i> إضافة دفعة شراء جديدة
        </a>
    </div>

    {{-- جدول البيانات --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-bordered table-hover text-center">
                <thead class="thead-light">
                    <tr>
                        <th>رقم فاتورة الشراء</th>
                        <th>المورد</th>
                        <th>المبلغ</th>
                        <th>تاريخ الدفع</th>
                        <th>طريقة الدفع</th>
                        <th>المرجع</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchasePayments as $payment)
                        <tr>
                            <td>{{ $payment->purchaseInvoice->invoice_number ?? 'N/A' }}</td>
                            <td>{{ $payment->purchaseInvoice->supplier->name ?? 'N/A' }}</td>
                            <td>{{ number_format($payment->amount, 2) }}</td>
                            <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                            <td>{{ $payment->payment_method_name }}</td>
                            <td>{{ $payment->reference ?? 'لا يوجد' }}</td>
                            <td>
                                <a href="{{ route('purchase_payments.show', $payment->id) }}" class="btn btn-sm btn-info">عرض</a>
                                <a href="{{ route('purchase_payments.edit', $payment->id) }}" class="btn btn-sm btn-primary">تعديل</a>
                                <form action="{{ route('purchase_payments.destroy', $payment->id) }}" method="POST" class="d-inline-block" onsubmit="return confirm('هل أنت متأكد من حذف هذه الدفعة؟ هذا الإجراء لا يمكن التراجع عنه وسيقوم بتعديل مبالغ الفاتورة المرتبطة.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted">لا توجد مدفوعات فواتير شراء لعرضها.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- روابط التنقل --}}
        <div class="card-footer">
            {{ $purchasePayments->links() }}
        </div>
    </div>
</div>
@endsection
