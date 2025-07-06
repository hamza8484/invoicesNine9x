@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', 'تقرير ضريبة القيمة المضافة - ناينوكس')

@section('css')
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css')}}" rel="stylesheet">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">التقارير</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تقرير ضريبة القيمة المضافة</span>
            </div>
        </div>
        <div class="d-flex my-xl-auto right-content">
            {{-- زر تصدير التقرير (يمكن إضافته لاحقاً) --}}
            {{-- <button class="btn btn-info ml-auto mr-2" onclick="exportReport()"><i class="fas fa-file-excel"></i> تصدير Excel</button> --}}
        </div>
    </div>
@endsection

@section('content')

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title mb-0">تقرير ضريبة القيمة المضافة لهيئة الزكاة والضريبة والجمارك</h4>
                </div>
                <div class="card-body">
                    {{-- فلتر الفترة الزمنية --}}
                    <form action="{{ route('taxes.vat_report') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-5 form-group">
                                <label for="start_date">من تاريخ:</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $reportData['startDate'] }}">
                            </div>
                            <div class="col-md-5 form-group">
                                <label for="end_date">إلى تاريخ:</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $reportData['endDate'] }}">
                            </div>
                            <div class="col-md-2 form-group d-flex align-items-end">
                                <button type="submit" class="btn btn-primary btn-block">عرض التقرير</button>
                            </div>
                        </div>
                    </form>

                    <hr>

                    <h5 class="text-center mb-4">ملخص الفترة من {{ $reportData['startDate'] }} إلى {{ $reportData['endDate'] }}</h5>

                    <div class="row text-center mb-4">
                        <div class="col-md-4">
                            <div class="card bg-info-transparent">
                                <div class="card-body">
                                    <h6 class="card-title text-info">إجمالي المبيعات (قبل الضريبة)</h6>
                                    <h2 class="text-info">{{ number_format($reportData['totalSalesAmount'], 2) }} ريال</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning-transparent">
                                <div class="card-body">
                                    <h6 class="card-title text-warning">إجمالي مبلغ الضريبة</h6>
                                    <h2 class="text-warning">{{ number_format($reportData['totalTaxAmount'], 2) }} ريال</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-success-transparent">
                                <div class="card-body">
                                    <h6 class="card-title text-success">إجمالي المبيعات (شامل الضريبة)</h6>
                                    <h2 class="text-success">{{ number_format($reportData['totalSalesWithTax'], 2) }} ريال</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <h6>الفواتير المضمنة في التقرير:</h6>
                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>رقم الفاتورة</th>
                                    <th>تاريخ الفاتورة</th>
                                    <th>المبلغ قبل الضريبة</th>
                                    <th>مبلغ الضريبة</th>
                                    <th>الإجمالي بعد الضريبة</th>
                                    <th>العميل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportData['invoices'] as $key => $invoice)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $invoice->invoice_number }}</td>
                                        <td>{{ $invoice->invoice_date }}</td>
                                        <td>{{ number_format($invoice->sub_total, 2) }}</td>
                                        <td>{{ number_format($invoice->total_tax, 2) }}</td>
                                        <td>{{ number_format($invoice->total, 2) }}</td>
                                        <td>{{ $invoice->client->name ?? 'غير محدد' }}</td> {{-- افترض وجود علاقة بالعميل --}}
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">لا توجد فواتير لهذه الفترة.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
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
                dir: "rtl"
            });

            // تهيئة date pickers
            $('#start_date, #end_date').datepicker({
                dateFormat: 'yy-mm-dd',
                // يمكنك إضافة خيارات أخرى
            });

            // تأكد من تهيئة DataTable إذا كنت تستخدمها
            if ($.fn.DataTable) {
                $('#example1').DataTable({
                    responsive: true,
                    paging: false, // لا نريد تقسيم صفحات لتقرير كامل
                    searching: false, // لا نريد بحث
                    info: false, // لا نريد معلومات
                    order: [[0, 'asc']], // ترتيب افتراضي
                    // يمكنك إضافة خيارات أخرى هنا
                });
            }
        });

        // دالة لتصدير التقرير (يمكن توسيعها لاحقاً)
        function exportReport() {
            // هنا يمكنك إضافة منطق لتصدير البيانات إلى Excel أو PDF
            // يمكن استخدام مكتبات مثل SheetJS (js-xlsx) للـ Excel
            // أو jsPDF للـ PDF
            alert('وظيفة التصدير قيد التطوير!');
        }
    </script>
@endsection