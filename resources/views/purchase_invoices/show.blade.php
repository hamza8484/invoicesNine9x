{{-- resources/views/purchase_invoices/show.blade.php --}}
@extends('layouts.master') {{-- تم التغيير إلى layouts.master --}}

@section('title')
    عرض فاتورة الشراء - ناينوكس
@stop

@section('css')
    {{-- أصول CSS من قالب ناينوكس --}}
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.min.css')}}" rel="stylesheet">
    <link rel="stylesheet" href="{{URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css')}}">
    <link href="{{URL::asset('assets/plugins/notify/css/notifIt.css')}}" rel="stylesheet"/>
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css')}}" rel="stylesheet">
    {{-- أضف أي CSS إضافي لـ Font Awesome إذا لم يكن مضمناً في master --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">فواتير الشراء</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تفاصيل فاتورة الشراء: {{ $purchaseInvoice->invoice_number }}</span>
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

    @if (session()->has('error'))
        <script>
            window.onload = function() {
                notif({
                    msg: "{{ session()->get('error') }}",
                    type: "error"
                });
            }
        </script>
    @endif

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between">
                        <h4 class="card-title mg-b-0">تفاصيل فاتورة الشراء #{{ $purchaseInvoice->invoice_number }}</h4>
                        <div class="d-flex">
                            <a href="{{ route('purchase_invoices.edit', $purchaseInvoice->id) }}" class="btn btn-sm btn-primary mr-2">
                                <i class="las la-pen"></i> تعديل
                            </a>
                            {{-- يمكنك إضافة زر طباعة إذا كان لديك مسار طباعة لفواتير الشراء --}}
                            {{-- <a href="{{ route('purchase_invoices.print', $purchaseInvoice->id) }}" class="btn btn-sm btn-secondary mr-2" target="_blank">
                                <i class="las la-print"></i> طباعة
                            </a> --}}
                            <a href="#" class="btn btn-sm btn-danger" data-invoice_id="{{ $purchaseInvoice->id }}"
                                data-toggle="modal" data-target="#delete_invoice_modal">
                                <i class="las la-trash"></i> حذف
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>رقم الفاتورة:</strong> {{ $purchaseInvoice->invoice_number }}</p>
                            <p><strong>تاريخ الإصدار:</strong> {{ $purchaseInvoice->issue_date->format('Y-m-d') }}</p>
                            <p><strong>تاريخ الاستحقاق:</strong> {{ $purchaseInvoice->due_date ? $purchaseInvoice->due_date->format('Y-m-d') : 'لا يوجد' }}</p>
                            <p><strong>المورد:</strong> {{ $purchaseInvoice->supplier->name ?? 'غير معروف' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الإجمالي الفرعي:</strong> {{ number_format($purchaseInvoice->subtotal, 2) }}</p>
                            <p><strong>الخصم:</strong> {{ number_format($purchaseInvoice->discount, 2) }}</p>
                            <p><strong>الضريبة:</strong> {{ number_format($purchaseInvoice->tax, 2) }}</p>
                            <p><strong>الإجمالي الكلي:</strong> {{ number_format($purchaseInvoice->total, 2) }}</p>
                            <p><strong>المبلغ المدفوع:</strong> {{ number_format($purchaseInvoice->paid_amount, 2) }}</p>
                            <p><strong>المبلغ المستحق:</strong> {{ number_format($purchaseInvoice->due_amount, 2) }}</p>
                        </div>
                    </div>

                    <hr>

                    <h5>بنود الفاتورة</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered text-md-nowrap">
                            <thead>
                                <tr>
                                    <th>تسلسل</th>
                                    <th>المادة</th>
                                    <th>الكمية</th>
                                    <th>سعر الوحدة</th>
                                    <th>الإجمالي</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchaseInvoice->items as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $item->material->name ?? 'غير معروف' }}</td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ number_format($item->unit_price, 2) }}</td>
                                        <td>{{ number_format($item->total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right"><strong>الإجمالي الفرعي (للبنود):</strong></td>
                                    <td><strong>{{ number_format($purchaseInvoice->items->sum('total'), 2) }}</strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <hr>

                    {{-- =============================================== --}}
                    {{-- قسم المدفوعات --}}
                    {{-- =============================================== --}}
                    <div class="row mt-5">
                        <div class="col-12">
                            <h5>المدفوعات الخاصة بهذه الفاتورة</h5>
                            <a href="{{ route('purchase_payments.create', ['purchase_invoice_id' => $purchaseInvoice->id]) }}" class="btn btn-primary btn-sm mb-3">
                                <i class="fas fa-plus"></i> إضافة دفعة جديدة
                            </a>
                            <div class="table-responsive">
                                <table class="table table-bordered text-md-nowrap" id="payments-table">
                                    <thead>
                                        <tr>
                                            <th>تسلسل</th>
                                            <th>تاريخ الدفع</th>
                                            <th>المبلغ</th>
                                            <th>طريقة الدفع</th>
                                            <th>تمت بواسطة</th>
                                            <th>ملاحظات</th>
                                            <th>العمليات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($purchaseInvoice->payments as $key => $payment)
                                            <tr>
                                                <td>{{ $key + 1 }}</td>
                                                <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                                <td>{{ number_format($payment->amount, 2) }}</td>
                                                <td>{{ $payment->payment_method_name }}</td>
                                                <td>{{ $payment->user->name ?? 'غير معروف' }}</td> {{-- عرض اسم المستخدم --}}
                                                <td>{{ $payment->notes }}</td>
                                                <td>
                                                    <a href="{{ route('purchase_payments.edit', $payment->id) }}" class="btn btn-sm btn-info" title="تعديل"><i class="las la-pen"></i></a>
                                                    <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                            data-target="#delete_payment_modal_{{ $payment->id }}" title="حذف">
                                                            <i class="las la-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            {{-- Delete Modal for payment --}}
                                            <div class="modal fade" id="delete_payment_modal_{{ $payment->id }}" tabindex="-1" role="dialog"
                                                aria-labelledby="deletePaymentModalLabel_{{ $payment->id }}" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deletePaymentModalLabel_{{ $payment->id }}">حذف دفعة</h5>
                                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">&times;</span>
                                                            </button>
                                                        </div>
                                                        <form action="{{ route('purchase_payments.destroy', $payment->id) }}" method="post">
                                                            @method('delete')
                                                            @csrf
                                                            <div class="modal-body">
                                                                هل أنت متأكد من عملية حذف الدفعة بمبلغ <strong>{{ number_format($payment->amount, 2) }}</strong>؟
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                                                                <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center">لا توجد مدفوعات مسجلة لهذه الفاتورة.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    {{-- =============================================== --}}
                    {{-- نهاية قسم المدفوعات --}}
                    {{-- =============================================== --}}

                    <hr>

                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>الحالة:</strong>
                                @if ($purchaseInvoice->status == 'paid')
                                    <span class="badge badge-success-gradient">{{ $purchaseInvoice->status_name }}</span>
                                @elseif ($purchaseInvoice->status == 'partial')
                                    <span class="badge badge-warning-gradient">{{ $purchaseInvoice->status_name }}</span>
                                @else
                                    <span class="badge badge-danger-gradient">{{ $purchaseInvoice->status_name }}</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>تاريخ الإنشاء:</strong> {{ $purchaseInvoice->created_at->format('Y-m-d H:i:s') }}</p>
                            <p><strong>تاريخ آخر تحديث:</strong> {{ $purchaseInvoice->updated_at->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <p><strong>ملاحظات:</strong></p>
                        <p>{{ $purchaseInvoice->notes ?? 'لا توجد ملاحظات.' }}</p>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- Delete Invoice Modal --}}
    <div class="modal fade" id="delete_invoice_modal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">حذف فاتورة شراء</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form action="{{ route('purchase_invoices.destroy', $purchaseInvoice->id) }}" method="post">
                    @method('delete')
                    @csrf
                    <div class="modal-body">
                        <p>هل أنت متأكد من عملية حذف فاتورة الشراء رقم <strong>{{ $purchaseInvoice->invoice_number }}</strong>؟</p><br>
                        <input type="hidden" name="invoice_id" id="invoice_id" value="{{ $purchaseInvoice->id }}">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    {{-- أصول JS من قالب ناينوكس --}}
    <script src="{{URL::asset('assets/plugins/select2/js/select2.min.js')}}"></script>
    <script src="{{URL::asset('assets/js/form-select2.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/jquery-ui/ui/widgets/datepicker.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/sumoselect/jquery.sumoselect.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/notify/js/notifIt.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/notify/js/notifit-custom.js')}}"></script>

    <script src="{{URL::asset('assets/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.dataTables.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.responsive.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/responsive.dataTables.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/jquery.dataTables.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.bootstrap4.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.buttons.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/buttons.bootstrap4.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/jszip.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/pdfmake.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/vfs_fonts.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/buttons.html5.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/buttons.print.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/buttons.colVis.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.responsive.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/responsive.bootstrap4.min.js')}}"></script>
    <script src="{{URL::asset('assets/js/table-data.js')}}"></script>

    <script>
        // JavaScript for delete invoice modal
        $('#delete_invoice_modal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var invoice_id = button.data('invoice_id')
            var modal = $(this)
            modal.find('.modal-body #invoice_id').val(invoice_id);
        })

        // تهيئة Datatable للمدفوعات
        $(document).ready(function() {
            $('#payments-table').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": true,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Arabic.json" // للغة العربية
                },
                "columnDefs": [ // لتعطيل الترتيب على عمود العمليات
                    { "orderable": false, "targets": [6] }
                ]
            });
        });
    </script>
@endsection
