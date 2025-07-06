<?php

namespace App\Http\Controllers;

use App\Invoice; // <== تأكد من المسار الصحيح: App\Models\Invoice
use App\InvoiceItem;
use App\Client; // <== تأكد من المسار الصحيح: App\Models\Client
use App\Project; // <== تأكد من المسار الصحيح: App\Models\Project
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // لإدارة المعاملات (Transactions)
use Illuminate\Validation\Rule; // لاستخدام قواعد التحقق المتقدمة
use Illuminate\Support\Facades\Auth;


class InvoiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // جلب جميع الفواتير مع العميل والمشروع المرتبطين بها لتحسين الأداء
        $invoices = Invoice::with(['client', 'project'])->latest()->paginate(10); // إضافة pagination
        return view('invoices.index', compact('invoices'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // جلب قائمة بالعملاء والمشاريع لعرضها في القوائم المنسدلة
        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('project_name')->get();

        // إنشاء كائن فاتورة جديد فارغ لنموذج الإنشاء
        $invoice = new Invoice();

        // **استدعاء الدالة المسؤولة عن توليد رقم الفاتورة**
        $newInvoiceNumber = $this->generateUniqueInvoiceNumber(); // ستقوم هذه الدالة بجلب آخر فاتورة وتوليد الرقم الجديد

        // يمكننا أيضاً تمرير بنود فاتورة فارغة لبداية ديناميكية في الـ Blade
        $invoiceItems = collect(); // كولكشن فارغ من بنود الفاتورة

        // تمرير رقم الفاتورة الجديد إلى الـ View
        return view('invoices.create_edit', compact('clients', 'projects', 'invoice', 'invoiceItems', 'newInvoiceNumber'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'invoice_number' => 'required|string|max:255|unique:invoices,invoice_number',
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'required|exists:projects,id',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_amount' => 'required|numeric',
            'payment_method' => 'nullable|string|max:255',
            'status' => 'required|string|in:unpaid,partial,paid',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.total' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            $finalStatus = 'unpaid';
            if ($validatedData['paid_amount'] >= $validatedData['total'] && $validatedData['total'] > 0) {
                $finalStatus = 'paid';
            } elseif ($validatedData['paid_amount'] > 0 && $validatedData['paid_amount'] < $validatedData['total']) {
                $finalStatus = 'partial';
            }

            $invoice = Invoice::create([
                'invoice_number' => $validatedData['invoice_number'],
                'issue_date' => $validatedData['issue_date'],
                'due_date' => $validatedData['due_date'],
                'client_id' => $validatedData['client_id'],
                'project_id' => $validatedData['project_id'],
                'subtotal' => $validatedData['subtotal'],
                'discount' => $validatedData['discount'],
                'tax' => $validatedData['tax'],
                'total' => $validatedData['total'],
                'paid_amount' => $validatedData['paid_amount'],
                'due_amount' => $validatedData['due_amount'],
                'payment_method' => $validatedData['payment_method'],
                'status' => $finalStatus,
                'notes' => $validatedData['notes'],
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Invoice', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $invoice->id,
                'description' => 'تم إنشاء فاتورة جديدة: ' . $invoice->invoice_number . ' بقيمة ' . $invoice->total,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'old_values' => null,
                'new_values' => $invoice->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            foreach ($validatedData['items'] as $itemData) {
                $itemTotal = $itemData['quantity'] * $itemData['unit_price'];

                $invoiceItem = $invoice->items()->create([
                    'description' => $itemData['description'],
                    'quantity' => $itemData['quantity'],
                    'unit_price' => $itemData['unit_price'],
                    'total' => $itemTotal,
                ]);
            }

            DB::commit();

            return redirect()->route('invoices.index')->with('success', 'تم إنشاء الفاتورة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating invoice: ' . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['error' => 'حدث خطأ أثناء حفظ الفاتورة: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        // تأكد من تحميل جميع العلاقات التي تحتاجها لعرضها في تفاصيل الفاتورة
        $invoice->load('client', 'project', 'items', 'attachments', 'payments.user');
        // 'payments.user' لتحميل المستخدم الذي قام بالدفع لكل دفعة
        return view('invoices.show', compact('invoice'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice)
    {
        // جلب قائمة بالعملاء والمشاريع لعرضها في القوائم المنسدلة
        $clients = Client::orderBy('name')->get();
        $projects = Project::orderBy('project_name')->get();

        // جلب بنود الفاتورة المرتبطة بها
        $invoiceItems = $invoice->items;

        return view('invoices.create_edit', compact('clients', 'projects', 'invoice', 'invoiceItems'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $oldValues = $invoice->toArray(); // <== احصل على القيم القديمة قبل التحديث

        // 1. التحقق من صحة البيانات
        $validatedData = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('invoices')->ignore($invoice->id), // تجاهل الفاتورة الحالية عند التحقق من uniqueness
            ],
            'issue_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:issue_date',
            'discount' => 'nullable|numeric|min:0',
            'tax' => 'nullable|numeric|min:0',
            'payment_method' => 'nullable|in:cash,bank_transfer,cheque,card,other',
            'notes' => 'nullable|string',
            // التحقق من بنود الفاتورة
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|exists:invoice_items,id', // للسماح بتحديث البنود الموجودة
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            // 'items.*.total' => 'required|numeric|min:0', // هذا الحقل سيتم حسابه
        ]);

        DB::beginTransaction();

        try {
            // 2. حساب القيم المالية المحدثة
            $subtotal = 0;
            foreach ($validatedData['items'] as $item) {
                $subtotal += ($item['quantity'] * $item['unit_price']);
            }

            $discount_amount = $validatedData['discount'] ?? 0;
            $tax_amount = $validatedData['tax'] ?? 0;

            $total = ($subtotal - $discount_amount) + $tax_amount;
            // لا نعدل paid_amount هنا، سيتم التعامل معه في وظيفة الدفعات لاحقاً
            $due_amount = $total - $invoice->paid_amount; // المبلغ المستحق يعتمد على الإجمالي الجديد والمبلغ المدفوع سابقا

            // تحديث حالة الفاتورة بناءً على paid_amount و total
            if ($invoice->paid_amount >= $total) {
                $status = 'paid';
            } elseif ($invoice->paid_amount > 0 && $invoice->paid_amount < $total) {
                $status = 'partial';
            } else {
                $status = 'unpaid';
            }

            // 3. تحديث الفاتورة
            $invoice->update([
                'project_id' => $validatedData['project_id'],
                'client_id' => $validatedData['client_id'],
                'invoice_number' => $validatedData['invoice_number'],
                'issue_date' => $validatedData['issue_date'],
                'due_date' => $validatedData['due_date'],
                'subtotal' => $subtotal,
                'discount' => $discount_amount,
                'tax' => $tax_amount,
                'total' => $total,
                // 'paid_amount' => $paid_amount, // لا نحدثه هنا
                'due_amount' => $due_amount,
                'payment_method' => $validatedData['payment_method'],
                'status' => $status,
                'notes' => $validatedData['notes'],
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Invoice', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $invoice->id,
                'description' => 'تم تحديث الفاتورة رقم: ' . $invoice->invoice_number . ' بقيمة إجمالية ' . $invoice->total,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'old_values' => $oldValues,
                'new_values' => $invoice->fresh()->toArray(), // <== احصل على القيم الجديدة بعد التحديث
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            // 4. تحديث وحفظ بنود الفاتورة (معقدة قليلاً: إضافة، تحديث، حذف)
            // الحصول على IDs البنود المرسلة من النموذج
            $submittedItemIds = collect($validatedData['items'])->pluck('id')->filter()->all();

            // حذف البنود التي لم تعد موجودة في النموذج
            $invoice->items()->whereNotIn('id', $submittedItemIds)->delete();

            // في دالة update()
            foreach ($validatedData['items'] as $itemData) {
                $itemTotal = $itemData['quantity'] * $itemData['unit_price'];
                if (isset($itemData['id'])) {
                    // تحديث بند موجود
                    $invoice->items()->where('id', $itemData['id'])->update([
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total' => $itemTotal,
                    ]);
                } else {
                    // إضافة بند جديد
                    $invoice->items()->create([
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'total' => $itemTotal,
                    ]);
                }
            }

            DB::commit();

            return redirect()->route('invoices.index')->with('success', 'تم تحديث الفاتورة بنجاح!');

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating invoice: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'حدث خطأ أثناء تحديث الفاتورة: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $oldValues = $invoice->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $invoiceId = $invoice->id;
        $invoiceNumber = $invoice->invoice_number;

        try {
            DB::beginTransaction();

            $invoice->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Invoice', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $invoiceId,
                'description' => 'تم حذف الفاتورة رقم: ' . $invoiceNumber,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => 'App\Invoice',
                'auditable_id' => $invoiceId,
                'old_values' => $oldValues,
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('invoices.index')->with('success', 'تم حذف الفاتورة بنجاح!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting invoice: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف الفاتورة: ' . $e->getMessage());
        }
    }

    //
    // **** دوال مساعدة إضافية قد تحتاجها مستقبلاً ****
    //
    /**
     * لعرض بنود فاتورة معينة (مثلاً عند طباعتها أو إرسالها).
     * @param Invoice $invoice
     * @return \Illuminate\View\View
     */
    public function print(Invoice $invoice)
    {
        $invoice->load(['items', 'client', 'project']);
        return view('invoices.print', compact('invoice'));
    }

    /**
     * تحديث حالة الدفع للفاتورة.
     * يمكن استدعاء هذه الدالة من شاشة الدفعات أو من نموذج منفصل
     * @param Request $request
     * @param Invoice $invoice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updatePaymentStatus(Request $request, Invoice $invoice)
    {
        $oldValues = $invoice->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'paid_amount' => 'required|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $newPaidAmount = $request->input('paid_amount');
            $total = $invoice->total;

            $invoice->paid_amount = $newPaidAmount;
            $invoice->due_amount = $total - $newPaidAmount;

            if ($newPaidAmount >= $total) {
                $invoice->status = 'paid';
            } elseif ($newPaidAmount > 0 && $newPaidAmount < $total) {
                $invoice->status = 'partial';
            } else {
                $invoice->status = 'unpaid';
            }

            $invoice->save();

            $newValues = $invoice->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'payment_status_updated',
                'target_type' => 'App\Invoice',
                'target_id' => $invoice->id,
                'description' => 'تم تحديث حالة دفع الفاتورة رقم: ' . $invoice->invoice_number . ' إلى: ' . $invoice->status,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'payment_status_updated',
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return back()->with('success', 'تم تحديث حالة الدفع بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating invoice payment status: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديث حالة الدفع: ' . $e->getMessage());
        }
    }

    /**
     * توليد رقم فاتورة فريد تلقائياً.
     * يمكن استخدامها في JavaScript أو كدالة مساعدة في المتحكم.
     * @return string
     */
    public function generateUniqueInvoiceNumber()
    {
        $lastInvoice = Invoice::latest()->first();
        $prefix = 'INV-';
        $number = 1; // الرقم الافتراضي إذا لم تكن هناك فواتير سابقة

        if ($lastInvoice) {
            // استخراج الجزء الرقمي بعد الـ 'INV-'
            $lastNumber = (int)str_replace($prefix, '', $lastInvoice->invoice_number);
            $number = $lastNumber + 1;
        }

        // تنسيق الرقم ليكون 6 خانات مع بادئة الأصفار
        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }
}
