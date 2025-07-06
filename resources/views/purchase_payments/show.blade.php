@extends('layouts.master')

@section('content')
<div class="container py-4">
    <h1 class="h3 mb-4">تفاصيل دفعة الشراء</h1>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6 mb-3">
                    <strong>فاتورة الشراء:</strong>
                    <p class="mb-0">
                        @if($purchasePayment->purchaseInvoice)
                            <a href="{{ route('purchase_invoices.show', $purchasePayment->purchaseInvoice->id) }}" class="text-primary">
                                {{ $purchasePayment->purchaseInvoice->invoice_number }}
                            </a>
                            (المورد: {{ $purchasePayment->purchaseInvoice->supplier->name ?? 'غير معروف' }})
                        @else
                            غير مرتبطة بفاتورة
                        @endif
                    </p>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>المبلغ المدفوع:</strong>
                    <p class="mb-0">{{ number_format($purchasePayment->amount, 2) }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>تاريخ الدفع:</strong>
                    <p class="mb-0">{{ $purchasePayment->payment_date->format('Y-m-d') }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>طريقة الدفع:</strong>
                    <p class="mb-0">{{ $purchasePayment->payment_method_name }}</p>
                </div>

                <div class="col-md-12 mb-3">
                    <strong>المرجع:</strong>
                    <p class="mb-0">{{ $purchasePayment->reference ?? 'لا يوجد' }}</p>
                </div>

                <div class="col-md-12 mb-3">
                    <strong>ملاحظات:</strong>
                    <p class="mb-0">{{ $purchasePayment->notes ?? 'لا يوجد' }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>تاريخ الإنشاء:</strong>
                    <p class="mb-0">{{ $purchasePayment->created_at->format('Y-m-d H:i') }}</p>
                </div>

                <div class="col-md-6 mb-3">
                    <strong>آخر تحديث:</strong>
                    <p class="mb-0">{{ $purchasePayment->updated_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>

            <div class="text-right mt-4">
                <a href="{{ route('purchase_payments.edit', $purchasePayment->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit ml-1"></i> تعديل الدفعة
                </a>
                <a href="{{ route('purchase_payments.index') }}" class="btn btn-secondary ml-2">
                    <i class="fas fa-arrow-left ml-1"></i> العودة إلى القائمة
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
