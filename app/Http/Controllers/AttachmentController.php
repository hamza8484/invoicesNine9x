<?php

namespace App\Http\Controllers;

use App\Attachment; // تأكد من المسار الصحيح (App\Models\Attachment)
use App\ActivityLog; // <== أضف هذا الاستيراد
use App\AuditLog;    // <== أضف هذا الاستيراد


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // لاستخدام نظام الملفات
use Illuminate\Support\Facades\Auth; // لجلب المستخدم الحالي
use Illuminate\Support\Facades\DB; // لاستخدام المعاملات (Transactions)

class AttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // يمكنك إضافة middleware للتحقق من الصلاحيات هنا لاحقًا
        // $this->middleware('can:manage-attachments');
    }

    /**
     * Display a listing of all attachments.
     */
    public function index(Request $request)
    {
        // تأكد من مسار موديل User الصحيح: App\Models\User
        $query = Attachment::with('uploader');

        // يمكنك إضافة فلاتر هنا، مثلاً حسب نوع الكائن أو المستخدم
        if ($request->has('attachable_type') && $request->attachable_type != '') {
            $query->where('attachable_type', 'like', '%' . $request->attachable_type . '%');
        }
        if ($request->has('file_name') && $request->file_name != '') {
            $query->where('file_name', 'like', '%' . $request->file_name . '%');
        }
        if ($request->has('uploaded_by') && $request->uploaded_by != '') {
            $query->where('uploaded_by', $request->uploaded_by);
        }

        $attachments = $query->latest()->paginate(10);

        // جلب قائمة بالمستخدمين للفلترة
        $users = \App\Models\User::orderBy('name')->get(); // <== تأكد من مسار نموذج User الصحيح

        return view('attachments.index', compact('attachments', 'users'));
    }

    /**
     * Store a newly created attachment in storage.
     * هذه الدالة ستُستدعى عادةً عبر AJAX من نماذج أخرى (مثل إنشاء/تعديل فاتورة).
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // مطلوب، ملف، أقصى حجم 10 ميجابايت
            'attachable_type' => 'required|string', // مثال: App\Models\Invoice
            'attachable_id' => 'required|integer',  // مثال: 1
        ]);

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            // حفظ الملف في مجلد public/attachments داخل storage
            $path = $request->file('file')->store('public/attachments');
            $filePath = str_replace('public/', '', $path); // حفظ المسار بدون public/

            $attachment = Attachment::create([
                'attachable_type' => $request->attachable_type,
                'attachable_id' => $request->attachable_id,
                'file_path' => $filePath,
                'file_name' => $request->file('file')->getClientOriginalName(),
                'uploaded_by' => Auth::id(),
            ]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'uploaded', // أو 'created'
                'target_type' => 'App\Attachment',
                'target_id' => $attachment->id,
                'description' => 'تم رفع مرفق جديد: ' . $attachment->file_name . ' للكيان ' . class_basename($attachment->attachable_type) . ' رقم ' . $attachment->attachable_id,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'created', // عملية إنشاء
                'auditable_type' => get_class($attachment), // أو 'App\Models\Attachment'
                'auditable_id' => $attachment->id,
                'old_values' => null, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $attachment->toArray(), // جميع قيم المرفق الجديد
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return response()->json([
                'success' => true,
                'message' => 'تم رفع المرفق بنجاح.',
                'attachment' => $attachment,
                'download_url' => $attachment->download_url, // استخدام accessor
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error uploading attachment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع المرفق: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download the specified attachment.
     * @param Attachment $attachment
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Attachment $attachment)
    {
        // ليس هناك حاجة لتسجيل AuditLog هنا لأنها عملية قراءة/تنزيل وليست تغيير بيانات
        // ولكن يمكنك تسجيلها في ActivityLog إذا أردت تتبع عمليات التنزيل
        /*
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'downloaded',
            'target_type' => 'App\Models\Attachment',
            'target_id' => $attachment->id,
            'description' => 'تم تنزيل المرفق: ' . $attachment->file_name,
        ]);
        */

        if (Storage::disk('public')->exists($attachment->file_path)) {
            return Storage::disk('public')->download($attachment->file_path, $attachment->file_name);
        }

        return back()->with('error', 'الملف غير موجود.');
    }

    /**
     * Remove the specified attachment from storage.
     * @param Attachment $attachment
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Attachment $attachment)
    {
        $oldValues = $attachment->toArray(); // <== احصل على القيم القديمة قبل الحذف
        $attachmentId = $attachment->id;
        $attachmentFileName = $attachment->file_name;
        $attachableType = class_basename($attachment->attachable_type);
        $attachableId = $attachment->attachable_id;
        $filePath = $attachment->file_path; // احتفظ بالمسار لحذفه

        try {
            DB::beginTransaction(); // <== بدء المعاملة

            if (Storage::disk('public')->exists($filePath)) {
                Storage::disk('public')->delete($filePath);
            }
            $attachment->delete();

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted',
                'target_type' => 'App\Attachment',
                'target_id' => $attachmentId,
                'description' => 'تم حذف المرفق: ' . $attachmentFileName . ' من الكيان ' . $attachableType . ' رقم ' . $attachableId,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'deleted', // عملية حذف
                'auditable_type' => 'App\Attachment', // أو get_class($attachment)
                'auditable_id' => $attachmentId,
                'old_values' => $oldValues, // القيم القديمة للمرفق الذي تم حذفه
                'new_values' => null, // لا توجد قيم جديدة عند الحذف
                'ip_address' => request()->ip(), // استخدام request() لجلب IP
                'user_agent' => request()->header('User-Agent'), // استخدام request() لجلب User-Agent
            ]);

            DB::commit(); // <== إنهاء المعاملة بنجاح

            return back()->with('success', 'تم حذف المرفق بنجاح.');
        } catch (\Exception $e) {
            DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
            \Log::error('Error deleting attachment: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء حذف المرفق: ' . $e->getMessage());
        }
    }
}
