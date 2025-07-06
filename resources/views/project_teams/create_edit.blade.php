@extends('layouts.master')

@section('title')
    {{ isset($user) ? 'تعديل دور عضو في المشروع' : 'إضافة عضو لفريق المشروع' }} - ناينوكس
@stop

@section('css')
    <link href="{{ URL::asset('assets/plugins/select2/css/select2.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ URL::asset('assets/plugins/sumoselect/sumoselect-rtl.css') }}">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">فريق المشروع</h4><span class="text-muted mt-1 tx-13 mr-2 mb-0">/ {{ isset($user) ? 'تعديل دور عضو' : 'إضافة عضو' }} لمشروع: {{ $project->project_name }}</span>
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
                    <form action="{{ isset($user) ? route('projects.team.update', [$project->id, $user->id]) : route('projects.team.store', $project->id) }}" method="post" autocomplete="off">
                        {{ csrf_field() }}
                        @if (isset($user))
                            {{ method_field('patch') }} {{-- أو 'PUT' --}}
                        @endif

                        <div class="row">
                            <div class="col-lg-6">
                                <label for="user_id" class="control-label">اسم العضو <span class="text-danger">*</span></label>
                                @if (isset($user))
                                    {{-- في وضع التعديل، لا يمكن تغيير المستخدم، فقط دوره --}}
                                    <input type="text" class="form-control" value="{{ $user->name }}" readonly>
                                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                                @else
                                    {{-- في وضع الإضافة، اختر المستخدم من القائمة المتاحة --}}
                                    <select name="user_id" id="user_id" class="form-control SlectBox" required>
                                        <option value="" selected disabled>اختر العضو</option>
                                        @foreach ($availableUsers as $availableUser)
                                            <option value="{{ $availableUser->id }}" {{ (old('user_id') == $availableUser->id) ? 'selected' : '' }}>
                                                {{ $availableUser->name }} ({{ $availableUser->role }})
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div class="col-lg-6">
                                <label for="role_in_project" class="control-label">الدور في المشروع</label>
                                <select name="role_in_project" id="role_in_project" class="form-control SlectBox">
                                    <option value="" selected>اختر الدور (اختياري)</option>
                                    @foreach ($rolesInProject as $role)
                                        <option value="{{ $role }}" {{ (old('role_in_project', $currentRole ?? '') == $role) ? 'selected' : '' }}>
                                            {{ ucfirst(str_replace('_', ' ', $role)) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="d-flex justify-content-center mt-3">
                            <button type="submit" class="btn btn-primary">{{ isset($user) ? 'تحديث الدور' : 'إضافة العضو' }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endsection

@section('js')
    <script src="{{ URL::asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script>
        $(function() {
            $('.SlectBox').select2({
                minimumResultsForSearch: Infinity
            });
        });
    </script>
    <script src="{{ URL::asset('assets/plugins/sumoselect/jquery.sumoselect.js') }}"></script>
@endsection