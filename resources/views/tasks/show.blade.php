@extends('layouts.master')

@section('title')
    تفاصيل المهمة: {{ $task->title }} - ناينوكس
@stop

@section('css')
    @endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المهام</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تفاصيل المهمة: {{ $task->title }}</span>
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
                        تفاصيل المهمة
                    </div>
                    <p class="mg-b-20"></p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>المشروع:</strong> {{ $project->project_name }}</p>
                            <p><strong>عنوان المهمة:</strong> {{ $task->title }}</p>
                            <p><strong>المسؤول:</strong> {{ $task->assignee->name ?? 'غير معين' }}</p>
                            <p><strong>الحالة:</strong>
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
                            </p>
                            <p><strong>الأولوية:</strong>
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
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>تاريخ البدء:</strong> {{ $task->start_date ? $task->start_date->format('Y-m-d') : '-' }}</p>
                            <p><strong>تاريخ الانتهاء المحدد:</strong> {{ $task->end_date ? $task->end_date->format('Y-m-d') : '-' }}</p>
                            <p><strong>تاريخ الاكتمال الفعلي:</strong> {{ $task->completed_at ? $task->completed_at->format('Y-m-d H:i') : '-' }}</p>
                            <p><strong>تاريخ الإنشاء:</strong> {{ $task->created_at->format('Y-m-d H:i') }}</p>
                            <p><strong>آخر تحديث:</strong> {{ $task->updated_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                    <hr>
                    <h5>الوصف:</h5>
                    <p>{{ $task->description ?? 'لا يوجد وصف.' }}</p>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('projects.tasks.edit', [$project->id, $task->id]) }}" class="btn btn-info ml-2">تعديل المهمة</a>
                        <a href="{{ route('projects.tasks.index', $project->id) }}" class="btn btn-secondary">العودة إلى المهام</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    @endsection