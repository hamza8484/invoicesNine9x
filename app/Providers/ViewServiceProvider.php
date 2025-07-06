<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Notification; // تأكد من المسار الصحيح
use Illuminate\Support\Facades\Auth;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // هذا الـ View Composer سيتم تشغيله قبل عرض أي View
        // ويقوم بتمرير الإشعارات غير المقروءة للمستخدم الحالي
        View::composer('*', function ($view) {
            if (Auth::check()) {
                $unreadNotifications = Notification::where('user_id', Auth::id())
                                                ->orWhereNull('user_id') // الإشعارات العامة
                                                ->unread()
                                                ->latest()
                                                ->take(5) // عرض آخر 5 إشعارات غير مقروءة في القائمة المنسدلة
                                                ->get();

                $totalUnreadNotificationsCount = Notification::where('user_id', Auth::id())
                                                            ->orWhereNull('user_id')
                                                            ->unread()
                                                            ->count();

                $view->with('unreadNotifications', $unreadNotifications);
                $view->with('totalUnreadNotificationsCount', $totalUnreadNotificationsCount);
            } else {
                $view->with('unreadNotifications', collect()); // تمرير مجموعة فارغة إذا لم يكن المستخدم مسجلاً
                $view->with('totalUnreadNotificationsCount', 0);
            }
        });
    }
}