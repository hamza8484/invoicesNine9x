@extends('layouts.master')

@section('title')
    تعديل عميل - ناينوكس
@stop

@section('css')
    <link href="{{ URL::asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet">
    <link href="{{ URL::asset('assets/plugins/fileuploads/css/fileupload.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ URL::asset('assets/plugins/fancyuploder/fancy_fileupload.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css') }}">
    <link rel="stylesheet" href="{{ URL::asset('assets/plugins/telephoneinput/telephoneinput-rtl.css') }}">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">العملاء</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ تعديل عميل</span>
            </div>
        </div>
    </div>
    @endsection

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('clients.update', $client->id) }}" method="post" autocomplete="off">
                        {{ method_field('patch') }} {{-- أو 'PUT' --}}
                        {{ csrf_field() }}
                        {{-- <input type="hidden" name="id" value="{{ $client->id }}"> --}} {{-- تم تمرير الـ ID في route --}}

                        <div class="row">
                            <div class="col">
                                <label for="inputName" class="control-label">اسم العميل</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ $client->name }}" required>
                            </div>

                            <div class="col">
                                <label for="inputName" class="control-label">شخص الاتصال</label>
                                <input type="text" class="form-control" id="company" name="company"
                                    value="{{ $client->company }}" required>
                            </div>

                            <div class="col">
                                <label for="inputName" class="control-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="{{ $client->email }}" required>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col">
                                <label for="inputName" class="control-label">الهاتف</label>
                                <input type="text" class="form-control" id="phone" name="phone"
                                    value="{{ $client->phone }}">
                            </div>

                            
                        </div>

                        <div class="row mt-3">
                           
                            <div class="col-lg-6">
                                <label for="inputName" class="control-label">الرقم الضريبي</label>
                                <input type="text" class="form-control" id="Vat_No" name="Vat_No"
                                    value="{{ $client->Vat_No }}">
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col">
                                <label for="exampleTextarea">العنوان</label>
                                <textarea class="form-control" id="address" name="address" rows="3">{{ $client->address }}</textarea>
                            </div>
                        </div>

                        <div class="row mt-3">
                            <div class="col">
                                <label for="exampleTextarea">ملاحظات</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3">{{ $client->notes }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <button type="submit" class="btn btn-primary">تحديث العميل</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    <script src="{{ URL::asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fileuploads/js/fileupload.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fileuploads/js/file-upload.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fancyuploder/jquery.ui.widget.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fancyuploder/jquery.fileupload.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fancyuploder/jquery.iframe-transport.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fancyuploder/jquery.fancy-fileupload.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/fancyuploder/fancy-uploader.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/sumoselect/jquery.sumoselect.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/telephoneinput/telephoneinput.js') }}"></script>
    <script src="{{ URL::asset('assets/plugins/telephoneinput/inttelephoneinput.js') }}"></script>
@endsection