@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', 'إدارة الإشعارات - ناينوكس')

@section('css')
    <!-- Internal Data table css -->
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css')}}" rel="stylesheet">
    <!--Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <style>
        .notification-unread {
            background-color: #e6f7ff; /* Light blue for unread notifications */
            font-weight: bold;
        }
        .notification-read {
            background-color: #f8f9fa; /* Light gray for read notifications */
            color: #6c757d;
        }
    </style>
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">الإشعارات</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ إدارة الإشعارات</span>
            </div>
        </div>
        <div class="d-flex my-xl-auto right-content">
            <a href="{{ route('notifications.markAllAsRead') }}" class="btn btn-info ml-auto mr-2">
                <i class="fas fa-check-double"></i> تحديد الكل كمقروء
            </a>
            {{-- إذا كنت تريد زر إضافة إشعار يدوي (غير شائع للإشعارات) --}}
            {{-- <a href="{{ route('notifications.create') }}" class="btn btn-primary ml-auto"><i class="fas fa-plus"></i> إضافة إشعار جديد</a> --}}
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
                    <h4 class="card-title mb-0">قائمة الإشعارات</h4>
                </div>
                <div class="card-body">
                    {{-- فلتر البحث --}}
                    <form action="{{ route('notifications.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="read_status">حالة القراءة:</label>
                                <select name="read_status" id="read_status" class="form-control select2">
                                    <option value="">الكل</option>
                                    <option value="unread" {{ request('read_status') == 'unread' ? 'selected' : '' }}>غير مقروءة</option>
                                    <option value="read" {{ request('read_status') == 'read' ? 'selected' : '' }}>مقروءة</option>
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="type">النوع:</label>
                                <select name="type" id="type" class="form-control select2">
                                    <option value="">كل الأنواع</option>
                                    @foreach($notificationTypes as $type)
                                        <option value="{{ $type }}" {{ request('type') == $type ? 'selected' : '' }}>
                                            {{ $type }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="search">بحث:</label>
                                <input type="text" name="search" id="search" class="form-control" value="{{ request('search') }}" placeholder="بحث بالعنوان أو المحتوى">
                            </div>
                            <div class="col-md-12 form-group d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">بحث</button>
                                <a href="{{ route('notifications.index') }}" class="btn btn-secondary mr-2">إعادة تعيين</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1"> {{-- افترض أن لديك جدول بيانات DataTable --}}
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-15p border-bottom-0">العنوان</th>
                                    <th class="wd-30p border-bottom-0">الرسالة</th>
                                    <th class="wd-10p border-bottom-0">النوع</th>
                                    <th class="wd-10p border-bottom-0">المستخدم المستهدف</th>
                                    <th class="wd-10p border-bottom-0">الحالة</th>
                                    <th class="wd-15p border-bottom-0">تاريخ الإنشاء</th>
                                    <th class="wd-10p border-bottom-0">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($notifications as $key => $notification)
                                    <tr class="{{ $notification->is_read ? 'notification-read' : 'notification-unread' }}">
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $notification->title }}</td>
                                        <td>{{ Str::limit($notification->message, 70) }}</td> {{-- استخدم Str::limit لتقصير الرسالة --}}
                                        <td>{{ $notification->type ?? 'عام' }}</td>
                                        <td>{{ $notification->user->name ?? 'جميع المستخدمين' }}</td>
                                        <td>
                                            @if ($notification->is_read)
                                                <span class="badge badge-success-gradient">مقروءة</span>
                                            @else
                                                <span class="badge badge-warning-gradient">غير مقروءة</span>
                                            @endif
                                        </td>
                                        <td>{{ $notification->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            @if (!$notification->is_read)
                                                <a href="{{ route('notifications.markAsRead', $notification->id) }}" class="btn btn-sm btn-info" title="تحديد كمقروء">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            @else
                                                <a href="{{ route('notifications.markAsUnread', $notification->id) }}" class="btn btn-sm btn-secondary" title="تحديد كغير مقروء">
                                                    <i class="fas fa-eye-slash"></i>
                                                </a>
                                            @endif

                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                    data-target="#delete_notification{{ $notification->id }}" title="حذف">
                                                <i class="las la-trash"></i>
                                            </button>
                                        </td>
                                    </tr>

                                    {{-- Delete Modal --}}
                                    <div class="modal fade" id="delete_notification{{ $notification->id }}" tabindex="-1" role="dialog"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">حذف إشعار</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('notifications.destroy', $notification->id) }}" method="post">
                                                    {{ method_field('delete') }}
                                                    {{ csrf_field() }}
                                                    <div class="modal-body">
                                                        هل أنت متأكد من عملية حذف الإشعار: <strong>{{ $notification->title }}</strong>؟
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
                                        <td colspan="8" class="text-center">لا توجد إشعارات لعرضها.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $notifications->links() }} {{-- لروابط التنقل بين الصفحات (pagination) --}}
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
