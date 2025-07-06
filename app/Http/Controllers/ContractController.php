<?php

namespace App\Http\Controllers;

use App\Contract; // استيراد نموذج العقد (تأكد من المسار الصحيح: App\Models\Contract)
use App\Project;  // استيراد نموذج المشروع (تأكد من المسار الصحيح: App\Models\Project)
use App\Client;   // استيراد نموذج العميل (تأكد من المسار الصحيح: App\Models\Client)
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <== أضف هذا الاستيراد
use Illuminate\Support\Facades\DB; // <== أضف هذا الاستيراد لاستخدام المعاملات (Transactions)
use Illuminate\Validation\Rule; // لاستخدام قاعدة التحقق unique مع الاستثناءات

class ContractController extends Controller
{
    /**
     * يجب على المستخدم أن يكون مسجل الدخول للوصول.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-contracts');
    }

    /**
     * عرض قائمة بجميع العقود.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // جلب جميع العقود مع معلومات المشروع والعميل المرتبطة بها
        $contracts = Contract::with(['project', 'client'])->orderBy('start_date', 'desc')->paginate(10); // إضافة pagination
        return view('contracts.index', compact('contracts'));
    }

    /**
     * عرض نموذج لإضافة عقد جديد.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // جلب جميع المشاريع والعملاء لملء القوائم المنسدلة
        $projects = Project::orderBy('project_name')->get();
        $clients = Client::orderBy('name')->get();
        return view('contracts.create_edit', compact('projects', 'clients'));
    }

    /**
     * تخزين عقد جديد.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'client_id' => 'required|exists:clients,id',
            'contract_number' => 'required|string|max:255|unique:contracts,contract_number', // رقم العقد يجب أن يكون فريدًا
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'total_amount' => 'required|numeric|min:0',
            'status' => ['required', Rule::in(['active', 'expired', 'terminated'])],
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $contract = Contract::create($request->all());

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Contract', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $contract->id,
                'description' => 'تم إنشاء عقد جديد برقم: ' . $contract->contract_number . ' للمشروع: ' . ($contract->project->project_name ?? 'غير محدد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($contract), // <== استخدام get_class للحصول على اسم الكلاس الكامل
                'auditable_id' => $contract->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $contract->toArray(), // جميع قيم العقد الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('contracts.index')->with('success', 'تم إضافة العقد بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error creating contract: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة العقد: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل عقد معين.
     *
     * @param  \App\Models\Contract  $contract
     * @return \Illuminate\View\View
     */
    public function show(Contract $contract)
    {
        // تحميل العلاقات لضمان عرض اسم المشروع والعميل
        $contract->load('project', 'client');
        return view('contracts.show', compact('contract'));
    }

    /**
     * عرض نموذج لتعديل عقد موجود.
     *
     * @param  \App\Models\Contract  $contract
     * @return \Illuminate\View\View
     */
    public function edit(Contract $contract)
    {
        $projects = Project::orderBy('project_name')->get();
        $clients = Client::orderBy('name')->get();
        return view('contracts.create_edit', compact('contract', 'projects', 'clients'));
    }

    /**
     * تحديث عقد موجود.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Contract  $contract
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Contract $contract)
    {
        $oldValues = $contract->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'client_id' => 'required|exists:clients,id',
            'contract_number' => ['required', 'string', 'max:255', Rule::unique('contracts')->ignore($contract->id)], // رقم العقد يجب أن يكون فريدًا باستثناء العقد الحالي
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'total_amount' => 'required|numeric|min:0',
            'status' => ['required', Rule::in(['active', 'expired', 'terminated'])],
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $contract->update($request->all());

            $newValues = $contract->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Contract', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $contract->id,
                'description' => 'تم تحديث العقد برقم: ' . $contract->contract_number . ' للمشروع: ' . ($contract->project->project_name ?? 'غير محدد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($contract),
                'auditable_id' => $contract->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('contracts.index')->with('success', 'تم تحديث العقد بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error updating contract: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث العقد: ' . $e->getMessage());
        }
    }

    /**
     * حذف عقد.
     *
     * @param  \App\Models\Contract  $contract
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Contract $contract)
    {
        $oldValues = $contract->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $contractId = $contract->id;
        $contractNumber = $contract->contract_number;
        $projectName = $contract->project->project_name ?? 'غير محدد';

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $contract->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Contract', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $contractId,
                'description' => 'تم حذف العقد برقم: ' . $contractNumber . ' للمشروع: ' . $projectName,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Contract', // <== يمكن استخدام اسم الكلاس مباشرة
                'auditable_id' => $contractId,
                'old_values' => $oldValues, // القيم القديمة للعقد الذي تم حذفه
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(), // <== استخدام request() لجلب IP
                'user_agent' => request()->header('User-Agent'), // <== استخدام request() لجلب User-Agent
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('contracts.index')->with('success', 'تم حذف العقد بنجاح!');
        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error deleting contract: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف العقد: ' . $e->getMessage());
        }
    }
}
