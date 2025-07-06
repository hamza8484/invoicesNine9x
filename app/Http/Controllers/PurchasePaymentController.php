<?php

namespace App\Http\Controllers;

use App\PurchasePayment;
use App\PurchaseInvoice;
use App\Supplier;
use App\ActivityLog;
use App\AuditLog;
use App\User; // تأكد من استيراد موديل User

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchasePaymentController extends Controller
{
    // طرق الدفع المتاحة
    private $paymentMethods = ['cash', 'bank_transfer', 'cheque', 'card', 'other'];

    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PurchasePayment::with('purchaseInvoice.supplier', 'user');

        if ($request->has('purchase_invoice_id') && $request->purchase_invoice_id != '') {
            $query->where('purchase_invoice_id', $request->purchase_invoice_id);
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

        $purchasePayments = $query->latest()->paginate(10);
        $purchaseInvoices = PurchaseInvoice::with('supplier')->select('id', 'invoice_number', 'supplier_id')->get();
        $paymentMethods = $this->paymentMethods;

        return view('purchase_payments.index', compact('purchasePayments', 'purchaseInvoices', 'paymentMethods'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $purchasePayment = new PurchasePayment();

        // <== هذا السطر هو المفتاح لضمان تحميل المورد
        $purchaseInvoices = PurchaseInvoice::whereIn('status', ['unpaid', 'partial'])
                                            ->with('supplier')
                                            ->orderBy('invoice_number')
                                            ->get();

        $paymentMethods = $this->paymentMethods;
        $selectedInvoiceId = $request->query('purchase_invoice_id');

        return view('purchase_payments.create_edit', compact('purchasePayment', 'purchaseInvoices', 'paymentMethods', 'selectedInvoiceId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', $this->paymentMethods),
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $purchasePayment = PurchasePayment::create(array_merge($validatedData, [
                'user_id' => Auth::id(),
            ]));

            $this->updatePurchaseInvoiceAmounts($purchasePayment->purchase_invoice_id);

            $purchasePayment->load('purchaseInvoice');

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\PurchasePayment',
                'target_id' => $purchasePayment->id,
                'description' => 'تم إنشاء دفعة شراء جديدة بمبلغ ' . number_format($purchasePayment->amount, 2) . ' للفاتورة رقم: ' . ($purchasePayment->purchaseInvoice->invoice_number ?? 'غير معروف'),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'auditable_type' => get_class($purchasePayment),
                'auditable_id' => $purchasePayment->id,
                'old_values' => null,
                'new_values' => $purchasePayment->toArray(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('purchase_payments.index')->with('success', 'تم إضافة الدفعة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error storing purchase payment: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة الدفعة: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchasePayment $purchasePayment)
    {
        $purchasePayment->load('purchaseInvoice.supplier', 'user');
        return view('purchase_payments.show', compact('purchasePayment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchasePayment $purchasePayment)
    {
        // <== هذا السطر هو المفتاح لضمان تحميل المورد
        $purchaseInvoices = PurchaseInvoice::with('supplier')
                                            ->orderBy('invoice_number')
                                            ->get();

        $paymentMethods = $this->paymentMethods;

        return view('purchase_payments.create_edit', compact('purchasePayment', 'purchaseInvoices', 'paymentMethods'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchasePayment $purchasePayment)
    {
        $oldValues = $purchasePayment->toArray();
        $oldInvoiceId = $purchasePayment->purchase_invoice_id;

        $validatedData = $request->validate([
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:' . implode(',', $this->paymentMethods),
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $purchasePayment->update(array_merge($validatedData, [
                'user_id' => Auth::id(),
            ]));

            if ($oldInvoiceId != $purchasePayment->purchase_invoice_id) {
                $this->updatePurchaseInvoiceAmounts($oldInvoiceId);
            }
            $this->updatePurchaseInvoiceAmounts($purchasePayment->purchase_invoice_id);

            $newValues = $purchasePayment->fresh()->toArray();

            $purchasePayment->load('purchaseInvoice');

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\PurchasePayment',
                'target_id' => $purchasePayment->id,
                'description' => 'تم تحديث دفعة شراء بمبلغ ' . number_format($purchasePayment->amount, 2) . ' للفاتورة رقم: ' . ($purchasePayment->purchaseInvoice->invoice_number ?? 'غير معروف'),
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'auditable_type' => get_class($purchasePayment),
                'auditable_id' => $purchasePayment->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('purchase_payments.index')->with('success', 'تم تحديث الدفعة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating purchase payment: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الدفعة: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchasePayment $purchasePayment, Request $request)
    {
        $oldValues = $purchasePayment->toArray();
        $purchasePaymentId = $purchasePayment->id;
        $invoiceId = $purchasePayment->purchase_invoice_id;
        $amount = $purchasePayment->amount;

        $purchasePayment->load('purchaseInvoice');
        $invoiceNumber = $purchasePayment->purchaseInvoice->invoice_number ?? 'غير معروف';

        try {
            DB::beginTransaction();

            $purchasePayment->delete();

            $this->updatePurchaseInvoiceAmounts($invoiceId);

            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\PurchasePayment',
                'target_id' => $purchasePaymentId,
                'description' => 'تم حذف دفعة شراء بمبلغ ' . number_format($amount, 2) . ' من الفاتورة رقم: ' . $invoiceNumber,
            ]);

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => 'App\PurchasePayment',
                'auditable_id' => $purchasePaymentId,
                'old_values' => $oldValues,
                'new_values' => null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('purchase_payments.index')->with('success', 'تم حذف الدفعة بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting purchase payment: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف الدفعة: ' . $e->getMessage());
        }
    }

    /**
     * Helper function to update purchase invoice paid_amount, due_amount, and status.
     *
     * @param int $purchaseInvoiceId
     * @return void
     */
    private function updatePurchaseInvoiceAmounts(int $purchaseInvoiceId): void
    {
        $purchaseInvoice = PurchaseInvoice::find($purchaseInvoiceId);

        if ($purchaseInvoice) {
            $oldInvoiceValues = $purchaseInvoice->toArray();

            $totalPaidAmount = $purchaseInvoice->payments()->sum('amount');
            $purchaseInvoice->paid_amount = $totalPaidAmount;
            $purchaseInvoice->due_amount = $purchaseInvoice->total - $totalPaidAmount;

            if ($purchaseInvoice->due_amount <= 0) {
                $purchaseInvoice->status = 'paid';
            } elseif ($totalPaidAmount > 0) {
                $purchaseInvoice->status = 'partial';
            } else {
                $purchaseInvoice->status = 'unpaid';
            }

            $purchaseInvoice->save();

            $newInvoiceValues = $purchaseInvoice->fresh()->toArray();

            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'invoice_payment_status_updated',
                'auditable_type' => get_class($purchaseInvoice),
                'auditable_id' => $purchaseInvoice->id,
                'old_values' => $oldInvoiceValues,
                'new_values' => $newInvoiceValues,
                'ip_address' => request()->ip(),
                'user_agent' => request()->header('User-Agent'),
            ]);
        }
    }
}
