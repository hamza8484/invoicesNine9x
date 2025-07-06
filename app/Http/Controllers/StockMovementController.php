<?php

namespace App\Http\Controllers;

use App\StockMovement; // تأكد من المسار الصحيح
use App\Material;      // لاستخدام موديل المادة
use App\Warehouse;     // لاستخدام موديل المستودع
use App\User;          // لاستخدام موديل المستخدم
use App\Inventory;     // لاستخدام موديل المخزون لتحديث الكميات
use App\ActivityLog;   // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;      // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // لاستخدام Carbon لتنسيق الوقت

class StockMovementController extends Controller
{
    // أنواع الحركات المتاحة
    private $transactionTypes = [
        'purchase_receipt' => 'استلام شراء',
        'sale_issue' => 'صرف بيع',
        'transfer_out' => 'تحويل خارج',
        'transfer_in' => 'تحويل داخل',
        'adjustment_in' => 'تسوية بالزيادة',
        'adjustment_out' => 'تسوية بالنقصان',
        'project_issue' => 'صرف لمشروع',
        'project_return' => 'إرجاع من مشروع',
    ];

    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('can:manage-stock-movements'); // يمكنك إضافة صلاحيات لاحقًا
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = StockMovement::with(['material', 'warehouse', 'user']);

        // فلاتر البحث
        if ($request->has('material_id') && $request->material_id != '') {
            $query->where('material_id', $request->material_id);
        }
        if ($request->has('warehouse_id') && $request->warehouse_id != '') {
            $query->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->has('transaction_type') && $request->transaction_type != '') {
            $query->where('transaction_type', $request->transaction_type);
        }
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('transaction_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('transaction_date', '<=', $request->end_date);
        }
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }

        $stockMovements = $query->latest()->paginate(15);

        // جلب البيانات اللازمة للفلاتر
        $materials = Material::all();
        $warehouses = Warehouse::all();
        $users = User::all();
        $transactionTypes = $this->transactionTypes; // استخدام الأنواع المعرفة في المتحكم

        return view('stock_movements.index', compact('stockMovements', 'materials', 'warehouses', 'users', 'transactionTypes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $stockMovement = new StockMovement(); // كائن StockMovement فارغ للنموذج
        $materials = Material::all();
        $warehouses = Warehouse::all();
        $users = User::all(); // للمستخدم الذي قام بالحركة
        $transactionTypes = $this->transactionTypes;

        return view('stock_movements.create_edit', compact('stockMovement', 'materials', 'warehouses', 'users', 'transactionTypes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'transaction_type' => 'required|in:' . implode(',', array_keys($this->transactionTypes)),
            'quantity' => 'required|integer|min:1', // الكمية يجب أن تكون موجبة
            'unit_cost' => 'nullable|numeric|min:0',
            'transaction_date' => 'required|date',
            'reference_type' => 'nullable|string|max:255', // يمكن أن تكون App\Models\Invoice
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $material = Material::find($request->material_id);
            $warehouse = Warehouse::find($request->warehouse_id);

            // حساب total_cost
            $unitCost = $request->unit_cost ?? $material->purchase_price; // استخدام سعر الشراء للمادة كافتراضي
            $totalCost = $request->quantity * $unitCost;

            $stockMovement = StockMovement::create([
                'material_id' => $request->material_id,
                'warehouse_id' => $request->warehouse_id,
                'transaction_type' => $request->transaction_type,
                'quantity' => $request->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'transaction_date' => $request->transaction_date,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'notes' => $request->notes,
                'user_id' => $request->user_id,
            ]);

            // تحديث جدول المخزون (Inventory)
            $inventory = Inventory::firstOrNew([
                'material_id' => $request->material_id,
                'warehouse_id' => $request->warehouse_id,
            ]);

            $oldInventoryQuantity = $inventory->quantity ?? 0;

            if ($stockMovement->isIncrease()) {
                $inventory->quantity += $request->quantity;
            } elseif ($stockMovement->isDecrease()) {
                // تأكد من أن الكمية لا تصبح سالبة
                if ($inventory->quantity < $request->quantity) {
                    throw new \Exception('الكمية المتوفرة غير كافية لإتمام عملية الصرف.');
                }
                $inventory->quantity -= $request->quantity;
            }

            $inventory->cost_price = $unitCost; // يمكنك هنا تطبيق منطق متوسط التكلفة إذا أردت
            $inventory->save();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\StockMovement',
                'target_id' => $stockMovement->id,
                'description' => 'تم تسجيل حركة مخزون نوع ' . $stockMovement->transaction_type_name . ' للمادة ' . ($material->name ?? 'غير محدد') . ' بكمية ' . $stockMovement->quantity . ' في المستودع ' . ($warehouse->name ?? 'غير محدد') . '. الكمية الجديدة في المخزون: ' . $inventory->quantity,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'auditable_type' => get_class($stockMovement),
                'auditable_id' => $stockMovement->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $stockMovement->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('stock_movements.index')->with('success', 'تم إضافة حركة المخزون بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error storing stock movement: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة حركة المخزون: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(StockMovement $stockMovement)
    {
        return redirect()->route('stock_movements.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * ملاحظة: تعديل حركات المخزون بعد إنشائها قد يكون معقدًا ويؤثر على المخزون الحالي.
     * في الأنظمة الحقيقية، يفضل إنشاء "حركة تسوية" بدلاً من تعديل حركة سابقة.
     * لهذا السبب، قد تحتاج إلى تبسيط هذه الدالة أو إزالتها إذا كنت لا تريد السماح بالتعديل المباشر.
     */
    public function edit(StockMovement $stockMovement)
    {
        // في نظام حقيقي، قد لا تسمح بتعديل حركة مخزون مباشرة بعد إنشائها
        // بل تقوم بإنشاء حركة تسوية (adjustment) لعكس التغيير أو تصحيحه.
        // هذا الكود يوفر إمكانية التعديل، لكن كن حذرًا من تأثيره على المخزون.

        $materials = Material::all();
        $warehouses = Warehouse::all();
        $users = User::all();
        $transactionTypes = $this->transactionTypes;

        return view('stock_movements.create_edit', compact('stockMovement', 'materials', 'warehouses', 'users', 'transactionTypes'));
    }

    /**
     * Update the specified resource in storage.
     *
     * ملاحظة: تعديل حركات المخزون بعد إنشائها قد يكون معقدًا ويؤثر على المخزون الحالي.
     * في الأنظمة الحقيقية، يفضل إنشاء "حركة تسوية" بدلاً من تعديل حركة سابقة.
     * لهذا السبب، قد تحتاج إلى تبسيط هذه الدالة أو إزالتها إذا كنت لا تريد السماح بالتعديل المباشر.
     */
    public function update(Request $request, StockMovement $stockMovement)
    {
        $oldValues = $stockMovement->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'material_id' => 'required|exists:materials,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'transaction_type' => 'required|in:' . implode(',', array_keys($this->transactionTypes)),
            'quantity' => 'required|integer|min:1',
            'unit_cost' => 'nullable|numeric|min:0',
            'transaction_date' => 'required|date',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
        ]);

        try {
            DB::beginTransaction();

            $oldQuantity = $stockMovement->quantity;
            $oldTransactionType = $stockMovement->transaction_type;
            $oldMaterialId = $stockMovement->material_id;
            $oldWarehouseId = $stockMovement->warehouse_id;

            // أولاً: عكس تأثير الحركة القديمة على المخزون
            $oldInventory = Inventory::where('material_id', $oldMaterialId)
                                     ->where('warehouse_id', $oldWarehouseId)
                                     ->first();

            if ($oldInventory) {
                if ($stockMovement->isIncrease()) { // إذا كانت الحركة القديمة زيادة، فنقصها الآن
                    $oldInventory->quantity -= $oldQuantity;
                } elseif ($stockMovement->isDecrease()) { // إذا كانت الحركة القديمة نقصان، فزدها الآن
                    $oldInventory->quantity += $oldQuantity;
                }
                $oldInventory->save();
            }

            // ثانياً: تحديث حركة المخزون نفسها
            $material = Material::find($request->material_id);
            $warehouse = Warehouse::find($request->warehouse_id);

            $unitCost = $request->unit_cost ?? $material->purchase_price;
            $totalCost = $request->quantity * $unitCost;

            $stockMovement->update([
                'material_id' => $request->material_id,
                'warehouse_id' => $request->warehouse_id,
                'transaction_type' => $request->transaction_type,
                'quantity' => $request->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'transaction_date' => $request->transaction_date,
                'reference_type' => $request->reference_type,
                'reference_id' => $request->reference_id,
                'notes' => $request->notes,
                'user_id' => $request->user_id,
            ]);

            // ثالثاً: تطبيق تأثير الحركة الجديدة على المخزون
            $newInventory = Inventory::firstOrNew([
                'material_id' => $request->material_id,
                'warehouse_id' => $request->warehouse_id,
            ]);

            if ($stockMovement->isIncrease()) {
                $newInventory->quantity += $request->quantity;
            } elseif ($stockMovement->isDecrease()) {
                if ($newInventory->quantity < $request->quantity) {
                    throw new \Exception('الكمية المتوفرة غير كافية لإتمام عملية الصرف الجديدة.');
                }
                $newInventory->quantity -= $request->quantity;
            }
            $newInventory->cost_price = $unitCost;
            $newInventory->save();

            $newValues = $stockMovement->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\StockMovement',
                'target_id' => $stockMovement->id,
                'description' => 'تم تحديث حركة المخزون للمادة ' . ($material->name ?? 'غير محدد') . ' في المستودع ' . ($warehouse->name ?? 'غير محدد') . '. الكمية الجديدة في المخزون: ' . $newInventory->quantity,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'auditable_type' => get_class($stockMovement),
                'auditable_id' => $stockMovement->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('stock_movements.index')->with('success', 'تم تحديث حركة المخزون بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating stock movement: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث حركة المخزون: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * ملاحظة: حذف حركة المخزون يؤثر أيضًا على المخزون الحالي.
     * في الأنظمة الحقيقية، يفضل إنشاء "حركة تسوية" لعكس الحذف بدلاً من الحذف المباشر.
     */
    public function destroy(StockMovement $stockMovement, Request $request) // <== أضف Request $request
    {
        $oldValues = $stockMovement->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $movementId = $stockMovement->id;
        $materialName = $stockMovement->material->name ?? 'غير محدد';
        $warehouseName = $stockMovement->warehouse->name ?? 'غير محدد';
        $quantity = $stockMovement->quantity;
        $transactionType = $stockMovement->transaction_type_name;

        try {
            DB::beginTransaction();

            // عكس تأثير الحركة على المخزون قبل الحذف
            $inventory = Inventory::where('material_id', $stockMovement->material_id)
                                 ->where('warehouse_id', $stockMovement->warehouse_id)
                                 ->first();

            if ($inventory) {
                if ($stockMovement->isIncrease()) { // إذا كانت الحركة زيادة، فنقصها الآن
                    $inventory->quantity -= $quantity;
                } elseif ($stockMovement->isDecrease()) { // إذا كانت الحركة نقصان، فزدها الآن
                    $inventory->quantity += $quantity;
                }
                $inventory->save();
            }

            $stockMovement->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\StockMovement',
                'target_id' => $movementId,
                'description' => 'تم حذف حركة مخزون نوع ' . $transactionType . ' للمادة ' . $materialName . ' بكمية ' . $quantity . ' من المستودع ' . $warehouseName . '. الكمية الجديدة في المخزون: ' . ($inventory->quantity ?? 'غير معروف'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => 'App\StockMovement',
                'auditable_id' => $movementId,
                'old_values' => $oldValues,
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('stock_movements.index')->with('success', 'تم حذف حركة المخزون بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting stock movement: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف حركة المخزون: ' . $e->getMessage());
        }
    }
}
