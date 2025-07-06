@extends('layouts.master')

@section('title')
    قائمة فواتير الشراء - ناينوكس
@stop

@section('css')
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/notify/css/notifIt.css')}}" rel="stylesheet"/>
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المشتريات</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ قائمة فواتير الشراء</span>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (session('success'))
        <script>
            window.onload = function() {
                notif({
                    msg: "{{ session('success') }}",
                    type: "success"
                });
            }
        </script>
    @endif
    @if (session('error'))
        <script>
            window.onload = function() {
                notif({
                    msg: "{{ session('error') }}",
                    type: "error"
                });
            }
        </script>
    @endif

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between">
                        <h4 class="card-title mg-b-0">فواتير الشراء</h4>
                        <i class="mdi mdi-dots-horizontal text-gray"></i>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('purchase_invoices.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus ml-1"></i> إضافة فاتورة شراء
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1">
                            <thead>
                                <tr>
                                    <th>رقم الفاتورة</th>
                                    <th>المورد</th>
                                    <th>تاريخ الإصدار</th>
                                    <th>الإجمالي</th>
                                    <th>المدفوع</th>
                                    <th>المستحق</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($purchaseInvoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $invoice->supplier->name ?? 'N/A' }}</td>
                                        <td>{{ $invoice->issue_date->format('Y-m-d') }}</td>
                                        <td>{{ number_format($invoice->total, 2) }}</td>
                                        <td>{{ number_format($invoice->paid_amount, 2) }}</td>
                                        <td>{{ number_format($invoice->due_amount, 2) }}</td>
                                        <td>
                                            @if($invoice->status == 'paid')
                                                <span class="badge badge-success">مدفوعة</span>
                                            @elseif($invoice->status == 'partial')
                                                <span class="badge badge-warning">مدفوعة جزئياً</span>
                                            @else
                                                <span class="badge badge-danger">غير مدفوعة</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="btn ripple btn-primary btn-sm" data-toggle="dropdown">
                                                    العمليات <i class="fas fa-caret-down ml-1"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="{{ route('purchase_invoices.show', $invoice->id) }}">
                                                        <i class="text-info fas fa-eye"></i> عرض
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('purchase_invoices.edit', $invoice->id) }}">
                                                        <i class="text-primary fas fa-edit"></i> تعديل
                                                    </a>
                                                    <form action="{{ route('purchase_invoices.destroy', $invoice->id) }}" method="POST" onsubmit="return confirm('هل أنت متأكد من الحذف؟');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="dropdown-item text-danger">
                                                            <i class="fas fa-trash-alt"></i> حذف
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">لا توجد فواتير شراء.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- روابط التنقل --}}
                    <div class="mt-3">
                        {{ $purchaseInvoices->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="{{URL::asset('assets/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.bootstrap4.min.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/notify/js/notifIt.js')}}"></script>
    <script src="{{URL::asset('assets/plugins/notify/js/notifit-custom.js')}}"></script>
    <script>
        $(document).ready(function () {
            $('#example1').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Arabic.json'
                }
            });
        });
    </script>
@endsection
