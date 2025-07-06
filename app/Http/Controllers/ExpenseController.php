<?php

namespace App\Http\Controllers;

use App\Expense; // استيراد نموذج المصروف (تأكد من المسار الصحيح: App\Models\Expense)
use App\Project; // استيراد نموذج المشروع (تأكد من المسار الصحيح: App\Models\Project)
use App\Supplier; // استيراد نموذج المورد (تأكد من المسار الصحيح: App\Models\Supplier)
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // لاستخدام المستخدم الحالي
use Illuminate\Support\Facades\DB; // <== أضف هذا الاستيراد لاستخدام المعاملات (Transactions)

class ExpenseController extends Controller
{
    /**
     * يجب على المستخدم أن يكون مسجل الدخول للوصول.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-expenses');
    }

    /**
     * عرض قائمة بجميع المصروفات.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $expenses = Expense::with(['project', 'supplier', 'creator'])->orderBy('expense_date', 'desc')->paginate(10); // إضافة pagination
        return view('expenses.index', compact('expenses'));
    }

    /**
     * عرض نموذج لإضافة مصروف جديد.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $projects = Project::all();
        $suppliers = Supplier::all();
        $expenseTypes = ['material', 'salary', 'transport', 'misc'];
        return view('expenses.create_edit', compact('projects', 'suppliers', 'expenseTypes'));
    }

    /**
     * تخزين مصروف جديد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'type' => 'required|in:material,salary,transport,misc',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $expense = new Expense($request->all());
            $expense->created_by = Auth::id(); // تعيين المستخدم الذي أنشأ المصروف
            $expense->save();

            // تحديث current_spend للمشروع
            $project = Project::find($request->project_id);
            if ($project) {
                $project->current_spend += $request->amount;
                $project->save();
            }

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Expense', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $expense->id,
                'description' => 'تم إضافة مصروف جديد بقيمة ' . $expense->amount . ' للمشروع ' . ($expense->project->project_name ?? 'غير محدد') . ' من نوع ' . $expense->type,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($expense), // <== استخدام get_class للحصول على اسم الكلاس الكامل
                'auditable_id' => $expense->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $expense->toArray(), // جميع قيم المصروف الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('expenses.index')->with('success', 'تم إضافة المصروف بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error creating expense: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المصروف: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل مصروف معين.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\View\View
     */
    public function show(Expense $expense)
    {
        $expense->load(['project', 'supplier', 'creator']); // تحميل العلاقات
        return view('expenses.show', compact('expense'));
    }

    /**
     * عرض نموذج لتعديل مصروف موجود.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\View\View
     */
    public function edit(Expense $expense)
    {
        $projects = Project::all();
        $suppliers = Supplier::all();
        $expenseTypes = ['material', 'salary', 'transport', 'misc'];
        return view('expenses.create_edit', compact('expense', 'projects', 'suppliers', 'expenseTypes'));
    }

    /**
     * تحديث مصروف موجود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Expense $expense)
    {
        $oldValues = $expense->toArray(); // <== احصل على القيم القديمة قبل التحديث
        $oldAmount = $expense->amount; // حفظ المبلغ القديم قبل التحديث

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'type' => 'required|in:material,salary,transport,misc',
            'description' => 'nullable|string|max:1000',
            'amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $expense->update($request->all());

            // تحديث current_spend للمشروع
            $project = Project::find($request->project_id);
            if ($project) {
                // نطرح المبلغ القديم ونضيف المبلغ الجديد
                $project->current_spend = ($project->current_spend - $oldAmount) + $request->amount;
                $project->save();
            }

            $newValues = $expense->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Expense', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $expense->id,
                'description' => 'تم تحديث المصروف بقيمة ' . $expense->amount . ' للمشروع ' . ($expense->project->project_name ?? 'غير محدد') . ' من نوع ' . $expense->type,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($expense),
                'auditable_id' => $expense->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('expenses.index')->with('success', 'تم تحديث المصروف بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error updating expense: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث المصروف: ' . $e->getMessage());
        }
    }

    /**
     * حذف مصروف.
     *
     * @param  \App\Models\Expense  $expense
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Expense $expense)
    {
        $oldValues = $expense->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $expenseId = $expense->id;
        $expenseAmount = $expense->amount;
        $projectName = $expense->project->project_name ?? 'غير محدد';
        $expenseType = $expense->type;

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            // قبل الحذف، نطرح المبلغ من current_spend للمشروع
            $project = $expense->project; // <== جلب المشروع المرتبط بالمصروف
            if ($project) {
                $project->current_spend -= $expenseAmount; // استخدام $expenseAmount الذي تم حفظه
                $project->save();
            }

            $expense->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Expense', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $expenseId,
                'description' => 'تم حذف المصروف بقيمة ' . $expenseAmount . ' من المشروع ' . $projectName . ' من نوع ' . $expenseType,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Expense', // <== يمكن استخدام اسم الكلاس مباشرة
                'auditable_id' => $expenseId,
                'old_values' => $oldValues, // القيم القديمة للمصروف الذي تم حذفه
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(), // <== استخدام request() لجلب IP
                'user_agent' => request()->header('User-Agent'), // <== استخدام request() لجلب User-Agent
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('expenses.index')->with('success', 'تم حذف المصروف بنجاح!');
        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error deleting expense: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المصروف: ' . $e->getMessage());
        }
    }
}
