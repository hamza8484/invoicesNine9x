<?php

namespace App\Http\Controllers;

use App\AuditLog; // تأكد من المسار الصحيح
use App\User;     // لاستخدام موديل المستخدم

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('can:view-audit-logs'); // يمكنك إضافة صلاحيات لاحقًا
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with('user');

        // فلاتر البحث
        if ($request->has('user_id') && $request->user_id != '') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->has('action') && $request->action != '') {
            $query->where('action', $request->action);
        }
        if ($request->has('auditable_type') && $request->auditable_type != '') {
            $query->where('auditable_type', 'like', '%' . $request->auditable_type . '%');
        }
        if ($request->has('auditable_id') && $request->auditable_id != '') {
            $query->where('auditable_id', $request->auditable_id);
        }
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('old_values', 'like', '%' . $request->search . '%')
                  ->orWhere('new_values', 'like', '%' . $request->search . '%')
                  ->orWhere('ip_address', 'like', '%' . $request->search . '%')
                  ->orWhere('user_agent', 'like', '%' . $request->search . '%');
            });
        }

        $auditLogs = $query->latest()->paginate(15);

        // جلب البيانات اللازمة للفلاتر
        $users = User::all();
        $actions = AuditLog::distinct('action')->pluck('action')->toArray();
        $auditableTypes = AuditLog::distinct('auditable_type')->pluck('auditable_type')->toArray();

        return view('audit_logs.index', compact('auditLogs', 'users', 'actions', 'auditableTypes'));
    }

    /**
     * Display the specified resource.
     */
    public function show(AuditLog $auditLog)
    {
        return view('audit_logs.show', compact('auditLog'));
    }

    // لا توجد دوال store, update, destroy مباشرة من الواجهة لهذا المتحكم
    // لأن سجلات التدقيق يتم إنشاؤها وتعديلها بواسطة النظام عند حدوث الأحداث
    // ويجب أن تكون غير قابلة للتعديل للحفاظ على سلامتها.
}
