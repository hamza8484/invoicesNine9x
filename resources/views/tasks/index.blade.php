@extends('layouts.master')

@section('title')
    مهام مشروع {{ $project->project_name }} - ناينوكس
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
                <h4 class="content-title mb-0 my-auto">المشاريع</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ مهام مشروع: {{ $project->project_name }}</span>
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
                        <h4 class="card-title mg-b-0">مهام المشروع: {{ $project->project_name }}</h4>
                        <i class="mdi mdi-dots-horizontal text-gray"></i>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <a class="btn btn-primary btn-sm ml-2" href="{{ route('projects.tasks.create', $project->id) }}">إضافة مهمة جديدة</a>
                        <a class="btn btn-secondary btn-sm" href="{{ route('projects.index') }}">العودة لقائمة المشاريع</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1">
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-20p border-bottom-0">عنوان المهمة</th>
                                    <th class="wd-15p border-bottom-0">المسؤول</th>
                                    <th class="wd-10p border-bottom-0">الحالة</th>
                                    <th class="wd-10p border-bottom-0">الأولوية</th>
                                    <th class="wd-10p border-bottom-0">تاريخ البدء</th>
                                    <th class="wd-10p border-bottom-0">تاريخ الانتهاء</th>
                                    <th class="wd-15p border-bottom-0">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tasks as $task)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td><a href="{{ route('projects.tasks.show', [$project->id, $task->id]) }}">{{ $task->title }}</a></td>
                                        <td>{{ $task->assignee->name ?? 'غير معين' }}</td>
                                        <td>
                                            @php
                                                $statusClass = '';
                                                switch($task->status) {
                                                    case 'pending': $statusClass = 'badge-info'; break;
                                                    case 'in_progress': $statusClass = 'badge-warning'; break;
                                                    case 'completed': $statusClass = 'badge-success'; break;
                                                    case 'delayed': $statusClass = 'badge-danger'; break;
                                                }
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($task->status)) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $priorityClass = '';
                                                switch($task->priority) {
                                                    case 'low': $priorityClass = 'text-info'; break;
                                                    case 'medium': $priorityClass = 'text-primary'; break;
                                                    case 'high': $priorityClass = 'text-warning'; break;
                                                    case 'urgent': $priorityClass = 'text-danger'; break;
                                                }
                                            @endphp
                                            <span class="{{ $priorityClass }}">{{ str_replace('_', ' ', ucfirst($task->priority)) }}</span>
                                        </td>
                                        <td>{{ $task->start_date ? $task->start_date->format('Y-m-d') : '-' }}</td>
                                        <td>{{ $task->end_date ? $task->end_date->format('Y-m-d') : '-' }}</td>
                                        <td>
                                            <a href="{{ route('projects.tasks.edit', [$project->id, $task->id]) }}" class="btn btn-sm btn-info" title="تعديل المهمة">
                                                <i class="las la-pen"></i>
                                            </a>
                                            <a class="modal-effect btn btn-sm btn-danger" data-effect="effect-scale"
                                                data-project_id="{{ $project->id }}" data-task_id="{{ $task->id }}"
                                                data-task_title="{{ $task->title }}"
                                                data-toggle="modal" href="#delete_task_modal" title="حذف المهمة">
                                                <i class="las la-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">لا توجد مهام لهذا المشروع حتى الآن.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" id="delete_task_modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content modal-content-demo">
                    <div class="modal-header">
                        <h6 class="modal-title">حذف المهمة</h6><button aria-label="Close" class="close" data-dismiss="modal"
                            type="button"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form action="" method="post" id="deleteTaskForm">
                        {{ method_field('delete') }}
                        {{ csrf_field() }}
                        <div class="modal-body">
                            <p>هل أنت متأكد من حذف هذه المهمة؟</p><br>
                            <input type="hidden" name="project_id_modal" id="project_id_modal" value="">
                            <input type="hidden" name="task_id_modal" id="task_id_modal" value="">
                            <input class="form-control" name="task_title_modal" id="task_title_modal" type="text" readonly>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
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
    <script src="{{ URL::asset('assets/js/modal.js') }}"></script> {{-- تأكد أن هذا السكربت موجود ويتعامل مع المودال --}}

    <script>
        $('#delete_task_modal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var project_id = button.data('project_id')
            var task_id = button.data('task_id')
            var task_title = button.data('task_title')
            var modal = $(this)

            modal.find('.modal-body #project_id_modal').val(project_id);
            modal.find('.modal-body #task_id_modal').val(task_id);
            modal.find('.modal-body #task_title_modal').val(task_title);

            // تحديث action الخاص بالنموذج ليناسب المسار الديناميكي
            var form = document.getElementById('deleteTaskForm');
            form.action = '{{ url('projects') }}/' + project_id + '/tasks/' + task_id;
        })
    </script>
@endsection