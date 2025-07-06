@extends('layouts.master')

@section('title')
    تفاصيل المشروع: {{ $project->project_name }} - ناينوكس
@stop

@section('css')
    @endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المشاريع</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تفاصيل المشروع: {{ $project->project_name }}</span>
            </div>
        </div>
    </div>
    @endsection

@section('content')

    <div class="row row-sm">
        <div class="col-lg-12 col-xl-12 col-md-12 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="main-content-label mg-b-5">
                        تفاصيل المشروع
                    </div>
                    <p class="mg-b-20"></p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>اسم المشروع:</strong> {{ $project->project_name }}</p>
                            <p><strong>العميل:</strong> {{ $project->client->name ?? 'غير محدد' }}</p>
                            <p><strong>مدير المشروع:</strong> {{ $project->manager->name ?? 'غير محدد' }}</p>
                            <p><strong>تاريخ البدء:</strong> {{ $project->start_date->format('Y-m-d') }}</p>
                            <p><strong>تاريخ الانتهاء المتوقع:</strong> {{ $project->end_date->format('Y-m-d') }}</p>
                            <p><strong>الموقع:</strong> {{ $project->location ?? 'غير محدد' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الحالة:</strong>
                                @php
                                    $statusText = '';
                                    $statusClass = '';
                                    switch($project->status) {
                                        case 'planning': $statusText = 'قيد التخطيط'; $statusClass = 'badge-primary'; break;
                                        case 'in_progress': $statusText = 'قيد التنفيذ'; $statusClass = 'badge-info'; break;
                                        case 'on_hold': $statusText = 'معلق'; $statusClass = 'badge-warning'; break;
                                        case 'completed': $statusText = 'مكتمل'; $statusClass = 'badge-success'; break;
                                        case 'cancelled': $statusText = 'ملغى'; $statusClass = 'badge-danger'; break;
                                        case 'archived': $statusText = 'مؤرشف'; $statusClass = 'badge-secondary'; break;
                                        default: $statusText = 'غير معروف'; $statusClass = 'badge-light'; break;
                                    }
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ $statusText }}</span>
                            </p>
                            <p><strong>تاريخ البدء الفعلي:</strong> {{ $project->actual_start_date ? $project->actual_start_date->format('Y-m-d') : 'غير محدد' }}</p>
                            <p><strong>تاريخ الانتهاء الفعلي:</strong> {{ $project->actual_end_date ? $project->actual_end_date->format('Y-m-d') : 'غير محدد' }}</p>
                            <p><strong>قيمة العقد:</strong> {{ number_format($project->contract_value, 2) ?? 'غير محدد' }}</p>
                            <p><strong>الميزانية المخصصة:</strong> {{ number_format($project->budget, 2) }}</p>
                            <p><strong>المصروفات الحالية:</strong> {{ number_format($project->current_spend, 2) }}</p>
                            <p><strong>إجمالي الإيرادات:</strong> {{ number_format($project->total_income, 2) }}</p> {{-- **أضف هذا السطر** --}}
                            <p><strong>الربح/الخسارة المتوقعة:</strong> {{ number_format($project->total_income - $project->current_spend, 2) }}</p> {{-- **يمكنك إضافة هذا للميزانية المتبقية** --}}
                        </div>
                    </div>
                    <hr>
                    <h5>الوصف:</h5>
                    <p>{{ $project->description ?? 'لا يوجد وصف.' }}</p>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('projects.edit', $project->id) }}" class="btn btn-info ml-2">تعديل المشروع</a>
                        <a href="{{ route('projects.index') }}" class="btn btn-secondary">العودة إلى المشاريع</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    @endsection