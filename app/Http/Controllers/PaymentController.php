<?php

namespace App\Http\Controllers;

use App\Payment; // <== تأكد من المسار الصحيح: App\Models\Payment
use App\Invoice; // <== تأكد من المسار الصحيح: App\Models\Invoice
use App\User;    // <== تأكد من المسار الصحيح: App\Models\User
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-payments');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'user']);

        // فلاتر البحث
        if ($request->has('invoice_id') && $request->invoice_id != '') {
            $query->where('invoice_id', $request->invoice_id);
        }
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('payment_method') && $request->payment_method != '') {
            $query->where('payment_method', $request->payment_method);
        }
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('payment_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('payment_date', '<=', $request->end_date);
        }

        $payments = $query->latest()->paginate(10);
        $invoices = Invoice::select('id', 'invoice_number')->get(); // لجلب قائمة الفواتير للفلترة
        $users = User::select('id', 'name')->get(); // لجلب قائمة المستخدمين للفلترة
        $paymentMethods = Payment::distinct('payment_method')->pluck('payment_method'); // لجلب طرق الدفع الموجودة

        return view('payments.index', compact('payments', 'invoices', 'users', 'paymentMethods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $invoices = Invoice::all();
        $paymentMethods = ['cash', 'bank_transfer', 'cheque', 'card', 'other'];
        $selectedInvoiceId = $request->query('invoice_id'); // لجلب invoice_id من الـ URL إذا تم إرسالها

        // قم بإنشاء كائن Payment جديد فارغ للإرسال إلى الواجهة
        $payment = new Payment();

        return view('payments.create_edit', compact('invoices', 'paymentMethods', 'selectedInvoiceId', 'payment'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,card,other',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $payment = Payment::create([
                'invoice_id' => $request->invoice_id,
                'user_id' => Auth::id(), // المستخدم الحالي
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            // **تصحيح: يجب جلب كائن الفاتورة لكي تتمكن من الوصول إلى خصائصه**
            $invoice = Invoice::find($request->invoice_id);

            // **تسجيل النشاط (Activity Log)**
            if ($invoice) { // تحقق للتأكد من وجود الفاتورة
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'created',
                    'target_type' => 'App\Payment', // <=== **الموديل المستهدف هو Payment**
                    'target_id' => $payment->id, // <=== **معرف الدفعة التي تم إنشاؤها**
                    'description' => 'تم إنشاء دفعة جديدة برقم ' . $payment->id . ' للفاتورة رقم: ' . $invoice->invoice_number . ' بمبلغ: ' . $payment->amount,
                ]);

                // تسجيل التدقيق التفصيلي (Audit Log)
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'created',
                    'auditable_type' => get_class($payment),
                    'auditable_id' => $payment->id,
                    'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                    'new_values' => $payment->toArray(),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }

            $this->updateInvoiceAmounts($payment->invoice_id); // تحديث مبالغ الفاتورة

            DB::commit();

            return redirect()->route('payments.index')->with('success', 'تم إضافة الدفعة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error storing payment: ' . $e->getMessage()); // سجل الخطأ
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة الدفعة: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        return view('payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        $invoices = Invoice::all();
        $paymentMethods = ['cash', 'bank_transfer', 'cheque', 'card', 'other'];

        return view('payments.create_edit', compact('payment', 'invoices', 'paymentMethods'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,card,other',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // حفظ الـ old_invoice_id قبل التحديث
            $oldInvoiceId = $payment->invoice_id;
            $oldAmount = $payment->amount; // لحساب الفرق في الوصف
            $oldValues = $payment->toArray(); // <== احصل على القيم القديمة قبل التحديث

            $payment->update([
                'invoice_id' => $request->invoice_id,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
            ]);

            // **تسجيل النشاط (Activity Log) للتحديث**
            $invoice = Invoice::find($payment->invoice_id); // جلب الفاتورة الحالية المرتبطة بالدفعة
            if ($invoice) {
                $description = 'تم تحديث الدفعة برقم ' . $payment->id . ' للفاتورة رقم: ' . $invoice->invoice_number . '.';
                if ($oldAmount != $payment->amount) {
                    $description .= ' تغير المبلغ من ' . $oldAmount . ' إلى ' . $payment->amount . '.';
                }
                // يمكنك إضافة تفاصيل أخرى حول ما تم تغييره

                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'target_type' => 'App\Payment',
                    'target_id' => $payment->id,
                    'description' => $description,
                ]);

                // تسجيل التدقيق التفصيلي (Audit Log)
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'updated',
                    'auditable_type' => get_class($payment),
                    'auditable_id' => $payment->id,
                    'old_values' => $oldValues,
                    'new_values' => $payment->fresh()->toArray(), // <== احصل على القيم الجديدة بعد التحديث
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);
            }

            // إذا تغيرت الفاتورة المرتبطة، يجب تحديث كلتا الفاتورتين
            if ($oldInvoiceId != $payment->invoice_id) {
                $this->updateInvoiceAmounts($oldInvoiceId); // تحديث الفاتورة القديمة
            }
            $this->updateInvoiceAmounts($payment->invoice_id); // تحديث الفاتورة الجديدة/المعدلة

            DB::commit();

            return redirect()->route('payments.index')->with('success', 'تم تحديث الدفعة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating payment: ' . $e->getMessage()); // سجل الخطأ
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الدفعة: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        $oldValues = $payment->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $invoiceId = $payment->invoice_id;
        $paymentId = $payment->id; // حفظ معرف الدفعة قبل الحذف
        $amount = $payment->amount; // حفظ المبلغ قبل الحذف
        $invoiceNumber = $payment->invoice->invoice_number ?? 'غير معروفة'; // للحصول على رقم الفاتورة للوصف

        try {
            DB::beginTransaction();

            $payment->delete();

            // **تسجيل النشاط (Activity Log) للحذف**
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Payment',
                'target_id' => $paymentId,
                'description' => 'تم حذف دفعة برقم ' . $paymentId . ' للفاتورة رقم: ' . $invoiceNumber . ' بمبلغ: ' . $amount . '.',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => 'App\Payment',
                'auditable_id' => $paymentId,
                'old_values' => $oldValues,
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(), // يمكن استخدام request() في أي مكان
                'user_agent' => request()->header('User-Agent'),
            ]);

            $this->updateInvoiceAmounts($invoiceId); // تحديث مبالغ الفاتورة بعد الحذف

            DB::commit();

            return redirect()->route('payments.index')->with('success', 'تم حذف الدفعة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting payment: ' . $e->getMessage()); // سجل الخطأ
            return back()->with('error', 'حدث خطأ أثناء حذف الدفعة: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to update invoice paid_amount and due_amount.
     * @param int $invoiceId
     * @return void
     */
    private function updateInvoiceAmounts($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        if ($invoice) {
            // Store old values for audit log before updating invoice
            $oldInvoiceValues = $invoice->toArray();

            $totalPaidAmount = $invoice->payments()->sum('amount');
            $invoice->paid_amount = $totalPaidAmount;
            $invoice->due_amount = $invoice->total - $totalPaidAmount;

            // تحديث حالة الفاتورة بناءً على المبالغ المدفوعة
            if ($invoice->due_amount <= 0) {
                $invoice->status = 'paid';
            } elseif ($totalPaidAmount > 0) {
                $invoice->status = 'partial';
            } else {
                $invoice->status = 'unpaid';
            }

            $invoice->save();

            // Store new values for audit log after updating invoice
            $newInvoiceValues = $invoice->fresh()->toArray();

            // Log the update to the invoice itself in AuditLog
            // This is important because the invoice's status and amounts are changing
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'invoice_amounts_updated', // Specific action for clarity
                'auditable_type' => get_class($invoice),
                'auditable_id' => $invoice->id,
                'old_values' => $oldInvoiceValues,
                'new_values' => $newInvoiceValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);
        }
    }
}