<?php

namespace App\Http\Controllers;

use App\Inventory; // تأكد من المسار الصحيح
use App\Material;  // لاستخدام موديل المادة
use App\Warehouse; // لاستخدام موديل المستودع
use App\ActivityLog; // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule; // لاستخدام Rule::unique في التحديث

class InventoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-inventory');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Inventory::with(['material', 'warehouse']);

        // فلاتر البحث
        if ($request->has('material_id') && $request->material_id != '') {
            $query->where('material_id', $request->material_id);
        }
        if ($request->has('warehouse_id') && $request->warehouse_id != '') {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->has('min_quantity') && $request->min_quantity != '') {
            $query->where('quantity', '>=', $request->min_quantity);
        }
        if ($request->has('max_quantity') && $request->max_quantity != '') {
            $query->where('quantity', '<=', $request->max_quantity);
        }

        $inventoryRecords = $query->latest()->paginate(10);

        // جلب البيانات اللازمة للفلاتر
        $materials = Material::all();
        $warehouses = Warehouse::all();

        return view('inventory.index', compact('inventoryRecords', 'materials', 'warehouses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $inventory = new Inventory(); // كائن Inventory فارغ للنموذج
        $materials = Material::all();
        $warehouses = Warehouse::all();

        return view('inventory.create_edit', compact('inventory', 'materials', 'warehouses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            // تحقق مما إذا كان هناك سجل مخزون موجود لهذه المادة في هذا المستودع
            $inventory = Inventory::where('material_id', $request->material_id)
                                  ->where('warehouse_id', $request->warehouse_id)
                                  ->first();

            $message = ''; // تهيئة رسالة النجاح

            if ($inventory) {
                // إذا كان السجل موجودًا، قم بتحديث الكمية
                $oldValues = $inventory->toArray(); // <== احصل على القيم القديمة قبل التحديث
                $oldQuantity = $inventory->quantity;
                $inventory->quantity += $request->quantity; // إضافة الكمية الجديدة
                $inventory->cost_price = $request->cost_price; // تحديث سعر التكلفة
                $inventory->save();

                $newValues = $inventory->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

                // تسجيل النشاط للتحديث
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'target_type' => 'App\Inventory',
                    'target_id' => $inventory->id,
                    'description' => 'تم تحديث كمية المادة ' . ($inventory->material->name ?? 'غير محدد') . ' في المستودع ' . ($inventory->warehouse->name ?? 'غير محدد') . ' من ' . $oldQuantity . ' إلى ' . $inventory->quantity,
                ]);

                // تسجيل التدقيق التفصيلي (Audit Log)
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'auditable_type' => get_class($inventory),
                    'auditable_id' => $inventory->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);

                $message = 'تم تحديث كمية المادة في المخزون بنجاح.';
            } else {
                // إذا لم يكن السجل موجودًا، قم بإنشاء سجل جديد
                $inventory = Inventory::create([
                    'material_id' => $request->material_id,
                    'warehouse_id' => $request->warehouse_id,
                    'quantity' => $request->quantity,
                    'cost_price' => $request->cost_price,
                ]);

                // تسجيل النشاط للإنشاء
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'created',
                    'target_type' => 'App\Inventory',
                    'target_id' => $inventory->id,
                    'description' => 'تم إضافة مادة ' . ($inventory->material->name ?? 'غير محدد') . ' بكمية ' . $inventory->quantity . ' إلى المستودع ' . ($inventory->warehouse->name ?? 'غير محدد'),
                ]);

                // تسجيل التدقيق التفصيلي (Audit Log)
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'created',
                    'auditable_type' => get_class($inventory),
                    'auditable_id' => $inventory->id,
                    'old_values' => null,
                    'new_values' => $inventory->toArray(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);

                $message = 'تم إضافة سجل المخزون بنجاح.';
            }

            DB::commit();

            return redirect()->route('inventory.index')->with('success', $message);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error storing inventory record: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء حفظ سجل المخزون: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Inventory $inventory)
    {
        // عادة لا توجد شاشة عرض تفصيلية لسجل المخزون، ولكن يمكن إضافتها إذا لزم الأمر
        return redirect()->route('inventory.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Inventory $inventory)
    {
        $materials = Material::all();
        $warehouses = Warehouse::all();

        return view('inventory.create_edit', compact('inventory', 'materials', 'warehouses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inventory $inventory)
    {
        $oldValues = $inventory->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'material_id' => [
                'required',
                'exists:materials,id',
                // تأكد من أن تركيبة material_id و warehouse_id فريدة باستثناء السجل الحالي
                Rule::unique('inventory')->where(function ($query) use ($request) {
                    return $query->where('warehouse_id', $request->warehouse_id);
                })->ignore($inventory->id),
            ],
            'warehouse_id' => 'required|exists:warehouses,id',
            'quantity' => 'required|integer|min:0',
            'cost_price' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $oldQuantity = $inventory->quantity;
            $oldMaterialName = $inventory->material->name ?? 'غير محدد';
            $oldWarehouseName = $inventory->warehouse->name ?? 'غير محدد';

            $inventory->update([
                'material_id' => $request->material_id,
                'warehouse_id' => $request->warehouse_id,
                'quantity' => $request->quantity,
                'cost_price' => $request->cost_price,
            ]);

            $newValues = $inventory->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Inventory',
                'target_id' => $inventory->id,
                'description' => 'تم تحديث سجل المخزون للمادة ' . ($inventory->material->name ?? 'غير محدد') . ' في المستودع ' . ($inventory->warehouse->name ?? 'غير محدد') . '. الكمية تغيرت من ' . $oldQuantity . ' إلى ' . $inventory->quantity,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'auditable_type' => get_class($inventory),
                'auditable_id' => $inventory->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('inventory.index')->with('success', 'تم تحديث سجل المخزون بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating inventory record: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث سجل المخزون: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inventory $inventory)
    {
        $oldValues = $inventory->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $inventoryId = $inventory->id;
        $materialName = $inventory->material->name ?? 'غير محدد';
        $warehouseName = $inventory->warehouse->name ?? 'غير محدد';
        $quantity = $inventory->quantity;

        try {
            DB::beginTransaction();

            $inventory->delete();

            // تسجيل النشاط
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Inventory',
                'target_id' => $inventoryId,
                'description' => 'تم حذف سجل المخزون للمادة ' . $materialName . ' بكمية ' . $quantity . ' من المستودع ' . $warehouseName,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => 'App\Inventory',
                'auditable_id' => $inventoryId,
                'old_values' => $oldValues,
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('inventory.index')->with('success', 'تم حذف سجل المخزون بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting inventory record: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف سجل المخزون: ' . $e->getMessage());
        }
    }
}
