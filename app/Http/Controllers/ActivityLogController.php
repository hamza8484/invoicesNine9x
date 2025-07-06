<?php

namespace App\Http\Controllers;

use App\ActivityLog; // تأكد من استخدام App\Models\ActivityLog
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // قد تحتاج إلى middleware صلاحيات هنا، مثلاً للمدراء فقط
        // $this->middleware('can:view-activity-logs');
    }

    /**
     * Display a listing of the resource (activity logs).
     */
    public function index(Request $request)
    {
        // جلب سجلات الأنشطة مع المستخدم المرتبط بها
        // يمكن إضافة فلاتر أو بحث هنا
        $query = ActivityLog::with('user')->latest();

        // مثال على فلتر بسيط (يمكن توسيعه)
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('action') && $request->action != '') {
            $query->where('action', 'like', '%' . $request->action . '%');
        }
        // يمكنك إضافة فلاتر لتاريخ، نوع الهدف، إلخ.

        $activityLogs = $query->paginate(20); // تقسيم النتائج على صفحات

        // إذا أردت قائمة بالمستخدمين لفلترة السجلات
        $users = \App\User::orderBy('name')->get(); // تأكد من مساحة اسم موديل المستخدم

        return view('activity_logs.index', compact('activityLogs', 'users'));
    }

    // لن نحتاج لـ create, store, edit, update, destroy لسجلات الأنشطة عادةً
    // لأنها تُنشأ برمجياً وليس من واجهة المستخدم.
}