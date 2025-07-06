<?php

namespace App\Http\Controllers;

use App\Unit; // تأكد من المسار الصحيح
use App\ActivityLog; // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // لاستخدام Rule::unique في التحديث

class UnitController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-units');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Unit::query();

        // فلاتر البحث
        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('symbol') && $request->symbol != '') {
            $query->where('symbol', 'like', '%' . $request->symbol . '%');
        }

        $units = $query->latest()->paginate(10);

        return view('units.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $unit = new Unit(); // كائن Unit فارغ للنموذج
        return view('units.create_edit', compact('unit'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:units,name',
            'symbol' => 'nullable|string|max:50|unique:units,symbol',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $unit = Unit::create([
                'name' => $request->name,
                'symbol' => $request->symbol,
                'description' => $request->description,
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Unit',
                'target_id' => $unit->id,
                'description' => 'تم إنشاء وحدة جديدة: ' . $unit->name . ' (' . ($unit->symbol ?? 'لا يوجد رمز') . ')',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($unit),
                'auditable_id' => $unit->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $unit->toArray(), // جميع قيم الوحدة الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('units.index')->with('success', 'تم إضافة الوحدة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating unit: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة الوحدة: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        // عادة لا توجد شاشة عرض تفصيلية للوحدة، ولكن يمكن إضافتها إذا لزم الأمر
        return redirect()->route('units.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Unit $unit)
    {
        return view('units.create_edit', compact('unit'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Unit $unit)
    {
        $oldValues = $unit->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('units')->ignore($unit->id), // استثناء الوحدة الحالية
            ],
            'symbol' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('units')->ignore($unit->id), // استثناء الوحدة الحالية
            ],
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $unit->update([
                'name' => $request->name,
                'symbol' => $request->symbol,
                'description' => $request->description,
            ]);

            $newValues = $unit->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Unit',
                'target_id' => $unit->id,
                'description' => 'تم تحديث الوحدة: ' . $unit->name . ' (' . ($unit->symbol ?? 'لا يوجد رمز') . ')',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($unit),
                'auditable_id' => $unit->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('units.index')->with('success', 'تم تحديث الوحدة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating unit: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الوحدة: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Unit $unit, Request $request) // <== أضف Request $request
    {
        $oldValues = $unit->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $unitName = $unit->name;
        $unitId = $unit->id;

        try {
            DB::beginTransaction();

            $unit->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Unit',
                'target_id' => $unitId,
                'description' => 'تم حذف الوحدة: ' . $unitName,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Unit',
                'auditable_id' => $unitId,
                'old_values' => $oldValues, // القيم القديمة للوحدة التي تم حذفها
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => $request->ip(), // <== استخدام $request لجلب IP
                'user_agent' => $request->header('User-Agent'), // <== استخدام $request لجلب User-Agent
            ]);

            DB::commit();

            return redirect()->route('units.index')->with('success', 'تم حذف الوحدة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting unit: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف الوحدة: ' . $e->getMessage());
        }
    }
}
