@extends('layouts.master')

@section('title')
   فاتورة مبيعات - ناينوكس
@stop

@section('css')
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.min.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css')}}">
    <link href="{{URL::asset('assets/plugins/notify/css/notifIt.css')}}" rel="stylesheet"/>
@endsection
@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">الفواتير</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ isset($invoice->id) ? 'تعديل فاتورة' : 'إضافة فاتورة جديدة' }}</span>
            </div>
        </div>
    </div>
    @endsection
@section('content')

    @if (session()->has('success'))
        <script>
            window.onload = function() {
                notif({
                    msg: "{{ session()->get('success') }}",
                    type: "success"
                });
            }
        </script>
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
                    <form action="{{ isset($invoice->id) ? route('invoices.update', $invoice->id) : route('invoices.store') }}" method="post" autocomplete="off">
                        @csrf
                        @if (isset($invoice->id))
                            {{ method_field('patch') }}
                        @endif

                        <div class="row">
                           <div class="form-group">
                                <label for="invoice_number">رقم الفاتورة</label>
                                <input type="text" name="invoice_number" id="invoice_number" 
                                    value="{{ old('invoice_number', $invoice->invoice_number ?? $newInvoiceNumber ?? '') }}" 
                                    class="form-control" readonly>
                            </div>
                            <div class="col-lg-4 form-group">
                                <label for="issue_date">تاريخ الإصدار <span class="text-danger">*</span></label>
                                <input class="form-control fc-datepicker" name="issue_date" placeholder="YYYY-MM-DD" type="text"
                                    value="{{ old('issue_date', $invoice->issue_date ? $invoice->issue_date->format('Y-m-d') : date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-lg-4 form-group">
                                <label for="due_date">تاريخ الاستحقاق</label>
                                <input class="form-control fc-datepicker" name="due_date" placeholder="YYYY-MM-DD" type="text"
                                    value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 form-group">
                                <label for="client_id">العميل <span class="text-danger">*</span></label>
                                <select name="client_id" id="client_id" class="form-control select2" required>
                                    <option value="">اختر عميل</option>
                                    @foreach ($clients as $client)
                                        <option value="{{ $client->id }}" {{ old('client_id', $invoice->client_id) == $client->id ? 'selected' : '' }}>
                                            {{ $client->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 form-group">
                                <label for="project_id">المشروع <span class="text-danger">*</span></label>
                                <select name="project_id" id="project_id" class="form-control select2" required>
                                    <option value="">اختر مشروع</option>
                                    @foreach ($projects as $project)
                                        <option value="{{ $project->id }}" {{ old('project_id', $invoice->project_id) == $project->id ? 'selected' : '' }}>
                                            {{ $project->project_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <h5>بنود الفاتورة</h5>
                                <table class="table table-bordered" id="invoice_items_table">
                                    <thead>
                                        <tr>
                                            <th>الوصف <span class="text-danger">*</span></th>
                                            <th>الكمية <span class="text-danger">*</span></th>
                                            <th>سعر الوحدة <span class="text-danger">*</span></th>
                                            <th>الإجمالي</th>
                                            <th>العمليات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if (isset($invoiceItems) && $invoiceItems->count() > 0)
                                            @foreach ($invoiceItems as $item)
                                                <tr>
                                                    <td>
                                                        {{-- إذا كان التعديل، يجب أن نمرر ID البند لتحديثه --}}
                                                        <input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}">
                                                        <input type="text" name="items[{{ $loop->index }}][description]" class="form-control item-description" value="{{ old('items.'.$loop->index.'.description', $item->description) }}" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="items[{{ $loop->index }}][quantity]" class="form-control item-quantity" value="{{ old('items.'.$loop->index.'.quantity', $item->quantity) }}" min="1" required>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="items[{{ $loop->index }}][unit_price]" class="form-control item-unit-price" value="{{ old('items.'.$loop->index.'.unit_price', $item->unit_price) }}" step="0.01" min="0.01" required>
                                                    </td>
                                                    <td>
                                                        <input type="text" name="items[{{ $loop->index }}][total]" class="form-control item-total" value="{{ number_format(old('items.'.$loop->index.'.total', $item->total), 2) }}" readonly>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-danger btn-sm remove-item-row">حذف</button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @endif
                                        {{-- سنزيل الصف الافتراضي هنا وسنضيفه بالـ JS --}}
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <td colspan="5" class="text-left">
                                                <button type="button" class="btn btn-success btn-sm" id="add_item_row">إضافة بند جديد</button>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-lg-4 form-group">
                                <label for="subtotal">الإجمالي الفرعي (قبل الخصم والضريبة)</label>
                                <input type="text" class="form-control" id="subtotal" name="subtotal_display" value="{{ number_format(old('subtotal', $invoice->subtotal ?? 0), 2) }}" readonly>
                                <input type="hidden" name="subtotal" value="{{ old('subtotal', $invoice->subtotal ?? 0) }}">
                            </div>
                            <div class="col-lg-4 form-group">
                                <label for="discount">الخصم (قيمة أو نسبة)</label>
                                <input type="number" class="form-control" id="discount" name="discount"
                                    value="{{ old('discount', $invoice->discount ?? 0) }}" min="0" step="0.01">
                            </div>
                            <div class="col-lg-4 form-group">
                                <label for="tax">الضريبة (قيمة أو نسبة)</label>
                                <input type="number" class="form-control" id="tax" name="tax"
                                    value="{{ old('tax', $invoice->tax ?? 0) }}" min="0" step="0.01">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-4 form-group">
                                <label for="total">الإجمالي الكلي</label>
                                <input type="text" class="form-control" id="total" name="total_display" value="{{ number_format(old('total', $invoice->total ?? 0), 2) }}" readonly>
                                <input type="hidden" name="total" value="{{ old('total', $invoice->total ?? 0) }}">
                            </div>
                            <div class="col-lg-4 form-group">
                                <label for="paid_amount">المبلغ المدفوع</label>
                                <input type="number" class="form-control" id="paid_amount" name="paid_amount"
                                    value="{{ old('paid_amount', $invoice->paid_amount ?? 0) }}" min="0" step="0.01">
                            </div>
                            <div class="col-lg-4 form-group">
                                <label for="due_amount">المبلغ المستحق</label>
                                <input type="text" class="form-control" id="due_amount" name="due_amount_display" value="{{ number_format(old('due_amount', $invoice->due_amount ?? 0), 2) }}" readonly>
                                <input type="hidden" name="due_amount" value="{{ old('due_amount', $invoice->due_amount ?? 0) }}">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-lg-6 form-group">
                                <label for="payment_method">طريقة الدفع</label>
                                <select name="payment_method" id="payment_method" class="form-control select2">
                                    <option value="">اختر طريقة دفع</option>
                                    @foreach (['cash', 'bank_transfer', 'cheque', 'card', 'other'] as $method)
                                        <option value="{{ $method }}" {{ old('payment_method', $invoice->payment_method) == $method ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $method)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 form-group">
                                <label for="status">حالة الفاتورة</label>
                                <select name="status" id="status" class="form-control select2">
                                    <option value="unpaid" {{ old('status', $invoice->status) == 'unpaid' ? 'selected' : '' }}>غير مدفوعة</option>
                                    <option value="partial" {{ old('status', $invoice->status) == 'partial' ? 'selected' : '' }}>مدفوعة جزئياً</option>
                                    <option value="paid" {{ old('status', $invoice->status) == 'paid' ? 'selected' : '' }}>مدفوعة</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="notes">ملاحظات</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $invoice->notes) }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">{{ isset($invoice->id) ? 'تحديث الفاتورة' : 'حفظ الفاتورة' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection
@section('js')
    <script src="{{URL::asset('assets/plugins/select2/js/select2.min.js')}}"></script>
    <script src="{{URL::asset('assets/js/form-select2.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/jquery-ui/ui/widgets/datepicker.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/sumoselect/jquery.sumoselect.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/notify/js/notifIt.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/notify/js/notifit-custom.js')}}"></script>

    <script>
        $(document).ready(function() {
            // تهيئة Datepicker
            $('.fc-datepicker').datepicker({
                dateFormat: 'yy-mm-dd',
                showOtherMonths: true,
                selectOtherMonths: true
            });

            // تهيئة Select2
            $('.select2').select2({
                placeholder: 'اختر...',
                width: '100%'
            });

            // تحديد itemIndex بناءً على عدد الصفوف الموجودة بالفعل (لتعديل الفاتورة)
            // أو البدء من 0 إذا كانت فاتورة جديدة
            let itemIndex = $('#invoice_items_table tbody tr').length;

            // دالة لإضافة صف جديد لبند الفاتورة
            function addItemRow(itemData = {}) {
                const idInput = itemData.id ? `<input type="hidden" name="items[${itemIndex}][id]" value="${itemData.id}">` : '';
                const description = itemData.description || '';
                const quantity = itemData.quantity || 1;
                const unitPrice = itemData.unit_price || 0.00;
                const total = itemData.total || 0.00;

                const newRow = `
                    <tr>
                        <td>
                            ${idInput}
                            <input type="text" name="items[${itemIndex}][description]" class="form-control item-description" value="${description}" required>
                        </td>
                        <td>
                            <input type="number" name="items[${itemIndex}][quantity]" class="form-control item-quantity" value="${quantity}" min="1" required>
                        </td>
                        <td>
                            <input type="number" name="items[${itemIndex}][unit_price]" class="form-control item-unit-price" value="${unitPrice}" step="0.01" min="0.01" required>
                        </td>
                        <td>
                            <input type="text" name="items[${itemIndex}][total]" class="form-control item-total" value="${total.toFixed(2)}" readonly>
                        </td>
                        <td>
                            <button type="button" class="btn btn-danger btn-sm remove-item-row">حذف</button>
                        </td>
                    </tr>
                `;
                $('#invoice_items_table tbody').append(newRow);
                itemIndex++; // زيادة الـ index لكل صف جديد

                // تمكين/تعطيل زر الحذف
                updateRemoveButtonsStatus();
            }

            // دالة لتحديث حالة أزرار الحذف (تعطيل زر الحذف إذا كان هناك صف واحد فقط)
            function updateRemoveButtonsStatus() {
                if ($('#invoice_items_table tbody tr').length === 1) {
                    $('#invoice_items_table tbody tr:first .remove-item-row').prop('disabled', true);
                } else {
                    $('#invoice_items_table tbody tr .remove-item-row').prop('disabled', false);
                }
            }

            // دالة لإعادة ترقيم حقول البنود بعد الحذف
            function reindexItems() {
                $('#invoice_items_table tbody tr').each(function(index) {
                    $(this).find('[name^="items["]').each(function() {
                        const currentName = $(this).attr('name');
                        // نستخدم regex أكثر دقة للتأكد من استبدال الـ index فقط
                        const newName = currentName.replace(/items\[\d+\]/, `items[${index}]`);
                        $(this).attr('name', newName);
                    });
                });
                itemIndex = $('#invoice_items_table tbody tr').length; // تحديث itemIndex بعد إعادة الترقيم
            }

            // دالة لحساب إجمالي البند الواحد
            function calculateItemTotal(row) {
                const quantity = parseFloat(row.find('.item-quantity').val());
                const unitPrice = parseFloat(row.find('.item-unit-price').val());
                let total = 0;
                if (!isNaN(quantity) && !isNaN(unitPrice)) {
                    total = (quantity * unitPrice);
                }
                row.find('.item-total').val(total.toFixed(2));
            }

            // دالة لحساب الإجماليات الكلية للفاتورة
            function calculateInvoiceTotals() {
                let subtotal = 0;
                $('#invoice_items_table tbody tr').each(function() {
                    const itemTotal = parseFloat($(this).find('.item-total').val());
                    if (!isNaN(itemTotal)) {
                        subtotal += itemTotal;
                    }
                });
                $('#subtotal').val(subtotal.toFixed(2));
                $('input[name="subtotal"]').val(subtotal.toFixed(2));

                const discount = parseFloat($('#discount').val()) || 0;
                const tax = parseFloat($('#tax').val()) || 0;

                let total = (subtotal - discount) + tax;
                $('#total').val(total.toFixed(2));
                $('input[name="total"]').val(total.toFixed(2));

                const paidAmount = parseFloat($('#paid_amount').val()) || 0;
                let dueAmount = (total - paidAmount);
                $('#due_amount').val(dueAmount.toFixed(2));
                $('input[name="due_amount"]').val(dueAmount.toFixed(2));

                const statusSelect = $('#status');
                if (paidAmount >= total && total > 0) { // أضفنا total > 0 لتجنب حالة 0 = 0
                    statusSelect.val('paid').trigger('change');
                } else if (paidAmount > 0 && paidAmount < total) {
                    statusSelect.val('partial').trigger('change');
                } else {
                    statusSelect.val('unpaid').trigger('change');
                }
            }

            // عند تغيير الكمية أو سعر الوحدة لأي بند
            $('#invoice_items_table').on('input', '.item-quantity, .item-unit-price', function() {
                calculateItemTotal($(this).closest('tr'));
                calculateInvoiceTotals();
            });

            // عند تغيير الخصم أو الضريبة أو المبلغ المدفوع
            $('#discount, #tax, #paid_amount').on('input', function() {
                calculateInvoiceTotals();
            });

            // إضافة صف جديد لبند الفاتورة عند النقر على الزر
            $('#add_item_row').on('click', function() {
                addItemRow();
                calculateInvoiceTotals();
            });

            // حذف صف بند الفاتورة
            $('#invoice_items_table').on('click', '.remove-item-row', function() {
                if ($('#invoice_items_table tbody tr').length > 1) { // لا تحذف آخر صف
                    $(this).closest('tr').remove();
                    reindexItems(); // إعادة ترقيم الحقول
                    calculateInvoiceTotals(); // إعادة حساب الإجماليات بعد الحذف
                }
                updateRemoveButtonsStatus(); // تحديث حالة أزرار الحذف
            });

            // عند تحميل الصفحة
            // إذا كانت فاتورة جديدة ولا توجد بنود، أضف صفاً واحداً افتراضياً
            if (itemIndex === 0 && !{{ isset($invoice->id) ? 'true' : 'false' }}) {
                addItemRow();
            } else {
                // في وضع التعديل، تأكد من إعادة حساب الإجماليات
                // أو في وضع الإنشاء إذا كان هناك old input
                calculateInvoiceTotals();
            }
            updateRemoveButtonsStatus(); // تحديث حالة أزرار الحذف عند التحميل

            // توليد رقم الفاتورة تلقائيا عند إنشاء فاتورة جديدة فقط
            @if (!isset($invoice->id))
                if ($('#invoice_number').val() === '') { // فقط إذا كان حقل رقم الفاتورة فارغاً
                    $.ajax({
                        url: "{{ route('invoices.generate_number') }}",
                        method: 'GET',
                        success: function(response) {
                            $('#invoice_number').val(response);
                        },
                        error: function(xhr, status, error) {
                            console.error("Error generating invoice number:", error);
                        }
                    });
                }
            @endif
        });
    </script>
@endsection