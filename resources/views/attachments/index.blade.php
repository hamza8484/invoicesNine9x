@extends('layouts.master') 

@section('title', 'إدارة المرفقات - ناينوكس')

@section('css')
    <!-- Internal Data table css -->
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css')}}" rel="stylesheet">
    <!--Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المرفقات</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ إدارة المرفقات</span>
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

    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title mb-0">قائمة جميع المرفقات</h4>
                </div>
                <div class="card-body">
                    {{-- فلتر البحث --}}
                    <form action="{{ route('attachments.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label for="attachable_type">نوع الكائن:</label>
                                <input type="text" name="attachable_type" id="attachable_type" class="form-control" value="{{ request('attachable_type') }}" placeholder="مثال: App\Models\Invoice">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="file_name">اسم الملف:</label>
                                <input type="text" name="file_name" id="file_name" class="form-control" value="{{ request('file_name') }}" placeholder="اسم الملف">
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="uploaded_by">المستخدم الرافع:</label>
                                <select name="uploaded_by" id="uploaded_by" class="form-control select2">
                                    <option value="">كل المستخدمين</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ request('uploaded_by') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 form-group d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">بحث</button>
                                <a href="{{ route('attachments.index') }}" class="btn btn-secondary mr-2">إعادة تعيين</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1"> {{-- افترض أن لديك جدول بيانات DataTable --}}
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-15p border-bottom-0">اسم الملف</th>
                                    <th class="wd-15p border-bottom-0">نوع الكائن</th>
                                    <th class="wd-10p border-bottom-0">معرف الكائن</th>
                                    <th class="wd-15p border-bottom-0">تم الرفع بواسطة</th>
                                    <th class="wd-15p border-bottom-0">تاريخ الرفع</th>
                                    <th class="wd-10p border-bottom-0">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attachments as $key => $attachment)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>{{ $attachment->file_name }}</td>
                                        <td>{{ class_basename($attachment->attachable_type) }}</td>
                                        <td>{{ $attachment->attachable_id }}</td>
                                        <td>{{ $attachment->uploader->name ?? 'مستخدم غير معروف' }}</td>
                                        <td>{{ $attachment->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td>
                                            <a href="{{ route('attachments.download', $attachment->id) }}" class="btn btn-sm btn-success"
                                               title="تنزيل"><i class="las la-download"></i></a>

                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                    data-target="#delete_attachment{{ $attachment->id }}" title="حذف"><i
                                                    class="las la-trash"></i></button>
                                        </td>
                                    </tr>

                                    {{-- Delete Modal --}}
                                    <div class="modal fade" id="delete_attachment{{ $attachment->id }}" tabindex="-1" role="dialog"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">حذف مرفق</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('attachments.destroy', $attachment->id) }}" method="post">
                                                    {{ method_field('delete') }}
                                                    {{ csrf_field() }}
                                                    <div class="modal-body">
                                                        هل أنت متأكد من عملية حذف المرفق: <strong>{{ $attachment->file_name }}</strong>؟
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
                                        <td colspan="7" class="text-center">لا توجد مرفقات لعرضها.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $attachments->links() }} {{-- لروابط التنقل بين الصفحات (pagination) --}}
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
                width: '100%'
            });
        });
    </script>
@endsection