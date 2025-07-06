@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', 'سجلات التدقيق - ناينوكس')

@section('css')
    <!-- Internal Data table css -->
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css')}}" rel="stylesheet">
    <!--Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <!-- Internal Jquery-ui css -->
    <link href="{{URL::asset('assets/plugins/jquery-ui/jquery-ui.css')}}" rel="stylesheet">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">الإعدادات</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ سجلات التدقيق</span>
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
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title mb-0">قائمة سجلات التدقيق</h4>
                </div>
                <div class="card-body">
                    {{-- فلتر البحث --}}
                    <form action="{{ route('audit_logs.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label for="user_id">المستخدم:</label>
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
                                <label for="action">العملية:</label>
                                <select name="action" id="action" class="form-control select2">
                                    <option value="">كل العمليات</option>
                                    @foreach($actions as $action)
                                        <option value="{{ $action }}" {{ request('action') == $action ? 'selected' : '' }}>
                                            {{ $action }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="auditable_type">نوع الكيان:</label>
                                <select name="auditable_type" id="auditable_type" class="form-control select2">
                                    <option value="">كل الأنواع</option>
                                    @foreach($auditableTypes as $type)
                                        <option value="{{ $type }}" {{ request('auditable_type') == $type ? 'selected' : '' }}>
                                            {{ class_basename($type) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="auditable_id">معرف الكيان:</label>
                                <input type="number" name="auditable_id" id="auditable_id" class="form-control" value="{{ request('auditable_id') }}" placeholder="معرف الكيان">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="start_date">من تاريخ:</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="end_date">إلى تاريخ:</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="search">بحث عام:</label>
                                <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="بحث في القيم/IP/User Agent">
                            </div>
                            <div class="col-md-12 form-group d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">بحث</button>
                                <a href="{{ route('audit_logs.index') }}" class="btn btn-secondary mr-2">إعادة تعيين</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1"> {{-- افترض أن لديك جدول بيانات DataTable --}}
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-15p border-bottom-0">المستخدم</th>
                                    <th class="wd-10p border-bottom-0">العملية</th>
                                    <th class="wd-15p border-bottom-0">الكيان المتأثر</th>
                                    <th class="wd-10p border-bottom-0">معرف الكيان</th>
                                    <th class="wd-15p border-bottom-0">عنوان IP</th>
                                    <th class="wd-15p border-bottom-0">تاريخ ووقت</th>
                                    <th class="wd-10p border-bottom-0">التفاصيل</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($auditLogs as $key => $log)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $log->user->name ?? 'غير معروف' }}</td>
                                        <td>{{ $log->action }}</td>
                                        <td>{{ class_basename($log->auditable_type) }}</td>
                                        <td>{{ $log->auditable_id }}</td>
                                        <td>{{ $log->ip_address ?? 'لا يوجد' }}</td>
                                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <a href="{{ route('audit_logs.show', $log->id) }}" class="btn btn-sm btn-info"
                                               title="عرض التفاصيل"><i class="las la-eye"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">لا توجد سجلات تدقيق لعرضها.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $auditLogs->links() }} {{-- لروابط التنقل بين الصفحات (pagination) --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <!-- Internal Data tables -->
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
    <!--Internal  Datatable js -->
    <script src="{{URL::asset('assets/js/table-data.js')}}"></script>
    <!-- Internal Select2.min js -->
    <script src="{{URL::asset('assets/plugins/select2/js/select2.min.js')}}"></script>
    <!-- Internal Jquery-ui js -->
    <script src="{{URL::asset('assets/plugins/jquery-ui/ui/widgets/datepicker.js')}}"></script>
    <script>
        $(function() {
            $('.select2').select2({
                placeholder: 'اختر...',
                width: '100%',
                dir: "rtl" // إذا كانت الواجهة بالعربية
            });

            // تهيئة Datepicker
            $('#start_date, #end_date').datepicker({
                dateFormat: 'yy-mm-dd',
            });
        });
    </script>
@endsection
