@extends('layouts.master')

@section('title')
    {{ isset($purchaseInvoice->id) ? 'تعديل فاتورة شراء' : 'إنشاء فاتورة شراء' }} - ناينوكس
@stop

@section('content')
<div class="container py-6">
    <h1 class="mb-4">{{ isset($purchaseInvoice->id) ? 'تعديل فاتورة شراء' : 'إنشاء فاتورة شراء جديدة' }}</h1>

    {{-- رسائل الخطأ --}}
    @if ($errors->any())
    <div class="alert alert-danger">
        <strong>عفواً!</strong> هناك بعض المشاكل في الإدخال.
        <ul class="mt-2 mb-0">
            @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ isset($purchaseInvoice->id) ? route('purchase_invoices.update', $purchaseInvoice->id) : route('purchase_invoices.store') }}" method="POST">
        @csrf
        @if(isset($purchaseInvoice->id)) @method('PUT') @endif

        <div class="row">
            <div class="form-group col-md-6">
                <label>رقم الفاتورة</label>
                <input type="text" name="invoice_number" value="{{ old('invoice_number', $purchaseInvoice->invoice_number ?? $newInvoiceNumber ?? '') }}"
                    class="form-control" {{ isset($purchaseInvoice->id) ? 'readonly' : '' }} required>
            </div>

            <div class="form-group col-md-6">
                <label>المورد</label>
                <select name="supplier_id" class="form-control" required>
                    <option value="">اختر مورد</option>
                    @foreach($suppliers as $sup)
                    <option value="{{ $sup->id }}" {{ old('supplier_id', $purchaseInvoice->supplier_id ?? '') == $sup->id ? 'selected' : '' }}>
                        {{ $sup->name }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-6">
                <label>تاريخ الإصدار</label>
                <input type="date" name="issue_date" value="{{ old('issue_date', $purchaseInvoice->issue_date_formatted ?? '') }}" class="form-control" required>
            </div>

            <div class="form-group col-md-6">
                <label>تاريخ الاستحقاق (اختياري)</label>
                <input type="date" name="due_date" value="{{ old('due_date', $purchaseInvoice->due_date_formatted ?? '') }}" class="form-control">
            </div>

            <div class="form-group col-md-4">
                <label>الخصم</label>
                <input type="number" name="discount" id="discount" value="{{ old('discount', $purchaseInvoice->discount ?? 0) }}"
                    class="form-control" min="0" step="0.01" oninput="calculateTotals()">
            </div>

            <div class="form-group col-md-4">
                <label>الضريبة</label>
                <input type="number" name="tax" id="tax" value="{{ old('tax', $purchaseInvoice->tax ?? 0) }}"
                    class="form-control" min="0" step="0.01" oninput="calculateTotals()">
            </div>

            <div class="form-group col-md-4">
                <label>المبلغ المدفوع</label>
                <input type="number" name="paid_amount" id="paid_amount" value="{{ old('paid_amount', $purchaseInvoice->paid_amount ?? 0) }}"
                    class="form-control" min="0" step="0.01" oninput="calculateTotals()">
            </div>

            <div class="form-group col-md-6">
                <label>الحالة</label>
                <select name="status" id="status" class="form-control" required>
                    <option value="unpaid" {{ old('status', $purchaseInvoice->status ?? '') == 'unpaid' ? 'selected' : '' }}>غير مدفوعة</option>
                    <option value="partial" {{ old('status', $purchaseInvoice->status ?? '') == 'partial' ? 'selected' : '' }}>مدفوعة جزئياً</option>
                    <option value="paid" {{ old('status', $purchaseInvoice->status ?? '') == 'paid' ? 'selected' : '' }}>مدفوعة بالكامل</option>
                </select>
            </div>
        </div>

        {{-- بنود الفاتورة --}}
        <h5 class="mt-4">بنود الفاتورة</h5>
        <div id="items-container">
            @forelse(old('items', $purchaseInvoiceItems ?? []) as $i => $item)
            <div class="card mb-3 item-row position-relative p-3">
                <button type="button" class="close position-absolute" style="right:10px;top:10px" onclick="removeItem(this)">&times;</button>
                <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id ?? '' }}">
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>المادة</label>
                        <select name="items[{{ $i }}][material_id]" class="form-control material-select" onchange="updateUnitPrice(this)" required>
                            <option value="">اختر مادة</option>
                            @foreach($materials as $mat)
                            <option value="{{ $mat->id }}" data-price="{{ $mat->purchase_price }}"
                                {{ old("items.$i.material_id", $item->material_id) == $mat->id ? 'selected' : '' }}>
                                {{ $mat->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>الكمية</label>
                        <input type="number" name="items[{{ $i }}][quantity]" class="form-control qty-input" min="1" step="1"
                            value="{{ old("items.$i.quantity", $item->quantity) }}" oninput="calculateItem(this)" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>سعر الوحدة</label>
                        <input type="number" name="items[{{ $i }}][unit_price]" class="form-control price-input" min="0.01" step="0.01"
                            value="{{ old("items.$i.unit_price", $item->unit_price) }}" oninput="calculateItem(this)" required>
                    </div>
                    <div class="form-group col-md-3">
                        <label>الإجمالي</label>
                        <input type="number" name="items[{{ $i }}][total]" class="form-control total-input" readonly
                            value="{{ old("items.$i.total", $item->total) }}">
                    </div>
                </div>
            </div>
            @empty
            {{-- سيتم ملؤها عبر JS --}}
            @endforelse
        </div>

        <button type="button" class="btn btn-outline-primary mb-3" onclick="addItem()">+ إضافة بند</button>

        {{-- ملخص الإجماليات --}}
        <div class="card p-3 mb-4">
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>المجموع الفرعي</label>
                    <input type="number" id="subtotal_display" class="form-control" readonly>
                    <input type="hidden" name="subtotal" id="subtotal_hidden">
                </div>
                <div class="form-group col-md-4">
                    <label>الإجمالي الكلي</label>
                    <input type="number" id="total_display" class="form-control" readonly>
                    <input type="hidden" name="total" id="total_hidden">
                </div>
                <div class="form-group col-md-4">
                    <label>المبلغ المستحق</label>
                    <input type="number" id="due_display" class="form-control" readonly>
                    <input type="hidden" name="due_amount" id="due_hidden">
                </div>
            </div>
        </div>

        {{-- الملاحظات --}}
        <div class="form-group mb-4">
            <label>ملاحظات</label>
            <textarea name="notes" class="form-control">{{ old('notes', $purchaseInvoice->notes ?? '') }}</textarea>
        </div>

        <div class="text-right">
            <button type="submit" class="btn btn-success">{{ isset($purchaseInvoice->id) ? 'تحديث الفاتورة' : 'إنشاء الفاتورة' }}</button>
            <a href="{{ route('purchase_invoices.index') }}" class="btn btn-secondary">إلغاء</a>
        </div>
    </form>
</div>

<script>
let itemIndex = {{ old('items', $purchaseInvoiceItems ?? [])->count() ?? 0 }};

document.addEventListener('DOMContentLoaded', () => {
    if(itemIndex === 0) addItem();
    calculateTotals();
});

function addItem() {
    const i = itemIndex++;
    const container = document.getElementById('items-container');
    const row = document.createElement('div');
    row.className = 'card mb-3 item-row position-relative p-3';
    row.innerHTML = `
      <button type="button" class="close position-absolute" style="right:10px;top:10px" onclick="removeItem(this)">&times;</button>
      <input type="hidden" name="items[${i}][id]">
      <div class="form-row">
        <div class="form-group col-md-4">
          <label>المادة</label>
          <select name="items[${i}][material_id]" class="form-control material-select" onchange="updateUnitPrice(this)" required>
            <option value="">اختر مادة</option>
            @foreach($materials as $mat)
            <option value="{{ $mat->id }}" data-price="{{ $mat->purchase_price }}">{{ $mat->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-2">
          <label>الكمية</label>
          <input type="number" name="items[${i}][quantity]" class="form-control qty-input" value="1" min="1" oninput="calculateItem(this)" required>
        </div>
        <div class="form-group col-md-3">
          <label>سعر الوحدة</label>
          <input type="number" name="items[${i}][unit_price]" class="form-control price-input" value="0.00" min="0.01" step="0.01" oninput="calculateItem(this)" required>
        </div>
        <div class="form-group col-md-3">
          <label>الإجمالي</label>
          <input type="number" name="items[${i}][total]" class="form-control total-input" readonly value="0.00">
        </div>
      </div>`;
    container.appendChild(row);
}

function removeItem(btn){
    const container = document.getElementById('items-container');
    if(container.children.length > 1){
        btn.closest('.item-row').remove();
        calculateTotals();
    } else alert('يجب إضافة بند واحد على الأقل.');
}

function updateUnitPrice(sel){
    const price = sel.selectedOptions[0].dataset.price || 0;
    const row = sel.closest('.item-row');
    row.querySelector('.price-input').value = parseFloat(price).toFixed(2);
    calculateItem(row.querySelector('.price-input'));
}

function calculateItem(el){
    const row = el.closest('.item-row');
    const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value) || 0;
    const tot = qty * price;
    row.querySelector('.total-input').value = tot.toFixed(2);
    calculateTotals();
}

function calculateTotals(){
    let subtotal = 0;
    document.querySelectorAll('.total-input').forEach(i=>subtotal += parseFloat(i.value)||0);
    const disc = parseFloat(document.getElementById('discount').value)||0;
    const tax = parseFloat(document.getElementById('tax').value)||0;
    const paid = parseFloat(document.getElementById('paid_amount').value)||0;
    const total = subtotal - disc + tax;
    const due = total - paid;

    document.getElementById('subtotal_display').value = subtotal.toFixed(2);
    document.getElementById('subtotal_hidden').value = subtotal.toFixed(2);
    document.getElementById('total_display').value = total.toFixed(2);
    document.getElementById('total_hidden').value = total.toFixed(2);
    document.getElementById('due_display').value = due.toFixed(2);
    document.getElementById('due_hidden').value = due.toFixed(2);

    const st = document.getElementById('status');
    if(paid >= total) st.value = 'paid';
    else if(paid > 0) st.value = 'partial';
    else st.value = 'unpaid';
}
</script>
@endsection
