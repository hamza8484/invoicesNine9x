<?php

namespace App\Http\Controllers;

use App\Project; // تأكد من استيراد نموذج Project
use App\Task;    // تأكد من استيراد نموذج Task
use App\User;    // تأكد من استيراد نموذج User (لتعيين المهام)
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <== أضف هذا الاستيراد
use Illuminate\Support\Facades\DB; // <== أضف هذا الاستيراد لاستخدام المعاملات (Transactions)
use Illuminate\Validation\Rule; // لاستخدام قاعدة التحقق unique مع الاستثناءات
use Carbon\Carbon; // <== أضف هذا الاستيراد لاستخدام now() مع المعاملات


class TaskController extends Controller
{
    /**
     * يجب على المستخدم أن يكون مسجل الدخول للوصول.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-tasks');
    }

    /**
     * عرض قائمة المهام لمشروع معين.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Project $project)
    {
        // جلب المهام لهذا المشروع، مع جلب اسم المستخدم المعين له المهمة
        $tasks = $project->tasks()->with('assignee')->orderBy('end_date', 'asc')->paginate(10); // إضافة pagination

        return view('tasks.index', compact('project', 'tasks'));
    }

    /**
     * عرض نموذج لإضافة مهمة جديدة لمشروع معين.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function create(Project $project)
    {
        // جلب جميع المستخدمين الذين يمكن تعيين المهام لهم (مثلاً، جميع أعضاء الفريق في هذا المشروع)
        // يمكنك تعديل هذا الشرط لجلب أنواع محددة من الأدوار، مثلاً 'engineer', 'worker'
        $assignableUsers = $project->users()->orderBy('name')->get();

        // أو لجلب جميع المستخدمين المسجلين في النظام
        // $assignableUsers = User::orderBy('name')->get();

        return view('tasks.create_edit', compact('project', 'assignableUsers'));
    }

    /**
     * تخزين مهمة جديدة لمشروع معين.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'delayed'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $task = $project->tasks()->create([
                'title' => $request->title,
                'description' => $request->description,
                'assigned_to' => $request->assigned_to,
                'status' => $request->status,
                'priority' => $request->priority,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                // completed_at سيتم تعيينه عند تحديث الحالة إلى 'completed'
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Task', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $task->id,
                'description' => 'تم إنشاء مهمة جديدة: ' . $task->title . ' للمشروع: ' . ($project->project_name ?? 'غير محدد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($task), // <== استخدام get_class للحصول على اسم الكلاس الكامل
                'auditable_id' => $task->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $task->toArray(), // جميع قيم المهمة الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('projects.tasks.index', $project->id)->with('success', 'تم إضافة المهمة بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error creating task: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المهمة: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل مهمة معينة.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @return \Illuminate\View\View
     */
    public function show(Project $project, Task $task)
    {
        // التأكد أن المهمة تتبع المشروع الصحيح
        if ($task->project_id !== $project->id) {
            abort(404);
        }
        return view('tasks.show', compact('project', 'task'));
    }

    /**
     * عرض نموذج لتعديل مهمة موجودة.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @return \Illuminate\View\View
     */
    public function edit(Project $project, Task $task)
    {
        // التأكد أن المهمة تتبع المشروع الصحيح
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        // جلب جميع المستخدمين الذين يمكن تعيين المهام لهم
        $assignableUsers = $project->users()->orderBy('name')->get();

        return view('tasks.create_edit', compact('project', 'task', 'assignableUsers'));
    }

    /**
     * تحديث مهمة موجودة.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Project $project, Task $task)
    {
        // التأكد أن المهمة تتبع المشروع الصحيح
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $oldValues = $task->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => ['required', Rule::in(['pending', 'in_progress', 'completed', 'delayed'])],
            'priority' => ['required', Rule::in(['low', 'medium', 'high', 'urgent'])],
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $taskData = $request->only([
                'title', 'description', 'assigned_to', 'status', 'priority', 'start_date', 'end_date'
            ]);

            // تحديث تاريخ الانتهاء الفعلي إذا تغيرت الحالة إلى 'completed'
            if ($request->status === 'completed' && is_null($task->completed_at)) {
                $taskData['completed_at'] = Carbon::now();
            } elseif ($request->status !== 'completed' && !is_null($task->completed_at)) {
                // إذا تغيرت الحالة من 'completed' إلى شيء آخر، أعد completed_at إلى null
                $taskData['completed_at'] = null;
            }

            $task->update($taskData);

            $newValues = $task->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Task', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $task->id,
                'description' => 'تم تحديث المهمة: ' . $task->title . ' للمشروع: ' . ($project->project_name ?? 'غير محدد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($task),
                'auditable_id' => $task->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('projects.tasks.index', $project->id)->with('success', 'تم تحديث المهمة بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error updating task: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث المهمة: ' . $e->getMessage());
        }
    }

    /**
     * حذف مهمة.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Project $project, Task $task, Request $request) // <== أضف Request $request
    {
        // التأكد أن المهمة تتبع المشروع الصحيح
        if ($task->project_id !== $project->id) {
            abort(404);
        }

        $oldValues = $task->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $taskId = $task->id;
        $taskTitle = $task->title;

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $task->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Task', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $taskId,
                'description' => 'تم حذف المهمة: ' . $taskTitle . ' من المشروع: ' . ($project->project_name ?? 'غير محدد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Task', // <== يمكن استخدام اسم الكلاس مباشرة
                'auditable_id' => $taskId,
                'old_values' => $oldValues, // القيم القديمة للمهمة التي تم حذفها
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => $request->ip(), // <== استخدام $request لجلب IP
                'user_agent' => $request->header('User-Agent'), // <== استخدام $request لجلب User-Agent
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('projects.tasks.index', $project->id)->with('success', 'تم حذف المهمة بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error deleting task: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المهمة: ' . $e->getMessage());
        }
    }
}
