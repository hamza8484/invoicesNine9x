<?php

namespace App\Http\Controllers;

use App\MaterialGroup; // تأكد من المسار الصحيح
use App\ActivityLog;   // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;      // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // لاستخدام Rule::unique في التحديث

class MaterialGroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-material-groups');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = MaterialGroup::query();

        // فلاتر البحث
        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $materialGroups = $query->latest()->paginate(10); // إضافة pagination

        return view('material_groups.index', compact('materialGroups'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $materialGroup = new MaterialGroup(); // كائن MaterialGroup فارغ للنموذج
        return view('material_groups.create_edit', compact('materialGroup'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:material_groups,name',
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $materialGroup = MaterialGroup::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\MaterialGroup',
                'target_id' => $materialGroup->id,
                'description' => 'تم إنشاء مجموعة أصناف جديدة: ' . $materialGroup->name,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'auditable_type' => get_class($materialGroup),
                'auditable_id' => $materialGroup->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $materialGroup->toArray(), // جميع قيم مجموعة الأصناف الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('material_groups.index')->with('success', 'تم إضافة مجموعة الأصناف بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating material group: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة مجموعة الأصناف: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(MaterialGroup $materialGroup)
    {
        // عادة لا توجد شاشة عرض تفصيلية لمجموعة الأصناف، ولكن يمكن إضافتها إذا لزم الأمر
        return redirect()->route('material_groups.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MaterialGroup $materialGroup)
    {
        return view('material_groups.create_edit', compact('materialGroup'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MaterialGroup $materialGroup)
    {
        $oldValues = $materialGroup->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('material_groups')->ignore($materialGroup->id), // استثناء المجموعة الحالية
            ],
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $materialGroup->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);

            $newValues = $materialGroup->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\MaterialGroup',
                'target_id' => $materialGroup->id,
                'description' => 'تم تحديث مجموعة الأصناف: ' . $materialGroup->name,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'auditable_type' => get_class($materialGroup),
                'auditable_id' => $materialGroup->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('material_groups.index')->with('success', 'تم تحديث مجموعة الأصناف بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating material group: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث مجموعة الأصناف: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MaterialGroup $materialGroup)
    {
        $oldValues = $materialGroup->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $groupName = $materialGroup->name;
        $groupId = $materialGroup->id;

        try {
            DB::beginTransaction();

            $materialGroup->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\MaterialGroup',
                'target_id' => $groupId,
                'description' => 'تم حذف مجموعة الأصناف: ' . $groupName,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => 'App\MaterialGroup',
                'auditable_id' => $groupId,
                'old_values' => $oldValues,
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('material_groups.index')->with('success', 'تم حذف مجموعة الأصناف بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting material group: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف مجموعة الأصناف: ' . $e->getMessage());
        }
    }
}
