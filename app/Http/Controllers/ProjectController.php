<?php

namespace App\Http\Controllers;

use App\Project;
use App\Client;
use App\User;
use App\ActivityLog; // تأكد من وجود هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('can:manage-projects');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Project::with(['client', 'users']);

        // فلاتر البحث
        if ($request->has('project_name') && $request->project_name != '') {
            $query->where('project_name', 'like', '%' . $request->project_name . '%');
        }
        if ($request->has('client_id') && $request->client_id != '') {
            $query->where('client_id', $request->client_id);
        }
        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('start_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('end_date', '<=', $request->end_date);
        }

        $projects = $query->latest()->paginate(10);

        // جلب البيانات اللازمة للفلاتر
        $clients = Client::all();
        $statuses = Project::distinct('status')->pluck('status')->toArray();

        return view('projects.index', compact('projects', 'clients', 'statuses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $project = new Project();
        $clients = Client::all();
        $users = User::all(); // لجلب المستخدمين لربطهم بالمشروع (user_ids)
        // <== إضافة: جلب المديرين لدور manager_id
        $managers = User::whereIn('role', ['project_manager', 'admin'])->orderBy('name')->get();
        return view('projects.create_edit', compact('project', 'clients', 'users', 'managers')); // <== تمرير managers
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'manager_id' => 'nullable|exists:users,id', // <== إضافة manager_id للتحقق
        ]);

        try {
            DB::beginTransaction();

            $project = Project::create([
                'project_name' => $request->project_name,
                'client_id' => $request->client_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'budget' => $request->budget,
                'status' => $request->status,
                'description' => $request->description,
                'manager_id' => $request->manager_id, // <== إضافة manager_id للحفظ
            ]);

            // ربط المستخدمين بالمشروع
            if ($request->has('user_ids')) {
                $project->users()->sync($request->user_ids);
            } else {
                $project->users()->sync([]); // إزالة جميع المستخدمين إذا لم يتم تحديد أي منهم
            }

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Project', // تأكد من مسار الموديل الصحيح
                'target_id' => $project->id,
                'description' => 'تم إنشاء مشروع جديد: ' . $project->project_name,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log) <== إضافة جديدة هنا
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'auditable_type' => get_class($project), // أو 'App\Models\Project'
                'auditable_id' => $project->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $project->toArray(), // جميع قيم المشروع الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('projects.index')->with('success', 'تم إضافة المشروع بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating project: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة المشروع: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project)
    {
        return view('projects.show', compact('project'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Project $project)
    {
        $clients = Client::all();
        $users = User::all();
        $projectUsers = $project->users->pluck('id')->toArray(); // للحصول على المستخدمين المرتبطين حالياً
        // <== إضافة: جلب المديرين لدور manager_id
        $managers = User::whereIn('role', ['project_manager', 'admin'])->orderBy('name')->get();
        return view('projects.create_edit', compact('project', 'clients', 'users', 'projectUsers', 'managers')); // <== تمرير managers
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Project $project)
    {
        $oldValues = $project->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'project_name' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'required|string|max:50',
            'description' => 'nullable|string',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
            'manager_id' => 'nullable|exists:users,id', // <== إضافة manager_id للتحقق
        ]);

        try {
            DB::beginTransaction();

            $project->update([
                'project_name' => $request->project_name,
                'client_id' => $request->client_id,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'budget' => $request->budget,
                'status' => $request->status,
                'description' => $request->description,
                'manager_id' => $request->manager_id, // <== إضافة manager_id للحفظ
            ]);

            // تحديث ربط المستخدمين بالمشروع
            if ($request->has('user_ids')) {
                $project->users()->sync($request->user_ids);
            } else {
                $project->users()->sync([]); // إزالة جميع المستخدمين إذا لم يتم تحديد أي منهم
            }

            $newValues = $project->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Project',
                'target_id' => $project->id,
                'description' => 'تم تحديث المشروع: ' . $project->project_name,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log) <== إضافة جديدة هنا
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'auditable_type' => get_class($project),
                'auditable_id' => $project->id,
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('projects.index')->with('success', 'تم تحديث المشروع بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating project: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث المشروع: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project)
    {
        $oldValues = $project->toArray(); // <== احصل على القيم القديمة قبل الحذف

        try {
            DB::beginTransaction();

            $projectName = $project->project_name;
            $projectId = $project->id;

            $project->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Project',
                'target_id' => $projectId,
                'description' => 'تم حذف المشروع: ' . $projectName,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log) <== إضافة جديدة هنا
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'auditable_type' => get_class($project),
                'auditable_id' => $projectId,
                'old_values' => $oldValues,
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(), // استخدام request() بدلاً من $request إذا لم تكن متاحة
                'user_agent' => request()->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('projects.index')->with('success', 'تم حذف المشروع بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting project: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المشروع: ' . $e->getMessage());
        }
    }
}
