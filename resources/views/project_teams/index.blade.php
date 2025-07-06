@extends('layouts.master')

@section('title')
    فريق مشروع {{ $project->project_name }} - ناينوكس
@stop

@section('css')
    <link href="{{ URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" />
    <link href="{{ URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('assets/plugins/sweet-alert/sweetalert.css') }}" rel="stylesheet">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المشاريع</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ فريق مشروع: {{ $project->project_name }}</span>
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

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between">
                        <h4 class="card-title mg-b-0">أعضاء فريق المشروع: {{ $project->project_name }}</h4>
                        <i class="mdi mdi-dots-horizontal text-gray"></i>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <a class="btn btn-primary btn-sm ml-2" href="{{ route('projects.team.create', $project->id) }}">إضافة عضو جديد</a>
                        <a class="btn btn-secondary btn-sm" href="{{ route('projects.index') }}">العودة لقائمة المشاريع</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1">
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-20p border-bottom-0">اسم العضو</th>
                                    <th class="wd-15p border-bottom-0">الدور العام</th>
                                    <th class="wd-20p border-bottom-0">الدور في المشروع</th>
                                    <th class="wd-15p border-bottom-0">تاريخ التعيين</th>
                                    <th class="wd-15p border-bottom-0">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($teamMembers as $member)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $member->name }}</td>
                                        <td>{{ $member->role }}</td>
                                        <td>{{ $member->pivot->role_in_project ?? 'غير محدد' }}</td>
                                        <td>{{ $member->pivot->assigned_at ? \Carbon\Carbon::parse($member->pivot->assigned_at)->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            <a href="{{ route('projects.team.edit', [$project->id, $member->id]) }}" class="btn btn-sm btn-info" title="تعديل الدور">
                                                <i class="las la-pen"></i>
                                            </a>
                                            <a class="modal-effect btn btn-sm btn-danger" data-effect="effect-scale"
                                                data-project_id="{{ $project->id }}" data-user_id="{{ $member->id }}"
                                                data-user_name="{{ $member->name }}"
                                                data-toggle="modal" href="#delete_member_modal" title="إزالة من الفريق">
                                                <i class="las la-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">لا يوجد أعضاء فريق لهذا المشروع حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" id="delete_member_modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content modal-content-demo">
                    <div class="modal-header">
                        <h6 class="modal-title">إزالة عضو الفريق</h6><button aria-label="Close" class="close" data-dismiss="modal"
                            type="button"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form action="" method="post" id="deleteTeamMemberForm">
                        {{ method_field('delete') }}
                        {{ csrf_field() }}
                        <div class="modal-body">
                            <p>هل أنت متأكد من إزالة هذا العضو من فريق المشروع؟</p><br>
                            <input type="hidden" name="project_id_modal" id="project_id_modal" value="">
                            <input type="hidden" name="user_id_modal" id="user_id_modal" value="">
                            <input class="form-control" name="user_name_modal" id="user_name_modal" type="text" readonly>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-danger">تأكيد الإزالة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    <script src="{{ URL::asset('assets/plugins/datatable/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/responsive.dataTables.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/jquery.dataTables.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.bootstrap4.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/jszip.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/pdfmake.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/vfs_fonts.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/buttons.html5.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/buttons.print.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/buttons.colVis.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/datatable/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/sweet-alert/sweetalert.min.js') }}"></script>
    <script src="{{ URL::asset('assets/js/modal.js') }}"></script> {{-- Assuming this handles the modal script --}}

    <script>
        $('#delete_member_modal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var project_id = button.data('project_id')
            var user_id = button.data('user_id')
            var user_name = button.data('user_name')
            var modal = $(this)

            // تعيين قيمة project_id و user_id في الحقول المخفية بالنموذج
            modal.find('.modal-body #project_id_modal').val(project_id);
            modal.find('.modal-body #user_id_modal').val(user_id);
            modal.find('.modal-body #user_name_modal').val(user_name);

            // تحديث action الخاص بالنموذج ليناسب المسار الديناميكي
            var form = document.getElementById('deleteTeamMemberForm');
            // استخدم route() لتوليد المسار الصحيح
            form.action = '{{ url('projects') }}/' + project_id + '/team/' + user_id;
        })
    </script>
@endsection