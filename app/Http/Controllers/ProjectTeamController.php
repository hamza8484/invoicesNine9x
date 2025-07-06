<?php

namespace App\Http\Controllers;

use App\Project; // تأكد من استيراد نموذج Project
use App\User;    // تأكد من استيراد نموذج User
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // <== أضف هذا الاستيراد
use Illuminate\Support\Facades\DB; // <== أضف هذا الاستيراد لاستخدام المعاملات (Transactions)
use Illuminate\Validation\Rule; // لاستخدام قاعدة التحقق unique مع الاستثناءات
use Carbon\Carbon; // <== أضف هذا الاستيراد لاستخدام now() مع المعاملات

class ProjectTeamController extends Controller
{
    /**
     * يجب على المستخدم أن يكون مسجل الدخول للوصول.
     */
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-project-teams');
    }

    /**
     * عرض قائمة أعضاء الفريق لمشروع معين.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function index(Project $project)
    {
        // جلب أعضاء الفريق لهذا المشروع معPivot (دورهم في المشروع وتاريخ التعيين)
        $teamMembers = $project->users()->withPivot('role_in_project', 'assigned_at')->get();

        return view('project_teams.index', compact('project', 'teamMembers'));
    }

    /**
     * عرض نموذج لإضافة عضو جديد لفريق مشروع معين.
     *
     * @param  \App\Models\Project  $project
     * @return \Illuminate\View\View
     */
    public function create(Project $project)
    {
        // جلب جميع المستخدمين الذين ليسوا بالفعل أعضاء في هذا المشروع
        $currentMemberIds = $project->users->pluck('id')->toArray();
        $availableUsers = User::whereNotIn('id', $currentMemberIds)
                               ->whereIn('role', ['project_manager', 'engineer', 'worker']) // الأدوار المسموح بها كأعضاء فريق
                               ->orderBy('name')
                               ->get();

        // الأدوار المتاحة لتعيينها داخل المشروع (يمكنك تخصيصها)
        $rolesInProject = ['site_engineer', 'supervisor', 'worker', 'accountant', 'designer', 'other'];

        return view('project_teams.create_edit', compact('project', 'availableUsers', 'rolesInProject'));
    }

    /**
     * تخزين عضو فريق جديد لمشروع معين.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'user_id' => [
                'required',
                'exists:users,id',
                // التأكد أن المستخدم غير مضاف لهذا المشروع بالفعل
                Rule::unique('project_user')->where(function ($query) use ($project) {
                    return $query->where('project_id', $project->id);
                }),
            ],
            'role_in_project' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            $assignedAt = Carbon::now(); // لتسجيل نفس الوقت في attach و AuditLog

            // إرفاق المستخدم بالمشروع مع البيانات الإضافية
            $project->users()->attach($request->user_id, [
                'role_in_project' => $request->role_in_project,
                'assigned_at' => $assignedAt, // تعيين تاريخ التعيين الحالي
            ]);

            $user = User::find($request->user_id); // جلب كائن المستخدم لتسجيله في السجلات

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'added_team_member',
                'target_type' => 'App\User', // المستخدم الذي تم إضافته
                'target_id' => $user->id,
                'description' => 'تم إضافة المستخدم ' . ($user->name ?? 'غير معروف') . ' إلى فريق المشروع ' . ($project->project_name ?? 'غير معروف') . ' بدور: ' . ($request->role_in_project ?? 'غير محدد'),
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'attached', // عملية إرفاق (إنشاء علاقة)
                'auditable_type' => 'App\Project', // الكيان الرئيسي المتأثر هو المشروع
                'auditable_id' => $project->id,
                'old_values' => null, // لا توجد قيم قديمة للعلاقة
                'new_values' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role_in_project' => $request->role_in_project,
                    'assigned_at' => $assignedAt->toDateTimeString(),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('projects.team.index', $project->id)->with('success', 'تم إضافة عضو الفريق بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error adding team member: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء إضافة عضو الفريق: ' . $e->getMessage());
        }
    }

    /**
     * عرض تفاصيل عضو فريق محدد (اختياري).
     * يمكن دمجها في edit أو الاستغناء عنها.
     */
    public function show(Project $project, User $user)
    {
        // لن نستخدم هذه الدالة بشكل منفصل حاليًا، لكن يمكن استخدامها لعرض تفاصيل المستخدم في هذا المشروع
        // يمكنك توجيهها إلى صفحة التعديل أو عرض مودال
        return redirect()->route('projects.team.edit', ['project' => $project->id, 'user' => $user->id]);
    }

    /**
     * عرض نموذج لتعديل دور عضو فريق موجود في مشروع معين.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\User  $user
     * @return \Illuminate\View\View
     */
    public function edit(Project $project, User $user)
    {
        // تأكد أن المستخدم هو بالفعل عضو في هذا المشروع
        if (!$project->users->contains($user)) {
            abort(404); // أو إعادة توجيه مع رسالة خطأ
        }

        // جلب الدور الحالي للمستخدم في هذا المشروع
        // يجب تحميل العلاقة pivot أولاً
        $user->load('projects'); // تأكد من تحميل علاقة projects على المستخدم
        $pivotData = $user->projects->where('id', $project->id)->first()->pivot;
        $currentRole = $pivotData->role_in_project;

        // الأدوار المتاحة لتعيينها داخل المشروع
        $rolesInProject = ['site_engineer', 'supervisor', 'worker', 'accountant', 'designer', 'other'];

        return view('project_teams.create_edit', compact('project', 'user', 'currentRole', 'rolesInProject'));
    }

    /**
     * تحديث دور عضو فريق موجود في مشروع معين.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Project  $project
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Project $project, User $user)
    {
        $request->validate([
            'role_in_project' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            // جلب القيم القديمة من الجدول المحوري قبل التحديث
            $oldPivotData = $project->users()->where('user_id', $user->id)->first()->pivot->toArray();
            $oldRoleInProject = $oldPivotData['role_in_project'];

            // تحديث الحقول الإضافية في الجدول المحوري
            $project->users()->updateExistingPivot($user->id, [
                'role_in_project' => $request->role_in_project,
            ]);

            // جلب القيم الجديدة من الجدول المحوري بعد التحديث
            $newPivotData = $project->users()->where('user_id', $user->id)->first()->pivot->toArray();
            $newRoleInProject = $newPivotData['role_in_project'];

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'updated_team_member_role',
                'target_type' => 'App\User', // المستخدم الذي تم تعديل دوره
                'target_id' => $user->id,
                'description' => 'تم تحديث دور المستخدم ' . ($user->name ?? 'غير معروف') . ' في المشروع ' . ($project->project_name ?? 'غير معروف') . ' من "' . ($oldRoleInProject ?? 'غير محدد') . '" إلى "' . ($newRoleInProject ?? 'غير محدد') . '"',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'pivot_updated', // عملية تحديث على الجدول المحوري
                'auditable_type' => 'App\Project', // الكيان الرئيسي المتأثر هو المشروع
                'auditable_id' => $project->id,
                'old_values' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role_in_project' => $oldRoleInProject,
                ],
                'new_values' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role_in_project' => $newRoleInProject,
                ],
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('projects.team.index', $project->id)->with('success', 'تم تحديث دور عضو الفريق بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error updating team member role: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث دور عضو الفريق: ' . $e->getMessage());
        }
    }

    /**
     * إزالة عضو فريق من مشروع معين.
     *
     * @param  \App\Models\Project  $project
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Project $project, User $user, Request $request) // <== أضف Request $request
    {
        try {
            DB::beginTransaction(); // <== بدء المعاملة

            // جلب القيم القديمة من الجدول المحوري قبل الحذف
            $oldPivotData = $project->users()->where('user_id', $user->id)->first()->pivot->toArray();
            $oldRoleInProject = $oldPivotData['role_in_project'];
            $assignedAt = $oldPivotData['assigned_at'];

            // فصل (detach) المستخدم من المشروع
            $project->users()->detach($user->id);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'removed_team_member',
                'target_type' => 'App\User', // المستخدم الذي تم إزالته
                'target_id' => $user->id,
                'description' => 'تم إزالة المستخدم ' . ($user->name ?? 'غير معروف') . ' من فريق المشروع ' . ($project->project_name ?? 'غير معروف') . ' (كان دوره: ' . ($oldRoleInProject ?? 'غير محدد') . ')',
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'detached', // عملية فصل (حذف علاقة)
                'auditable_type' => 'App\Project', // الكيان الرئيسي المتأثر هو المشروع
                'auditable_id' => $project->id,
                'old_values' => [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role_in_project' => $oldRoleInProject,
                    'assigned_at' => $assignedAt,
                ],
                'new_values' => null, // لا توجد قيم جديدة للعلاقة بعد الحذف
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return redirect()->route('projects.team.index', $project->id)->with('success', 'تم إزالة عضو الفريق بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error removing team member: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء إزالة عضو الفريق: ' . $e->getMessage());
        }
    }
}

