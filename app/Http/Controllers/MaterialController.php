<?php

namespace App\Http\Controllers;

use App\Material; // تأكد من المسار الصحيح
use App\Unit; // تأكد من المسار الصحيح
use App\MaterialGroup; // تأكد من المسار الصحيح
use App\Tax; // تأكد من المسار الصحيح
use App\ActivityLog; // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage; // لاستخدام نظام الملفات
use Illuminate\Validation\Rule; // لاستخدام Rule::unique في التحديث

class MaterialController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-materials');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Material::with(['unit', 'materialGroup', 'tax']);

        // فلاتر البحث
        if ($request->has('name') && $request->name != '') {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        if ($request->has('code') && $request->code != '') {
            $query->where('code', 'like', '%' . $request->code . '%');
        }
        if ($request->has('unit_id') && $request->unit_id != '') {
            $query->where('unit_id', $request->unit_id);
        }
        if ($request->has('material_group_id') && $request->material_group_id != '') {
            $query->where('material_group_id', $request->material_group_id);
        }
        if ($request->has('tax_id') && $request->tax_id != '') {
            $query->where('tax_id', $request->tax_id);
        }

        $materials = $query->latest()->paginate(10); // إضافة pagination

        // جلب البيانات اللازمة للفلاتر
        $units = Unit::all();
        $materialGroups = MaterialGroup::all();
        $taxes = Tax::all();

        return view('materials.index', compact('materials', 'units', 'materialGroups', 'taxes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $material = new Material(); // كائن Material فارغ للنموذج
        $units = Unit::all();
        $materialGroups = MaterialGroup::all();
        $taxes = Tax::all();

        return view('materials.create_edit', compact('material', 'units', 'materialGroups', 'taxes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:materials,code',
            'unit_id' => 'nullable|exists:units,id',
            'material_group_id' => 'nullable|exists:material_groups,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB Max
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('public/materials'); // حفظ الصورة في storage/app/public/materials
                $imagePath = str_replace('public/', '', $imagePath); // حفظ المسار بدون public/
            }

            $material = Material::create([
                'name' => $request->name,
                'code' => $request->code,
                'unit_id' => $request->unit_id,
                'material_group_id' => $request->material_group_id,
                'tax_id' => $request->tax_id,
                'purchase_price' => $request->purchase_price,
                'sale_price' => $request->sale_price,
                'stock_quantity' => $request->stock_quantity,
                'image' => $imagePath,
                'description' => $request->description,
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Material',
                'target_id' => $material->id,
                'description' => 'تم إنشاء مادة جديدة: ' . $material->name . ' (الرمز: ' . ($material->code ?? 'لا يوجد') . ')',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'auditable_type' => get_class($material),
                'auditable_id' => $material->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $material->toArray(), // جميع قيم المادة الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('materials.index')->with('success', 'تم إضافة المادة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating material: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المادة: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Material $material)
    {
        // عادة لا توجد شاشة عرض تفصيلية للمادة، ولكن يمكن إضافتها إذا لزم الأمر
        return redirect()->route('materials.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Material $material)
    {
        $units = Unit::all();
        $materialGroups = MaterialGroup::all();
        $taxes = Tax::all();

        return view('materials.create_edit', compact('material', 'units', 'materialGroups', 'taxes'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Material $material)
    {
        $oldValues = $material->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('materials', 'code')->ignore($material->id), // استثناء المادة الحالية
            ],
            'unit_id' => 'nullable|exists:units,id',
            'material_group_id' => 'nullable|exists:material_groups,id',
            'tax_id' => 'nullable|exists:taxes,id',
            'purchase_price' => 'required|numeric|min:0',
            'sale_price' => 'required|numeric|min:0',
            'stock_quantity' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // 2MB Max
            'description' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $imagePath = $material->image; // احتفظ بالصورة القديمة افتراضياً

            if ($request->hasFile('image')) {
                // حذف الصورة القديمة إذا كانت موجودة
                if ($material->image && Storage::disk('public')->exists($material->image)) {
                    Storage::disk('public')->delete($material->image);
                }
                $imagePath = $request->file('image')->store('public/materials');
                $imagePath = str_replace('public/', '', $imagePath);
            } elseif ($request->input('clear_image')) { // إذا طلب المستخدم حذف الصورة
                if ($material->image && Storage::disk('public')->exists($material->image)) {
                    Storage::disk('public')->delete($material->image);
                }
                $imagePath = null;
            }

            $material->update([
                'name' => $request->name,
                'code' => $request->code,
                'unit_id' => $request->unit_id,
                'material_group_id' => $request->material_group_id,
                'tax_id' => $request->tax_id,
                'purchase_price' => $request->purchase_price,
                'sale_price' => $request->sale_price,
                'stock_quantity' => $request->stock_quantity,
                'image' => $imagePath,
                'description' => $request->description,
            ]);

            $newValues = $material->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Material',
                'target_id' => $material->id,
                'description' => 'تم تحديث المادة: ' . $material->name . ' (الرمز: ' . ($material->code ?? 'لا يوجد') . ')',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'auditable_type' => get_class($material),
                'auditable_id' => $material->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('materials.index')->with('success', 'تم تحديث المادة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating material: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث المادة: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Material $material)
    {
        $oldValues = $material->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $materialName = $material->name;
        $materialId = $material->id;
        $materialImage = $material->image; // احتفظ بمسار الصورة للحذف

        try {
            DB::beginTransaction();

            // حذف الصورة المرتبطة بالمادة قبل حذف المادة نفسها
            if ($materialImage && Storage::disk('public')->exists($materialImage)) {
                Storage::disk('public')->delete($materialImage);
            }

            $material->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Material',
                'target_id' => $materialId,
                'description' => 'تم حذف المادة: ' . $materialName,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => 'App\Material',
                'auditable_id' => $materialId,
                'old_values' => $oldValues,
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('materials.index')->with('success', 'تم حذف المادة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting material: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المادة: ' . $e->getMessage());
        }
    }
}
