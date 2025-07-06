@extends('layouts.master')

@section('title')
    تفاصيل الإيراد: {{ $income->amount }} - ناينوكس
@stop

@section('css')
    @endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">الإيرادات</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تفاصيل الإيراد: {{ number_format($income->amount, 2) }}</span>
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
                        تفاصيل الإيراد
                    </div>
                    <p class="mg-b-20"></p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>المشروع:</strong> {{ $income->project->project_name ?? 'غير محدد' }}</p>
                            <p><strong>المبلغ:</strong> {{ number_format($income->amount, 2) }}</p>
                            <p><strong>تاريخ الإيراد:</strong> {{ $income->income_date->format('Y-m-d') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>المصدر:</strong> {{ $income->source ?? '-' }}</p>
                            <p><strong>تاريخ الإنشاء:</strong> {{ $income->created_at->format('Y-m-d H:i') }}</p>
                            <p><strong>آخر تحديث:</strong> {{ $income->updated_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                    <hr>
                    <h5>ملاحظات:</h5>
                    <p>{{ $income->notes ?? 'لا توجد ملاحظات.' }}</p>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('incomes.edit', $income->id) }}" class="btn btn-info ml-2">تعديل الإيراد</a>
                        <a href="{{ route('incomes.index') }}" class="btn btn-secondary">العودة إلى الإيرادات</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    @endsection