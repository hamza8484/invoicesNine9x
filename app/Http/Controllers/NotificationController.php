<?php

namespace App\Http\Controllers;

use App\Notification; // تأكد من المسار الصحيح
use App\User;         // لاستخدام موديل المستخدم
use App\ActivityLog;   // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;      // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('can:manage-notifications'); // يمكنك إضافة صلاحيات لاحقًا
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Notification::query();

        // فلترة الإشعارات للمستخدم الحالي فقط (إذا لم يكن مسؤولاً)
        // يمكنك تعديل هذا المنطق بناءً على نظام الصلاحيات الخاص بك
        if (!Auth::user()->is_admin) { // افترض أن لديك حقل is_admin في جدول المستخدمين
            $query->where('user_id', Auth::id())
                  ->orWhereNull('user_id'); // الإشعارات العامة
        }

        // فلاتر البحث
        if ($request->has('read_status') && $request->read_status != '') {
            if ($request->read_status == 'read') {
                $query->read();
            } elseif ($request->read_status == 'unread') {
                $query->unread();
            }
        }
        if ($request->has('type') && $request->type != '') {
            $query->where('type', $request->type);
        }
        if ($request->has('search') && $request->search != '') {
            $query->where(function($q) use ($request) {
                $q->where('title', 'like', '%' . $request->search . '%')
                  ->orWhere('message', 'like', '%' . $request->search . '%');
            });
        }

        $notifications = $query->latest()->paginate(15);

        // جلب أنواع الإشعارات الفريدة الموجودة للاستخدام في الفلتر
        $notificationTypes = Notification::distinct('type')->pluck('type')->filter()->toArray();

        return view('notifications.index', compact('notifications', 'notificationTypes'));
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Notification $notification, Request $request)
    {
        // تأكد أن المستخدم الحالي لديه صلاحية قراءة هذا الإشعار
        if ($notification->user_id === Auth::id() || is_null($notification->user_id) || Auth::user()->is_admin) {
            try {
                DB::beginTransaction(); // <== بدء المعاملة

                $oldValues = $notification->toArray(); // <== احصل على القيم القديمة قبل التحديث

                $notification->markAsRead();

                $newValues = $notification->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

                // تسجيل النشاط العام (Activity Log)
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'read',
                    'target_type' => 'App\Notification',
                    'target_id' => $notification->id,
                    'description' => 'تم قراءة الإشعار: ' . $notification->title,
                ]);

                // تسجيل التدقيق التفصيلي (Audit Log)
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'updated', // عملية تحديث (تغيير حالة is_read)
                    'auditable_type' => get_class($notification),
                    'auditable_id' => $notification->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);

                DB::commit(); // <== إنهاء المعاملة بنجاح

                // التحقق من وجود redirect_to في الـ request
                if ($request->has('redirect_to') && filter_var($request->input('redirect_to'), FILTER_VALIDATE_URL)) {
                    return redirect($request->input('redirect_to'))->with('success', 'تم تحديد الإشعار كمقروء.');
                }

                return back()->with('success', 'تم تحديد الإشعار كمقروء.');

            } catch (\Exception $e) {
                DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
                \Log::error('Error marking notification as read: ' . $e->getMessage());
                return back()->with('error', 'حدث خطأ أثناء تحديد الإشعار كمقروء: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'ليس لديك صلاحية لتحديد هذا الإشعار كمقروء.');
    }

    /**
     * Mark a specific notification as unread.
     */
    public function markAsUnread(Notification $notification, Request $request) // <== أضف Request $request
    {
        // تأكد أن المستخدم الحالي لديه صلاحية تغيير حالة هذا الإشعار
        if ($notification->user_id === Auth::id() || is_null($notification->user_id) || Auth::user()->is_admin) {
            try {
                DB::beginTransaction(); // <== بدء المعاملة

                $oldValues = $notification->toArray(); // <== احصل على القيم القديمة قبل التحديث

                $notification->markAsUnread();

                $newValues = $notification->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث

                // تسجيل النشاط العام (Activity Log)
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'unread',
                    'target_type' => 'App\Notification',
                    'target_id' => $notification->id,
                    'description' => 'تم تحديد الإشعار كغير مقروء: ' . $notification->title,
                ]);

                // تسجيل التدقيق التفصيلي (Audit Log)
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'updated', // عملية تحديث (تغيير حالة is_read)
                    'auditable_type' => get_class($notification),
                    'auditable_id' => $notification->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->header('User-Agent'),
                ]);

                DB::commit(); // <== إنهاء المعاملة بنجاح

                return back()->with('success', 'تم تحديد الإشعار كغير مقروء.');

            } catch (\Exception $e) {
                DB::rollBack(); // <== التراجع عن المعاملة في حالة الخطأ
                \Log::error('Error marking notification as unread: ' . $e->getMessage());
                return back()->with('error', 'حدث خطأ أثناء تحديد الإشعار كغير مقروء: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'ليس لديك صلاحية لتحديد هذا الإشعار كغير مقروء.');
    }

    /**
     * Mark all notifications for the current user as read.
     */
    public function markAllAsRead(Request $request) // <== أضف Request $request
    {
        try {
            DB::beginTransaction();

            // الحصول على الإشعارات غير المقروءة قبل التحديث لتسجيلها في AuditLog (اختياري، أو يمكن تسجيلها كعملية جماعية)
            // بما أن هذه عملية جماعية، لن نأخذ old_values/new_values لكل إشعار على حدة
            // بل سنسجل أن عملية "تحديد الكل كمقروء" قد حدثت.
            $affectedNotificationsCount = Notification::where('user_id', Auth::id())
                                                    ->orWhereNull('user_id') // الإشعارات العامة
                                                    ->unread() // فقط الإشعارات غير المقروءة
                                                    ->update(['is_read' => true]);

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => 'marked_all_read',
                'target_type' => 'App\Notification',
                'target_id' => null, // لا يوجد هدف محدد فردي
                'description' => 'تم تحديد جميع الإشعارات (' . $affectedNotificationsCount . ' إشعار) كـ مقروءة للمستخدم: ' . Auth::user()->name,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log) لعملية جماعية
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => 'bulk_update', // أو 'marked_all_notifications_read'
                'auditable_type' => 'App\Notification', // نوع الكيان المتأثر
                'auditable_id' => null, // لا يوجد معرف فردي
                'old_values' => null, // يصعب تمثيلها لعملية جماعية
                'new_values' => ['message' => 'تم تحديث حالة ' . $affectedNotificationsCount . ' إشعار إلى مقروء.'], // رسالة وصفية
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return back()->with('success', 'تم تحديد جميع الإشعارات كمقروءة.');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error marking all notifications as read: ' . $e->getMessage());
            return back()->with('error', 'حدث خطأ أثناء تحديد جميع الإشعارات كمقروءة: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Notification $notification, Request $request) // <== أضف Request $request
    {
        // تأكد أن المستخدم الحالي لديه صلاحية حذف هذا الإشعار
        // (إما هو صاحب الإشعار، أو الإشعار عام، أو المستخدم مسؤول)
        if ($notification->user_id === Auth::id() || is_null($notification->user_id) || Auth::user()->is_admin) {
            try {
                DB::beginTransaction();

                $oldValues = $notification->toArray(); // <== احصل على القيم القديمة قبل الحذف
                $notificationTitle = $notification->title;
                $notificationId = $notification->id;

                $notification->delete();

                // تسجيل النشاط العام (Activity Log)
                ActivityLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'deleted',
                    'target_type' => 'App\Notification',
                    'target_id' => $notificationId,
                    'description' => 'تم حذف الإشعار: ' . $notificationTitle,
                ]);

                // تسجيل التدقيق التفصيلي (Audit Log)
                AuditLog::create([
                    'user_id' => Auth::id(),
                    'action' => 'deleted',
                    'auditable_type' => 'App\Notification',
                    'auditable_id' => $notificationId,
                    'old_values' => $oldValues,
                    'new_values' => null, // لا توجد قيم جديدة عند الحذف
                    'ip_address' => $request->ip(), // <== استخدام $request لجلب IP
                    'user_agent' => $request->header('User-Agent'), // <== استخدام $request لجلب User-Agent
                ]);

                DB::commit();

                return back()->with('success', 'تم حذف الإشعار بنجاح.');

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error deleting notification: ' . $e->getMessage());
                return back()->with('error', 'حدث خطأ أثناء حذف الإشعار: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'ليس لديك صلاحية لحذف هذا الإشعار.');
    }

    /**
     * Helper method to create a new notification.
     * This method can be called from other controllers or services.
     *
     * @param int|null $userId If null, notification is for all users.
     * @param string $title
     * @param string $message
     * @param string|null $type
     * @return Notification
     */
    public static function createNotification(?int $userId, string $title, string $message, ?string $type = null): Notification
    {
        // لا يمكن استخدام DB::beginTransaction هنا مباشرة لأنها دالة ثابتة
        // ويجب أن تتم إدارة المعاملات من الدالة التي تستدعي createNotification
        $notification = Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'is_read' => false, // Always unread when created
        ]);

        // تسجيل التدقيق التفصيلي (Audit Log) لإنشاء الإشعار
        // نستخدم request() هنا لأن هذه دالة ثابتة ولا تستقبل Request كمعامل
        AuditLog::create([
            'user_id' => Auth::id(), // المستخدم الحالي الذي قام بإنشاء الإشعار (إذا كان موجوداً)
            'action' => 'created',
            'auditable_type' => get_class($notification),
            'auditable_id' => $notification->id,
            'old_values' => null,
            'new_values' => $notification->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->header('User-Agent'),
        ]);

        return $notification;
    }
}
