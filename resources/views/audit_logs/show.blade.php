@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', 'تفاصيل سجل التدقيق - ناينوكس')

@section('css')
    <!-- Internal Data table css -->
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <!--Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border: 1px solid #e9ecef;
            overflow-x: auto; /* لضمان التمرير الأفقي إذا كان المحتوى طويلاً */
        }
    </style>
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">الإعدادات</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تفاصيل سجل التدقيق</span>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title mb-0">تفاصيل سجل التدقيق #{{ $auditLog->id }}</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>المستخدم:</strong> {{ $auditLog->user->name ?? 'غير معروف' }}
                        </div>
                        <div class="col-md-4">
                            <strong>العملية:</strong> {{ $auditLog->action }}
                        </div>
                        <div class="col-md-4">
                            <strong>تاريخ ووقت:</strong> {{ $auditLog->created_at->format('Y-m-d H:i:s') }}
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <strong>نوع الكيان المتأثر:</strong> {{ class_basename($auditLog->auditable_type) }}
                        </div>
                        <div class="col-md-4">
                            <strong>معرف الكيان المتأثر:</strong> {{ $auditLog->auditable_id }}
                        </div>
                        <div class="col-md-4">
                            <strong>عنوان IP:</strong> {{ $auditLog->ip_address ?? 'لا يوجد' }}
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-12">
                            <strong>وكيل المستخدم (User Agent):</strong>
                            <pre>{{ $auditLog->user_agent ?? 'لا يوجد' }}</pre>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>القيم القديمة:</h5>
                            @if($auditLog->old_values)
                                <pre>{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                <p>لا توجد قيم قديمة (غالباً عملية إنشاء).</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h5>القيم الجديدة:</h5>
                            @if($auditLog->new_values)
                                <pre>{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            @else
                                <p>لا توجد قيم جديدة (غالباً عملية حذف).</p>
                            @endif
                        </div>
                    </div>

                    <div class="d-flex justify-content-center mt-4">
                        <a href="{{ route('audit_logs.index') }}" class="btn btn-secondary">العودة إلى سجلات التدقيق</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
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
