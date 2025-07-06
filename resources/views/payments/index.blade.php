@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', 'إدارة المدفوعات - ناينوكس')

@section('css')
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المدفوعات</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ إدارة المدفوعات</span>
            </div>
        </div>
        <div class="d-flex my-xl-auto right-content">
            <a href="{{ route('payments.create') }}" class="btn btn-primary ml-auto"><i class="fas fa-plus"></i> إضافة دفعة جديدة</a>
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
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title mb-0">قائمة جميع المدفوعات</h4>
                </div>
                <div class="card-body">
                    {{-- فلتر البحث --}}
                    <form action="{{ route('payments.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label for="invoice_id">الفاتورة:</label>
                                <select name="invoice_id" id="invoice_id" class="form-control select2">
                                    <option value="">كل الفواتير</option>
                                    @foreach($invoices as $invoice)
                                        <option value="{{ $invoice->id }}" {{ request('invoice_id') == $invoice->id ? 'selected' : '' }}>
                                            {{ $invoice->invoice_number }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="user_id">المستخدم الرافع:</label>
                                <select name="user_id" id="user_id" class="form-control select2">
                                    <option value="">كل المستخدمين</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="payment_method">طريقة الدفع:</label>
                                <select name="payment_method" id="payment_method" class="form-control select2">
                                    <option value="">كل الطرق</option>
                                    @foreach($paymentMethods as $method)
                                        <option value="{{ $method }}" {{ request('payment_method') == $method ? 'selected' : '' }}>
                                            {{ ucwords(str_replace('_', ' ', $method)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="start_date">تاريخ البدء:</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="end_date">تاريخ الانتهاء:</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-12 form-group d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">بحث</button>
                                <a href="{{ route('payments.index') }}" class="btn btn-secondary mr-2">إعادة تعيين</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1"> {{-- افترض أن لديك جدول بيانات DataTable --}}
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-15p border-bottom-0">الفاتورة</th>
                                    <th class="wd-10p border-bottom-0">تاريخ الدفع</th>
                                    <th class="wd-10p border-bottom-0">المبلغ</th>
                                    <th class="wd-10p border-bottom-0">طريقة الدفع</th>
                                    <th class="wd-15p border-bottom-0">تمت بواسطة</th>
                                    <th class="wd-20p border-bottom-0">ملاحظات</th>
                                    <th class="wd-15p border-bottom-0">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $key => $payment)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $payment->invoice->invoice_number ?? 'فاتورة محذوفة' }}</td>
                                        <td>{{ $payment->payment_date->format('Y-m-d') }}</td>
                                        <td>{{ number_format($payment->amount, 2) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                        <td>{{ $payment->user->name ?? 'غير معروف' }}</td>
                                        <td>{{ $payment->notes }}</td>
                                        <td>
                                            <a href="{{ route('payments.edit', $payment->id) }}" class="btn btn-sm btn-info"
                                               title="تعديل"><i class="las la-pen"></i></a>

                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                    data-target="#delete_payment{{ $payment->id }}" title="حذف"><i
                                                    class="las la-trash"></i></button>
                                        </td>
                                    </tr>

                                    {{-- Delete Modal --}}
                                    <div class="modal fade" id="delete_payment{{ $payment->id }}" tabindex="-1" role="dialog"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">حذف دفعة</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('payments.destroy', $payment->id) }}" method="post">
                                                    {{ method_field('delete') }}
                                                    {{ csrf_field() }}
                                                    <div class="modal-body">
                                                        هل أنت متأكد من عملية حذف الدفعة بمبلغ <strong>{{ number_format($payment->amount, 2) }}</strong> للفاتورة <strong>{{ $payment->invoice->invoice_number ?? '' }}</strong>؟
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
                                        <td colspan="8" class="text-center">لا توجد مدفوعات لعرضها.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $payments->links() }} {{-- لروابط التنقل بين الصفحات (pagination) --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
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
    <script src="{{URL::asset('assets/plugins/select2/js/select2.min.js')}}"></script>
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