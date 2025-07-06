<?php

namespace App\Http\Controllers;

use App\Warehouse; // تأكد من المسار الصحيح
use App\ActivityLog; // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // لاستخدام Rule::unique في التحديث

class WarehouseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-warehouses');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Warehouse::query();

        // فلاتر البحث
        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->has('location') && $request->location != '') {
            $query->where('location', 'like', '%' . $request->location . '%');
        }
        if ($request->has('is_active') && $request->is_active != '') {
            $query->where('is_active', (bool)$request->is_active);
        }

        $warehouses = $query->latest()->paginate(10);

        return view('warehouses.index', compact('warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $warehouse = new Warehouse(); // كائن Warehouse فارغ للنموذج
        return view('warehouses.create_edit', compact('warehouse'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:warehouses,code',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean', // سيتم التحقق من وجوده كـ checkbox
        ]);

        try {
            DB::beginTransaction();

            $warehouse = Warehouse::create([
                'name' => $request->name,
                'code' => $request->code,
                'location' => $request->location,
                'description' => $request->description,
                'is_active' => $request->has('is_active'), // يتم تعيينها بناءً على وجود الحقل في الـ request
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Warehouse',
                'target_id' => $warehouse->id,
                'description' => 'تم إنشاء مستودع جديد: ' . $warehouse->name . ' (الرمز: ' . ($warehouse->code ?? 'لا يوجد') . ')',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($warehouse),
                'auditable_id' => $warehouse->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $warehouse->toArray(), // جميع قيم المستودع الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('warehouses.index')->with('success', 'تم إضافة المستودع بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating warehouse: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المستودع: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Warehouse $warehouse)
    {
        // عادة لا توجد شاشة عرض تفصيلية للمستودع، ولكن يمكن إضافتها إذا لزم الأمر
        return redirect()->route('warehouses.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.create_edit', compact('warehouse'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Warehouse $warehouse)
    {
        $oldValues = $warehouse->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')->ignore($warehouse->id), // استثناء المستودع الحالي
            ],
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();

            $warehouse->update([
                'name' => $request->name,
                'code' => $request->code,
                'location' => $request->location,
                'description' => $request->description,
                'is_active' => $request->has('is_active'), // يتم تعيينها بناءً على وجود الحقل في الـ request
            ]);

            $newValues = $warehouse->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Warehouse',
                'target_id' => $warehouse->id,
                'description' => 'تم تحديث المستودع: ' . $warehouse->name . ' (الرمز: ' . ($warehouse->code ?? 'لا يوجد') . ')',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($warehouse),
                'auditable_id' => $warehouse->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('warehouses.index')->with('success', 'تم تحديث المستودع بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating warehouse: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث المستودع: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Warehouse $warehouse, Request $request) // <== أضف Request $request
    {
        $oldValues = $warehouse->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $warehouseName = $warehouse->name;
        $warehouseId = $warehouse->id;

        try {
            DB::beginTransaction();

            $warehouse->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Warehouse',
                'target_id' => $warehouseId,
                'description' => 'تم حذف المستودع: ' . $warehouseName,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Warehouse',
                'auditable_id' => $warehouseId,
                'old_values' => $oldValues, // القيم القديمة للمستودع الذي تم حذفه
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => $request->ip(), // <== استخدام $request لجلب IP
                'user_agent' => $request->header('User-Agent'), // <== استخدام $request لجلب User-Agent
            ]);

            DB::commit();

            return redirect()->route('warehouses.index')->with('success', 'تم حذف المستودع بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting warehouse: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المستودع: ' . $e->getMessage());
        }
    }
}
