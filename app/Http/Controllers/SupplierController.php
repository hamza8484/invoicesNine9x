<?php

namespace App\Http\Controllers;

use App\Supplier; // استيراد نموذج المورد (تأكد من المسار الصحيح)
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <== أضف هذا الاستيراد
use Illuminate\Support\Facades\DB; // <== أضف هذا الاستيراد لاستخدام المعاملات (Transactions)
use Illuminate\Validation\Rule; // لاستخدام قاعدة التحقق unique مع الاستثناءات

class SupplierController extends Controller
{
    /**
     * يجب على المستخدم أن يكون مسجل الدخول للوصول.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-suppliers');
    }

    /**
     * عرض قائمة بجميع الموردين.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $suppliers = Supplier::orderBy('name')->paginate(10); // إضافة pagination
        return view('suppliers.index', compact('suppliers'));
    }

    /**
     * عرض نموذج لإضافة مورد جديد.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view('suppliers.create_edit');
    }

    /**
     * تخزين مورد جديد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'vat_No' => 'nullable|string|max:255|unique:suppliers,vat_No', // يمكن أن يكون VAT No فريدًا
            'company' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255|unique:suppliers,email', // يمكن أن يكون البريد الإلكتروني فريدًا
            'address' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $supplier = Supplier::create($request->all());

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Supplier', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $supplier->id,
                'description' => 'تم إنشاء مورد جديد: ' . $supplier->name . ' للشركة: ' . ($supplier->company ?? 'لا يوجد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($supplier), // <== استخدام get_class للحصول على اسم الكلاس الكامل
                'auditable_id' => $supplier->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $supplier->toArray(), // جميع قيم المورد الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('suppliers.index')->with('success', 'تم إضافة المورد بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error creating supplier: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المورد: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل مورد معين.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\View\View
     */
    public function show(Supplier $supplier)
    {
        return view('suppliers.show', compact('supplier'));
    }

    /**
     * عرض نموذج لتعديل مورد موجود.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\View\View
     */
    public function edit(Supplier $supplier)
    {
        return view('suppliers.create_edit', compact('supplier'));
    }

    /**
     * تحديث مورد موجود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Supplier $supplier)
    {
        $oldValues = $supplier->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'name' => 'required|string|max:255',
            'vat_No' => ['nullable', 'string', 'max:255', Rule::unique('suppliers')->ignore($supplier->id)],
            'company' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('suppliers')->ignore($supplier->id)],
            'address' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $supplier->update($request->all());

            $newValues = $supplier->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Supplier', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $supplier->id,
                'description' => 'تم تحديث بيانات المورد: ' . $supplier->name . ' للشركة: ' . ($supplier->company ?? 'لا يوجد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($supplier),
                'auditable_id' => $supplier->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('suppliers.index')->with('success', 'تم تحديث بيانات المورد بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error updating supplier: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث بيانات المورد: ' . $e->getMessage());
        }
    }

    /**
     * حذف مورد.
     *
     * @param  \App\Models\Supplier  $supplier
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Supplier $supplier, Request $request) // <== أضف Request $request
    {
        $oldValues = $supplier->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $supplierName = $supplier->name;
        $supplierId = $supplier->id;

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $supplier->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Supplier', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $supplierId,
                'description' => 'تم حذف المورد: ' . $supplierName,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Supplier', // <== يمكن استخدام اسم الكلاس مباشرة
                'auditable_id' => $supplierId,
                'old_values' => $oldValues, // القيم القديمة للمورد الذي تم حذفه
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => $request->ip(), // <== استخدام $request لجلب IP
                'user_agent' => $request->header('User-Agent'), // <== استخدام $request لجلب User-Agent
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('suppliers.index')->with('success', 'تم حذف المورد بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error deleting supplier: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المورد: ' . $e->getMessage());
        }
    }
}
