<?php

namespace App\Http\Controllers;

use App\PurchaseInvoice;
use App\PurchaseInvoiceItem;
use App\Supplier;
use App\Material;
use App\Inventory; // لتحديث المخزون
use App\StockMovement; // لتسجيل حركات المخزون
use App\ActivityLog;
use App\AuditLog;
use App\Warehouse;
use App\PurchasePayment;
use App\User;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class PurchaseInvoiceController extends Controller
{
    // أنواع حركات المخزون ذات الصلة بفواتير الشراء
    private $purchaseMovementType = 'purchase_receipt'; // استلام شراء

    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('can:manage-purchase-invoices'); // يمكنك إضافة صلاحيات لاحقًا
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PurchaseInvoice::with('supplier');

        // فلاتر البحث
        if ($request->has('invoice_number') && $request->invoice_number != '') {
            $query->where('invoice_number', 'like', '%' . $request->invoice_number . '%');
        }
        if ($request->has('supplier_id') && $request->supplier_id != '') {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('issue_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('issue_date', '<=', $request->end_date);
        }

        $purchaseInvoices = $query->latest()->paginate(10);
        $suppliers = Supplier::all(); // لجلب قائمة الموردين للفلاتر
        $statuses = ['unpaid' => 'غير مدفوعة', 'paid' => 'مدفوعة بالكامل', 'partial' => 'مدفوعة جزئياً'];

        return view('purchase_invoices.index', compact('purchaseInvoices', 'suppliers', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $purchaseInvoice = new PurchaseInvoice(); // كائن فاتورة شراء فارغ للنموذج
        $suppliers = Supplier::orderBy('name')->get();
        $materials = Material::orderBy('name')->get(); // لجلب المواد لبنود الفاتورة

        // توليد رقم فاتورة شراء فريد
        $newInvoiceNumber = $this->generateUniquePurchaseInvoiceNumber();

        // يمكننا أيضاً تمرير بنود فاتورة فارغة لبداية ديناميكية في الـ Blade
        $purchaseInvoiceItems = collect(); // كولكشن فارغ من بنود الفاتورة

        return view('purchase_invoices.create_edit', compact('purchaseInvoice', 'suppliers', 'materials', 'newInvoiceNumber', 'purchaseInvoiceItems'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:255|unique:purchase_invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0', // سيتم حسابه في الواجهة
            'total' => 'required|numeric|min:0',    // سيتم حسابه في الواجهة
            'paid_amount' => 'nullable|numeric|min:0',
            'due_amount' => 'required|numeric',
            'status' => 'required|string|in:unpaid,partial,paid',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.total' => 'required|numeric', // سيتم حسابه في الواجهة
        ]);

        try {
            DB::beginTransaction();

            // تحديد الحالة النهائية للفاتورة
            $finalStatus = 'unpaid';
            if ($validatedData['paid_amount'] >= $validatedData['total'] && $validatedData['total'] > 0) {
                $finalStatus = 'paid';
            } elseif ($validatedData['paid_amount'] > 0 && $validatedData['paid_amount'] < $validatedData['total']) {
                $finalStatus = 'partial';
            }

            $purchaseInvoice = PurchaseInvoice::create([
                'supplier_id' => $validatedData['supplier_id'],
                'invoice_number' => $validatedData['invoice_number'],
                'issue_date' => $validatedData['issue_date'],
                'due_date' => $validatedData['due_date'],
                'subtotal' => $validatedData['subtotal'],
                'discount' => $validatedData['discount'],
                'tax' => $validatedData['tax'],
                'total' => $validatedData['total'],
                'paid_amount' => $validatedData['paid_amount'],
                'due_amount' => $validatedData['due_amount'],
                'status' => $finalStatus,
                'notes' => $validatedData['notes'],
            ]);

            // إضافة بنود فاتورة الشراء وتحديث المخزون
            foreach ($validatedData['items'] as $itemData) {
                $itemTotal = $itemData['quantity'] * $itemData['unit_price'];

                $purchaseInvoiceItem = $purchaseInvoice->items()->create([
                    'material_id' => $itemData['material_id'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $itemTotal,
                ]);

                // تحديث المخزون وتسجيل حركة المخزون
                $this->updateInventoryAndStockMovement(
                    $purchaseInvoiceItem->material_id,
                    $purchaseInvoiceItem->quantity,
                    $purchaseInvoiceItem->unit_price,
                    $this->purchaseMovementType, // نوع الحركة: استلام شراء
                    $purchaseInvoice->id,
                    get_class($purchaseInvoice)
                );
            }

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\PurchaseInvoice',
                'target_id' => $purchaseInvoice->id,
                'description' => 'تم إنشاء فاتورة شراء جديدة رقم: ' . $purchaseInvoice->invoice_number . ' للمورد: ' . ($purchaseInvoice->supplier->name ?? 'غير معروف') . ' بقيمة: ' . $purchaseInvoice->total,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'auditable_type' => get_class($purchaseInvoice),
                'auditable_id' => $purchaseInvoice->id,
                'old_values' => null,
                'new_values' => $purchaseInvoice->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('purchase_invoices.index')->with('success', 'تم إنشاء فاتورة الشراء بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating purchase invoice: ' . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'حدث خطأ أثناء حفظ فاتورة الشراء: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseInvoice $purchaseInvoice)
        {
            // هذه الأسطر حاسمة لتحميل العلاقات
            $purchaseInvoice->load(['supplier', 'items.material', 'payments.user']);

            // يمكنك إضافة dd() هنا للتحقق من البيانات قبل عرضها
            // dd($purchaseInvoice->items, $purchaseInvoice->payments);

            return view('purchase_invoices.show', compact('purchaseInvoice'));
        }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load('items.material'); // تحميل بنود الفاتورة والمواد المرتبطة بها
        $suppliers = Supplier::orderBy('name')->get();
        $materials = Material::orderBy('name')->get();

        $purchaseInvoiceItems = $purchaseInvoice->items; // بنود الفاتورة الحالية

        return view('purchase_invoices.create_edit', compact('purchaseInvoice', 'suppliers', 'materials', 'purchaseInvoiceItems'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseInvoice $purchaseInvoice)
    {
        $oldValues = $purchaseInvoice->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $validatedData = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchase_invoices')->ignore($purchaseInvoice->id),
            ],
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_amount' => 'required|numeric',
            'status' => 'required|string|in:unpaid,partial,paid',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:purchase_invoice_items,id', // للسماح بتحديث البنود الموجودة
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.total' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            // 1. تحديد الحالة النهائية للفاتورة بناءً على paid_amount و total
            $finalStatus = 'unpaid';
            if ($validatedData['paid_amount'] >= $validatedData['total'] && $validatedData['total'] > 0) {
                $finalStatus = 'paid';
            } elseif ($validatedData['paid_amount'] > 0 && $validatedData['paid_amount'] < $validatedData['total']) {
                $finalStatus = 'partial';
            }

            // 2. تحديث فاتورة الشراء الرئيسية
            $purchaseInvoice->update([
                'supplier_id' => $validatedData['supplier_id'],
                'invoice_number' => $validatedData['invoice_number'],
                'issue_date' => $validatedData['issue_date'],
                'due_date' => $validatedData['due_date'],
                'subtotal' => $validatedData['subtotal'],
                'discount' => $validatedData['discount'],
                'tax' => $validatedData['tax'],
                'total' => $validatedData['total'],
                'paid_amount' => $validatedData['paid_amount'],
                'due_amount' => $validatedData['due_amount'],
                'status' => $finalStatus,
                'notes' => $validatedData['notes'],
            ]);

            // 3. تحديث بنود الفاتورة والمخزون
            $submittedItemIds = collect($validatedData['items'])->pluck('id')->filter()->all();
            $existingItems = $purchaseInvoice->items()->get()->keyBy('id');

            // حذف البنود التي لم تعد موجودة في النموذج
            foreach ($existingItems as $itemId => $existingItem) {
                if (!in_array($itemId, $submittedItemIds)) {
                    // عكس تأثير البند المحذوف على المخزون
                    $this->updateInventoryAndStockMovement(
                        $existingItem->material_id,
                        $existingItem->quantity,
                        $existingItem->unit_price,
                        'adjustment_out', // نوع الحركة: تسوية بالنقصان (لإزالة ما تم إضافته سابقاً)
                        $purchaseInvoice->id,
                        get_class($purchaseInvoice)
                    );
                    $existingItem->delete();
                }
            }

            // إضافة أو تحديث البنود
            foreach ($validatedData['items'] as $itemData) {
                $itemTotal = $itemData['quantity'] * $itemData['unit_price'];

                if (isset($itemData['id']) && $existingItems->has($itemData['id'])) {
                    // تحديث بند موجود
                    $existingItem = $existingItems->get($itemData['id']);
                    $oldQuantity = $existingItem->quantity;
                    $newQuantity = $itemData['quantity'];

                    $existingItem->update([
                        'material_id' => $itemData['material_id'],
                        'quantity' => $newQuantity,
                        'unit_price' => $itemData['unit_price'],
                        'total' => $itemTotal,
                    ]);

                    // تعديل المخزون بناءً على الفرق
                    if ($newQuantity > $oldQuantity) {
                        $quantityDiff = $newQuantity - $oldQuantity;
                        $this->updateInventoryAndStockMovement(
                            $existingItem->material_id,
                            $quantityDiff,
                            $existingItem->unit_price,
                            'adjustment_in', // زيادة
                            $purchaseInvoice->id,
                            get_class($purchaseInvoice)
                        );
                    } elseif ($newQuantity < $oldQuantity) {
                        $quantityDiff = $oldQuantity - $newQuantity;
                        $this->updateInventoryAndStockMovement(
                            $existingItem->material_id,
                            $quantityDiff,
                            $existingItem->unit_price,
                            'adjustment_out', // نقصان
                            $purchaseInvoice->id,
                            get_class($purchaseInvoice)
                        );
                    }
                } else {
                    // إضافة بند جديد
                    $purchaseInvoiceItem = $purchaseInvoice->items()->create([
                        'material_id' => $itemData['material_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total' => $itemTotal,
                    ]);

                    // تحديث المخزون وتسجيل حركة المخزون للبنود الجديدة
                    $this->updateInventoryAndStockMovement(
                        $purchaseInvoiceItem->material_id,
                        $purchaseInvoiceItem->quantity,
                        $purchaseInvoiceItem->unit_price,
                        $this->purchaseMovementType, // نوع الحركة: استلام شراء
                        $purchaseInvoice->id,
                        get_class($purchaseInvoice)
                    );
                }
            }

            $newValues = $purchaseInvoice->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\PurchaseInvoice',
                'target_id' => $purchaseInvoice->id,
                'description' => 'تم تحديث فاتورة الشراء رقم: ' . $purchaseInvoice->invoice_number . ' للمورد: ' . ($purchaseInvoice->supplier->name ?? 'غير معروف') . ' بقيمة: ' . $purchaseInvoice->total,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'auditable_type' => get_class($purchaseInvoice),
                'auditable_id' => $purchaseInvoice->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('purchase_invoices.index')->with('success', 'تم تحديث فاتورة الشراء بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating purchase invoice: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'حدث خطأ أثناء تحديث فاتورة الشراء: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseInvoice $purchaseInvoice, Request $request)
    {
        $oldValues = $purchaseInvoice->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $invoiceNumber = $purchaseInvoice->invoice_number;
        $purchaseInvoiceId = $purchaseInvoice->id;

        try {
            DB::beginTransaction();

            // عكس تأثير جميع بنود الفاتورة على المخزون قبل حذف الفاتورة
            foreach ($purchaseInvoice->items as $item) {
                $this->updateInventoryAndStockMovement(
                    $item->material_id,
                    $item->quantity,
                    $item->unit_price,
                    'adjustment_out', // نوع الحركة: تسوية بالنقصان (لإزالة ما تم إضافته سابقاً)
                    $purchaseInvoice->id,
                    get_class($purchaseInvoice)
                );
            }

            $purchaseInvoice->delete(); // سيتم حذف البنود تلقائياً بسبب onDelete('cascade')

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\PurchaseInvoice',
                'target_id' => $purchaseInvoiceId,
                'description' => 'تم حذف فاتورة الشراء رقم: ' . $invoiceNumber,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => 'App\PurchaseInvoice',
                'auditable_id' => $purchaseInvoiceId,
                'old_values' => $oldValues,
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('purchase_invoices.index')->with('success', 'تم حذف فاتورة الشراء بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting purchase invoice: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف فاتورة الشراء: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to generate a unique purchase invoice number.
     *
     * @return string
     */
    private function generateUniquePurchaseInvoiceNumber(): string
    {
        $lastInvoice = PurchaseInvoice::latest()->first();
        $prefix = 'PI-';
        $number = 1;

        if ($lastInvoice) {
            $lastNumber = (int)str_replace($prefix, '', $lastInvoice->invoice_number);
            $number = $lastNumber + 1;
        }

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Helper function to update inventory and create stock movement.
     *
     * @param int $materialId
     * @param int $quantity
     * @param float $unitCost
     * @param string $transactionType
     * @param int|null $referenceId
     * @param string|null $referenceType
     * @return void
     */
    private function updateInventoryAndStockMovement(
        int $materialId,
        int $quantity,
        float $unitCost,
        string $transactionType,
        ?int $referenceId = null,
        ?string $referenceType = null
    ): void {
        // افتراض: كل المواد تدخل مستودع افتراضي واحد أو يجب تحديد المستودع
        // في هذا المثال، سنفترض مستودعاً واحداً أو نطلب تحديده.
        // لأغراض التبسيط، سنستخدم أول مستودع متاح أو نطلب تحديد المستودع الافتراضي.
        $warehouse = Warehouse::first(); // أو يمكنك تمرير warehouse_id من الـ request

        if (!$warehouse) {
            // يمكنك رمي استثناء أو تسجيل خطأ إذا لم يتم العثور على مستودع
            \Log::error('No default warehouse found for inventory update.');
            return;
        }

        $inventory = Inventory::firstOrNew([
            'material_id' => $materialId,
            'warehouse_id' => $warehouse->id,
        ]);

        $oldInventoryQuantity = $inventory->quantity ?? 0;

        // تحديث الكمية في المخزون بناءً على نوع الحركة
        $isIncrease = in_array($transactionType, ['purchase_receipt', 'transfer_in', 'adjustment_in', 'project_return']);
        $isDecrease = in_array($transactionType, ['sale_issue', 'transfer_out', 'adjustment_out', 'project_issue']);

        if ($isIncrease) {
            $inventory->quantity += $quantity;
        } elseif ($isDecrease) {
            // تأكد من أن الكمية لا تصبح سالبة
            if ($inventory->quantity < $quantity) {
                throw new \Exception('الكمية المتوفرة غير كافية في المخزون لإتمام العملية.');
            }
            $inventory->quantity -= $quantity;
        }

        $inventory->cost_price = $unitCost; // يمكن تطبيق منطق متوسط التكلفة هنا
        $inventory->save();

        // إنشاء حركة مخزون
        StockMovement::create([
            'material_id' => $materialId,
            'warehouse_id' => $warehouse->id,
            'user_id' => Auth::id(), // المستخدم الذي قام بالعملية
            'transaction_type' => $transactionType,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'transaction_date' => Carbon::now(),
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => 'حركة مخزون تلقائية من فاتورة شراء.',
        ]);
    }
}

