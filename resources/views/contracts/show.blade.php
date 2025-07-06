@extends('layouts.master')

@section('title')
    تفاصيل العقد: {{ $contract->contract_number }} - ناينوكس
@stop

@section('css')
    @endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">العقود</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تفاصيل العقد: {{ $contract->contract_number }}</span>
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
                        تفاصيل العقد
                    </div>
                    <p class="mg-b-20"></p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>رقم العقد:</strong> {{ $contract->contract_number }}</p>
                            <p><strong>المشروع المرتبط:</strong> {{ $contract->project->project_name ?? 'غير محدد' }}</p>
                            <p><strong>العميل:</strong> {{ $contract->client->name ?? 'غير محدد' }}</p>
                            <p><strong>القيمة الكلية:</strong> {{ number_format($contract->total_amount, 2) }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>تاريخ البدء:</strong> {{ $contract->start_date ? $contract->start_date->format('Y-m-d') : '-' }}</p>
                            <p><strong>تاريخ الانتهاء:</strong> {{ $contract->end_date ? $contract->end_date->format('Y-m-d') : '-' }}</p>
                            <p><strong>الحالة:</strong>
                                @php
                                    $statusClass = '';
                                    switch($contract->status) {
                                        case 'active': $statusClass = 'badge-success'; break;
                                        case 'expired': $statusClass = 'badge-danger'; break;
                                        case 'terminated': $statusClass = 'badge-dark'; break;
                                    }
                                @endphp
                                <span class="badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($contract->status)) }}</span>
                            </p>
                            <p><strong>تاريخ الإنشاء:</strong> {{ $contract->created_at->format('Y-m-d H:i') }}</p>
                            <p><strong>آخر تحديث:</strong> {{ $contract->updated_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                    <hr>
                    <h5>الملاحظات:</h5>
                    <p>{{ $contract->notes ?? 'لا توجد ملاحظات.' }}</p>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('contracts.edit', $contract->id) }}" class="btn btn-info ml-2">تعديل العقد</a>
                        <a href="{{ route('contracts.index') }}" class="btn btn-secondary">العودة إلى العقود</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    @endsection