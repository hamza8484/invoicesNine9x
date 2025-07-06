@extends('layouts.master')

@section('title')
    تفاصيل المورد: {{ $supplier->name }} - ناينوكس
@stop

@section('css')
    @endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">الموردون</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تفاصيل المورد: {{ $supplier->name }}</span>
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
                        تفاصيل المورد
                    </div>
                    <p class="mg-b-20"></p>
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>الاسم:</strong> {{ $supplier->name }}</p>
                            <p><strong>رقم ضريبة القيمة المضافة:</strong> {{ $supplier->vat_No ?? '-' }}</p>
                            <p><strong>الشركة:</strong> {{ $supplier->company ?? '-' }}</p>
                            <p><strong>شخص الاتصال:</strong> {{ $supplier->contact_person ?? '-' }}</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>الهاتف:</strong> {{ $supplier->phone ?? '-' }}</p>
                            <p><strong>البريد الإلكتروني:</strong> {{ $supplier->email ?? '-' }}</p>
                            <p><strong>الفئة:</strong> {{ $supplier->category ?? '-' }}</p>
                            <p><strong>تاريخ الإنشاء:</strong> {{ $supplier->created_at->format('Y-m-d H:i') }}</p>
                            <p><strong>آخر تحديث:</strong> {{ $supplier->updated_at->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>
                    <hr>
                    <h5>العنوان:</h5>
                    <p>{{ $supplier->address ?? 'لا يوجد عنوان مسجل.' }}</p>

                    <div class="d-flex justify-content-end mt-4">
                        <a href="{{ route('suppliers.edit', $supplier->id) }}" class="btn btn-info ml-2">تعديل المورد</a>
                        <a href="{{ route('suppliers.index') }}" class="btn btn-secondary">العودة إلى الموردين</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    @endsection