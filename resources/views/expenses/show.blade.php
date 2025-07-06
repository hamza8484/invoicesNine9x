@extends('layouts.master')

@section('title')
    تفاصيل المصروف: {{ $expense->description ?? $expense->id }} - ناينوكس
@stop

@section('css')
    @endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المصروفات</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تفاصيل المصروف: {{ $expense->description ?? $expense->id }}</span>
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
                        تفاصيل المصروف
                    </div>
                    <p class="mg-b-20"></p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>المشروع:</strong> {{ $expense->project->project_name ?? 'غير محدد' }}</p>
                            <p><strong>المبلغ:</strong> {{ number_format($expense->amount, 2) }}</p>
                            <p><strong>تاريخ المصروف:</strong> {{ $expense->expense_date->format('Y-m-d') }}</p>
                            <p><strong>نوع المصروف:</strong>
                                @switch($expense->type)
                                    @case('material') مواد @break
                                    @case('salary') رواتب @break
                                    @case('transport') نقل @break
                                    @case('misc') متنوع @break
                                @endswitch
                            </p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>المورد:</strong> {{ $expense->supplier->name ?? 'لا يوجد' }}</p>
                            <p><strong>بواسطة:</strong> {{ $expense->creator->name ?? 'غير محدد' }}</p>
                            <p><strong>تاريخ الإنشاء:</strong> {{ $expense->created_at->format('Y-m-d H:i') }}</p>
                            <p><strong>آخر تحديث:</strong> {{ $expense->updated_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                    <hr>
                    <h5>الوصف:</h5>
                    <p>{{ $expense->description ?? 'لا يوجد وصف.' }}</p>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-info ml-2">تعديل المصروف</a>
                        <a href="{{ route('expenses.index') }}" class="btn btn-secondary">العودة إلى المصروفات</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    @endsection