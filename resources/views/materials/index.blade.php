@extends('layouts.master') {{-- تأكد من اسم القالب الرئيسي الخاص بك --}}

@section('title', 'إدارة المواد - ناينوكس')

@section('css')
    <!-- Internal Data table css -->
    <link href="{{URL::asset('assets/plugins/datatable/css/dataTables.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/buttons.bootstrap4.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.bootstrap4.min.css')}}" rel="stylesheet" />
    <link href="{{URL::asset('assets/plugins/datatable/css/jquery.dataTables.min.css')}}" rel="stylesheet">
    <link href="{{URL::asset('assets/plugins/datatable/css/responsive.dataTables.min.css')}}" rel="stylesheet">
    <!--Internal Select2 css -->
    <link href="{{URL::asset('assets/plugins/select2/css/select2.min.css')}}" rel="stylesheet">
@endsection

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="my-auto">
            <div class="d-flex">
                <h4 class="content-title mb-0 my-auto">المخزون</h4>
                <span class="text-muted mt-1 tx-13 mr-2 mb-0">/ إدارة المواد</span>
            </div>
        </div>
        <div class="d-flex my-xl-auto right-content">
            <a href="{{ route('materials.create') }}" class="btn btn-primary ml-auto"><i class="fas fa-plus"></i> إضافة مادة جديدة</a>
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
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="card-title mb-0">قائمة جميع المواد</h4>
                </div>
                <div class="card-body">
                    {{-- فلتر البحث --}}
                    <form action="{{ route('materials.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3 form-group">
                                <label for="name">اسم المادة:</label>
                                <input type="text" name="name" id="name" class="form-control" value="{{ request('name') }}" placeholder="اسم المادة">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="code">رمز المادة:</label>
                                <input type="text" name="code" id="code" class="form-control" value="{{ request('code') }}" placeholder="رمز المادة">
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="unit_id">الوحدة:</label>
                                <select name="unit_id" id="unit_id" class="form-control select2">
                                    <option value="">كل الوحدات</option>
                                    @foreach($units as $unit)
                                        <option value="{{ $unit->id }}" {{ request('unit_id') == $unit->id ? 'selected' : '' }}>
                                            {{ $unit->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="material_group_id">المجموعة:</label>
                                <select name="material_group_id" id="material_group_id" class="form-control select2">
                                    <option value="">كل المجموعات</option>
                                    @foreach($materialGroups as $group)
                                        <option value="{{ $group->id }}" {{ request('material_group_id') == $group->id ? 'selected' : '' }}>
                                            {{ $group->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="tax_id">الضريبة:</label>
                                <select name="tax_id" id="tax_id" class="form-control select2">
                                    <option value="">كل الضرائب</option>
                                    @foreach($taxes as $tax)
                                        <option value="{{ $tax->id }}" {{ request('tax_id') == $tax->id ? 'selected' : '' }}>
                                            {{ $tax->name }} ({{ number_format($tax->rate, 2) }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-12 form-group d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">بحث</button>
                                <a href="{{ route('materials.index') }}" class="btn btn-secondary mr-2">إعادة تعيين</a>
                            </div>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table class="table text-md-nowrap" id="example1"> {{-- افترض أن لديك جدول بيانات DataTable --}}
                            <thead>
                                <tr>
                                    <th class="wd-5p border-bottom-0">#</th>
                                    <th class="wd-10p border-bottom-0">الصورة</th>
                                    <th class="wd-15p border-bottom-0">الاسم</th>
                                    <th class="wd-10p border-bottom-0">الرمز</th>
                                    <th class="wd-10p border-bottom-0">الوحدة</th>
                                    <th class="wd-10p border-bottom-0">المجموعة</th>
                                    <th class="wd-10p border-bottom-0">الضريبة</th>
                                    <th class="wd-10p border-bottom-0">سعر الشراء</th>
                                    <th class="wd-10p border-bottom-0">سعر البيع</th>
                                    <th class="wd-10p border-bottom-0">المخزون</th>
                                    <th class="wd-15p border-bottom-0">العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($materials as $key => $material)
                                    <tr>
                                        <td>{{ $key + 1 }}</td>
                                        <td>
                                            @if($material->image_url)
                                                <img src="{{ $material->image_url }}" alt="صورة المادة" width="50" height="50" style="border-radius: 5px; object-fit: cover;">
                                            @else
                                                <i class="mdi mdi-image-off-outline text-muted" style="font-size: 24px;"></i>
                                            @endif
                                        </td>
                                        <td>{{ $material->name }}</td>
                                        <td>{{ $material->code ?? 'لا يوجد' }}</td>
                                        <td>{{ $material->unit->name ?? 'غير محددة' }}</td>
                                        <td>{{ $material->materialGroup->name ?? 'غير محددة' }}</td>
                                        <td>{{ $material->tax->name ?? 'غير محددة' }}</td>
                                        <td>{{ number_format($material->purchase_price, 2) }}</td>
                                        <td>{{ number_format($material->sale_price, 2) }}</td>
                                        <td>{{ $material->stock_quantity }}</td>
                                        <td>
                                            <a href="{{ route('materials.edit', $material->id) }}" class="btn btn-sm btn-info"
                                               title="تعديل"><i class="las la-pen"></i></a>

                                            <button type="button" class="btn btn-sm btn-danger" data-toggle="modal"
                                                    data-target="#delete_material{{ $material->id }}" title="حذف"><i
                                                    class="las la-trash"></i></button>
                                        </td>
                                    </tr>

                                    {{-- Delete Modal --}}
                                    <div class="modal fade" id="delete_material{{ $material->id }}" tabindex="-1" role="dialog"
                                        aria-labelledby="exampleModalLabel" aria-hidden="true">
                                        <div class="modal-dialog" role="document">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="exampleModalLabel">حذف مادة</h5>
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true">&times;</span>
                                                    </button>
                                                </div>
                                                <form action="{{ route('materials.destroy', $material->id) }}" method="post">
                                                    {{ method_field('delete') }}
                                                    {{ csrf_field() }}
                                                    <div class="modal-body">
                                                        هل أنت متأكد من عملية حذف المادة: <strong>{{ $material->name }}</strong>؟
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                                                        <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">لا توجد مواد لعرضها.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $materials->links() }} {{-- لروابط التنقل بين الصفحات (pagination) --}}
                    </div>
                </div>
            </div>
        </div>
    @endsection

    @section('js')
        <!-- Internal Data tables -->
        <script src="{{URL::asset('assets/plugins/datatable/js/jquery.dataTables.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.dataTables.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.responsive.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/responsive.dataTables.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/jquery.dataTables.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.bootstrap4.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.buttons.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/buttons.bootstrap4.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/jszip.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/pdfmake.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/vfs_fonts.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/buttons.html5.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/buttons.print.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/buttons.colVis.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/dataTables.responsive.min.js')}}"></script>
        <script src="{{URL::asset('assets/plugins/datatable/js/responsive.bootstrap4.min.js')}}"></script>
        <!--Internal  Datatable js -->
        <script src="{{URL::asset('assets/js/table-data.js')}}"></script>
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
