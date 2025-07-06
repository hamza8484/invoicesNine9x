<?php

namespace App\Http\Controllers;

use App\Timesheet; // تأكد من المسار الصحيح
use App\User;     // لاستخدام موديل المستخدم
use App\Project;  // لاستخدام موديل المشروع
use App\Task;     // لاستخدام موديل المهمة (إذا كان موجودًا)
use App\ActivityLog; // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon; // لاستخدام Carbon لحساب المدة

class TimesheetController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-timesheets');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Timesheet::with(['user', 'project', 'task']);

        // فلاتر البحث
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('project_id') && $request->project_id != '') {
            $query->where('project_id', $request->project_id);
        }
        if ($request->has('task_id') && $request->task_id != '') {
            $query->where('task_id', $request->task_id);
        }
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('work_date', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('work_date', '<=', $request->end_date);
        }

        $timesheets = $query->latest()->paginate(10);

        // جلب البيانات اللازمة للفلاتر
        $users = User::all();
        $projects = Project::all();
        $tasks = Task::all(); // تأكد من وجود موديل Task

        return view('timesheets.index', compact('timesheets', 'users', 'projects', 'tasks'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $timesheet = new Timesheet(); // كائن Timesheet فارغ للنموذج
        $users = User::all();
        $projects = Project::all();
        $tasks = Task::all(); // تأكد من وجود موديل Task

        return view('timesheets.create_edit', compact('timesheet', 'users', 'projects', 'tasks'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'work_date' => 'required|date',
            'start_time' => 'required|date_format:H:i', // HH:MM format
            'end_time' => 'required|date_format:H:i|after:start_time', // HH:MM format and after start_time
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // حساب المدة
            $startTime = Carbon::parse($request->start_time);
            $endTime = Carbon::parse($request->end_time);
            $duration = $endTime->diffInMinutes($startTime) / 60; // المدة بالساعات كعدد عشري

            $timesheet = Timesheet::create([
                'user_id' => $request->user_id,
                'project_id' => $request->project_id,
                'task_id' => $request->task_id,
                'work_date' => $request->work_date,
                'start_time' => $request->work_date . ' ' . $request->start_time, // دمج التاريخ والوقت
                'end_time' => $request->work_date . ' ' . $request->end_time,     // دمج التاريخ والوقت
                'duration' => $duration,
                'notes' => $request->notes,
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'created',
                'target_type' => 'App\Timesheet',
                'target_id' => $timesheet->id,
                'description' => 'تم إنشاء سجل دوام جديد للمستخدم ' . ($timesheet->user->name ?? 'غير معروف') . ' في المشروع ' . ($timesheet->project->project_name ?? 'غير معروف') . ' لمدة ' . $timesheet->duration_formatted,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($timesheet),
                'auditable_id' => $timesheet->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $timesheet->toArray(), // جميع قيم سجل الدوام الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('timesheets.index')->with('success', 'تم إضافة سجل الدوام بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error creating timesheet: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة سجل الدوام: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Timesheet $timesheet)
    {
        // عادة لا توجد شاشة عرض تفصيلية لسجل الدوام، ولكن يمكن إضافتها إذا لزم الأمر
        return redirect()->route('timesheets.index');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Timesheet $timesheet)
    {
        $users = User::all();
        $projects = Project::all();
        $tasks = Task::all(); // تأكد من وجود موديل Task

        return view('timesheets.create_edit', compact('timesheet', 'users', 'projects', 'tasks'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Timesheet $timesheet)
    {
        $oldValues = $timesheet->toArray(); // <== احصل على القيم القديمة قبل التحديث

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'project_id' => 'required|exists:projects,id',
            'task_id' => 'nullable|exists:tasks,id',
            'work_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            // حساب المدة
            $startTime = Carbon::parse($request->start_time);
            $endTime = Carbon::parse($request->end_time);
            $duration = $endTime->diffInMinutes($startTime) / 60;

            $timesheet->update([
                'user_id' => $request->user_id,
                'project_id' => $request->project_id,
                'task_id' => $request->task_id,
                'work_date' => $request->work_date,
                'start_time' => $request->work_date . ' ' . $request->start_time,
                'end_time' => $request->work_date . ' ' . $request->end_time,
                'duration' => $duration,
                'notes' => $request->notes,
            ]);

            $newValues = $timesheet->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated',
                'target_type' => 'App\Timesheet',
                'target_id' => $timesheet->id,
                'description' => 'تم تحديث سجل دوام للمستخدم ' . ($timesheet->user->name ?? 'غير معروف') . ' في المشروع ' . ($timesheet->project->project_name ?? 'غير معروف') . ' لمدة ' . $timesheet->duration_formatted,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated', // عملية تحديث
                'auditable_type' => get_class($timesheet),
                'auditable_id' => $timesheet->id,
                'old_values' => $oldValues, // القيم القديمة
                'new_values' => $newValues, // القيم الجديدة
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('timesheets.index')->with('success', 'تم تحديث سجل الدوام بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating timesheet: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث سجل الدوام: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Timesheet $timesheet, Request $request) // <== أضف Request $request
    {
        $oldValues = $timesheet->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $timesheetId = $timesheet->id;
        $userName = $timesheet->user->name ?? 'غير معروف';
        $projectName = $timesheet->project->project_name ?? 'غير معروف'; // استخدام project_name
        $duration = $timesheet->duration_formatted;

        try {
            DB::beginTransaction();

            $timesheet->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Timesheet',
                'target_id' => $timesheetId,
                'description' => 'تم حذف سجل دوام للمستخدم ' . $userName . ' في المشروع ' . $projectName . ' لمدة ' . $duration,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Timesheet',
                'auditable_id' => $timesheetId,
                'old_values' => $oldValues, // القيم القديمة لسجل الدوام الذي تم حذفه
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('timesheets.index')->with('success', 'تم حذف سجل الدوام بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error deleting timesheet: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف سجل الدوام: ' . $e->getMessage());
        }
    }
}
