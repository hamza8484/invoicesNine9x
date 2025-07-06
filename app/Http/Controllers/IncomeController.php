<?php

namespace App\Http\Controllers;

use App\Income; // استيراد نموذج الإيراد (تأكد من المسار الصحيح: App\Models\Income)
use App\Project; // استيراد نموذج المشروع (تأكد من المسار الصحيح: App\Models\Project)
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // لاستخدام المستخدم الحالي
use Illuminate\Support\Facades\DB; // <== أضف هذا الاستيراد لاستخدام المعاملات (Transactions)

class IncomeController extends Controller
{
    /**
     * يجب على المستخدم أن يكون مسجل الدخول للوصول.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-incomes');
    }

    /**
     * عرض قائمة بجميع الإيرادات.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $incomes = Income::with('project')->orderBy('income_date', 'desc')->paginate(10); // إضافة pagination
        return view('incomes.index', compact('incomes'));
    }

    /**
     * عرض نموذج لإضافة إيراد جديد.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $projects = Project::all();
        return view('incomes.create_edit', compact('projects'));
    }

    /**
     * تخزين إيراد جديد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'source' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'income_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $income = Income::create($request->all());

            // تحديث total_income للمشروع
            $project = Project::find($request->project_id);
            if ($project) {
                $project->total_income += $request->amount;
                $project->save();
            }

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Income', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $income->id,
                'description' => 'تم إضافة إيراد جديد بقيمة ' . $income->amount . ' للمشروع ' . ($income->project->project_name ?? 'غير محدد') . ' من مصدر ' . ($income->source ?? 'غير محدد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($income), // <== استخدام get_class للحصول على اسم الكلاس الكامل
                'auditable_id' => $income->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $income->toArray(), // جميع قيم الإيراد الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('incomes.index')->with('success', 'تم إضافة الإيراد بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error creating income: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة الإيراد: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل إيراد معين.
     *
     * @param  \App\Models\Income  $income
     * @return \Illuminate\View\View
     */
    public function show(Income $income)
    {
        $income->load('project'); // تحميل العلاقة
        return view('incomes.show', compact('income'));
    }

    /**
     * عرض نموذج لتعديل إيراد موجود.
     *
     * @param  \App\Models\Income  $income
     * @return \Illuminate\View\View
     */
    public function edit(Income $income)
    {
        $projects = Project::all();
        return view('incomes.create_edit', compact('income', 'projects'));
    }

    /**
     * تحديث إيراد موجود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Income  $income
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Income $income)
    {
        $oldValues = $income->toArray(); // <== احصل على القيم القديمة قبل التحديث
        $oldAmount = $income->amount; // حفظ المبلغ القديم قبل التحديث

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'source' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'income_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $income->update($request->all());

            // تحديث total_income للمشروع
            $project = Project::find($request->project_id);
            if ($project) {
                // نطرح المبلغ القديم ونضيف المبلغ الجديد
                $project->total_income = ($project->total_income - $oldAmount) + $request->amount;
                $project->save();
            }

            $newValues = $income->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Income', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $income->id,
                'description' => 'تم تحديث الإيراد بقيمة ' . $income->amount . ' للمشروع ' . ($income->project->project_name ?? 'غير محدد') . ' من مصدر ' . ($income->source ?? 'غير محدد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($income),
                'auditable_id' => $income->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('incomes.index')->with('success', 'تم تحديث الإيراد بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error updating income: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الإيراد: ' . $e->getMessage());
        }
    }

    /**
     * حذف إيراد.
     *
     * @param  \App\Models\Income  $income
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Income $income)
    {
        $oldValues = $income->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $incomeId = $income->id;
        $incomeAmount = $income->amount;
        $projectName = $income->project->project_name ?? 'غير محدد';
        $incomeSource = $income->source ?? 'غير محدد';

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            // قبل الحذف، نطرح المبلغ من total_income للمشروع
            $project = $income->project;
            if ($project) {
                $project->total_income -= $incomeAmount; // استخدام $incomeAmount الذي تم حفظه
                $project->save();
            }

            $income->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Income', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $incomeId,
                'description' => 'تم حذف إيراد بقيمة ' . $incomeAmount . ' من المشروع ' . $projectName . ' من مصدر ' . $incomeSource,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Income', // <== يمكن استخدام اسم الكلاس مباشرة
                'auditable_id' => $incomeId,
                'old_values' => $oldValues, // القيم القديمة للإيراد الذي تم حذفه
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(), // <== استخدام request() لجلب IP
                'user_agent' => request()->header('User-Agent'), // <== استخدام request() لجلب User-Agent
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('incomes.index')->with('success', 'تم حذف الإيراد بنجاح!');
        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error deleting income: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف الإيراد: ' . $e->getMessage());
        }
    }
}
