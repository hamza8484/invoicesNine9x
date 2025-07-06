@extends('layouts.master')

@section('title')
    إدارة المشاريع - ناينوكس
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
                <h4 class="content-title mb-0 my-auto">المشاريع</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ إدارة المشاريع</span>
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
                        <h4 class="card-title mg-b-0">قائمة المشاريع</h4>
                        <i class="mdi mdi-dots-horizontal text-gray"></i>
                    </div>
                    <div class="d-flex justify-content-end mt-2">
                        <a class="btn btn-primary btn-sm" href="{{ route('projects.create') }}">إضافة مشروع جديد</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1">
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-15p border-bottom-0">اسم المشروع</th>
                                    <th class="wd-10p border-bottom-0">العميل</th>
                                    <th class="wd-10p border-bottom-0">المدير</th>
                                    <th class="wd-10p border-bottom-0">تاريخ البدء</th>
                                    <th class="wd-10p border-bottom-0">تاريخ الانتهاء</th>
                                    <th class="wd-10p border-bottom-0">الحالة</th>
                                    <th class="wd-10p border-bottom-0">الميزانية</th>
                                    <th class="wd-10p border-bottom-0">المصروفات الحالية</th>
                                    <th class="wd-10p border-bottom-0">إجمالي الإيرادات</th> {{-- **أضف هذا الرأس الجديد** --}}
                                    <th class="wd-10p border-bottom-0">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($projects as $project)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $project->project_name }}</td>
                                        <td>{{ $project->client->name ?? 'غير محدد' }}</td>
                                        <td>{{ $project->manager->name ?? 'غير محدد' }}</td>
                                        <td>{{ $project->start_date->format('Y-m-d') }}</td>
                                        <td>{{ $project->end_date->format('Y-m-d') }}</td>
                                        <td>
                                            @php
                                                $statusClass = '';
                                                switch($project->status) {
                                                    case 'planning': $statusClass = 'badge-primary'; break;
                                                    case 'in_progress': $statusClass = 'badge-info'; break;
                                                    case 'on_hold': $statusClass = 'badge-warning'; break;
                                                    case 'completed': $statusClass = 'badge-success'; break;
                                                    case 'cancelled': $statusClass = 'badge-danger'; break;
                                                    case 'archived': $statusClass = 'badge-secondary'; break;
                                                    default: $statusClass = 'badge-light'; break;
                                                }
                                            @endphp
                                            <span class="badge {{ $statusClass }}">{{ $project->status }}</span>
                                        </td>
                                        <td>{{ number_format($project->budget, 2) }}</td>
                                        <td>{{ number_format($project->current_spend, 2) }}</td>
                                        <td>{{ number_format($project->total_income, 2) }}</td> {{-- **أضف هذا السطر الجديد** --}}
                                        <td>
                                            <a href="{{ route('projects.show', $project->id) }}" class="btn btn-sm btn-success" title="عرض التفاصيل">
                                                <i class="las la-eye"></i>
                                            </a>
                                            <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-sm btn-info" title="تعديل المشروع">
                                                <i class="las la-pen"></i>
                                            </a>
                                            <a class="modal-effect btn btn-sm btn-danger" data-effect="effect-scale"
                                                data-id="{{ $project->id }}" data-project_name="{{ $project->project_name }}"
                                                data-toggle="modal" href="#delete_project_modal" title="حذف المشروع">
                                                <i class="las la-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">لا توجد مشاريع حتى الآن.</td> {{-- **عدّل colspan هنا** --}}
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal" id="delete_project_modal">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content modal-content-demo">
                    <div class="modal-header">
                        <h6 class="modal-title">حذف المشروع</h6><button aria-label="Close" class="close" data-dismiss="modal"
                            type="button"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form action="{{ route('projects.destroy', 'test') }}" method="post">
                        {{ method_field('delete') }}
                        {{ csrf_field() }}
                        <div class="modal-body">
                            <p>هل أنت متأكد من حذف هذا المشروع؟</p><br>
                            <input type="hidden" name="project_id" id="project_id" value="">
                            <input class="form-control" name="project_name" id="project_name" type="text" readonly>
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
        $('#delete_project_modal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget)
            var id = button.data('id')
            var project_name = button.data('project_name')
            var modal = $(this)
            modal.find('.modal-body #project_id').val(id);
            modal.find('.modal-body #project_name').val(project_name);
        })
    </script>
@endsection