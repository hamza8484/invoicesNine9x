<?php

namespace App\Http\Controllers;

use App\Setting; // تأكد من المسار الصحيح
use App\ActivityLog; // تأكد من المسار الصحيح لموديل ActivityLog
use App\AuditLog;    // <== أضف هذا الاستيراد

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function edit()
    {
        // ابحث عن أول سجل للإعدادات، أو أنشئ واحدًا جديدًا إذا لم يكن موجودًا
        // يجب توفير قيم افتراضية غير NULL لجميع الحقول التي ليست nullable في الـ migration
        $setting = Setting::firstOrCreate(
            [], // شروط البحث (فارغة لنجلب أي سجل)
            [   // القيم الافتراضية التي ستستخدم عند الإنشاء إذا لم يتم العثور على سجل
                'company_name' => 'اسم الشركة الافتراضي',
                'commercial_register' => '', // <=== يجب أن تكون سلسلة نصية فارغة
                'tax_number' => '',          // <=== يجب أن تكون سلسلة نصية فارغة
                'email' => 'default@example.com',
                'phone' => '',               // <=== يجب أن تكون سلسلة نصية فارغة
                'address' => '',             // <=== يجب أن تكون سلسلة نصية فارغة
                'logo' => '',                // <=== يجب أن تكون سلسلة نصية فارغة (مسار الشعار)
            ]
        );
        return view('settings.create_edit', compact('setting'));
    }

    public function update(Request $request)
    {
        // ابحث عن أول سجل للإعدادات
        $setting = Setting::first(); // نستخدم first() هنا لأننا سنتعامل معها كـ update أو create بشكل صريح

        // إذا لم يكن هناك سجل إعدادات، قم بإنشاء واحد بقيم افتراضية
        $isNewSetting = false;
        if (!$setting) {
            $setting = new Setting();
            $isNewSetting = true;
            // يمكنك تعيين قيم افتراضية هنا إذا لم تكن متأكداً من أنها ستأتي من الـ request
            $setting->company_name = 'اسم الشركة الافتراضي';
            $setting->email = 'default@example.com';
            // بقية الحقول يمكن أن تكون فارغة افتراضياً أو يتم ملؤها من الـ request
        }

        $oldValues = $setting->toArray(); // <== احصل على القيم القديمة قبل التحديث/الإنشاء

        $request->validate([
            'company_name' => 'required|string|max:255',
            'commercial_register' => 'nullable|string|max:255',
            'tax_number' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        try {
            DB::beginTransaction();

            $logoPath = $setting->logo;

            if ($request->hasFile('logo')) {
                if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                    Storage::disk('public')->delete($setting->logo);
                }
                $logoPath = $request->file('logo')->store('public/logos');
                $logoPath = str_replace('public/', '', $logoPath);
            } elseif ($request->input('clear_logo')) {
                if ($setting->logo && Storage::disk('public')->exists($setting->logo)) {
                    Storage::disk('public')->delete($setting->logo);
                }
                $logoPath = ''; // <=== مهم: عند حذف الشعار، اجعل القيمة سلسلة نصية فارغة وليس NULL
            }

            $setting->company_name = $request->company_name;
            $setting->commercial_register = $request->commercial_register ?? ''; // تأكد من تحويل null إلى سلسلة فارغة
            $setting->tax_number = $request->tax_number ?? '';
            $setting->email = $request->email;
            $setting->phone = $request->phone ?? '';
            $setting->address = $request->address ?? '';
            $setting->logo = $logoPath;
            $setting->save(); // حفظ التغييرات أو إنشاء السجل الجديد

            $newValues = $setting->fresh()->toArray(); // <== احصل على القيم الجديدة بعد التحديث/الإنشاء

            // تسجيل النشاط العام (Activity Log)
            ActivityLog::create([
                'user_id' => Auth::id(),
                'action' => $isNewSetting ? 'created' : 'updated', // تحديد نوع الإجراء
                'target_type' => 'App\Setting', // <== تأكد من مسار الموديل الصحيح
                'target_id' => $setting->id,
                'description' => ($isNewSetting ? 'تم إنشاء ' : 'تم تحديث ') . 'إعدادات الشركة: ' . $setting->company_name,
            ]);

            // تسجيل التدقيق التفصيلي (Audit Log)
            AuditLog::create([
                'user_id' => Auth::id(),
                'action' => $isNewSetting ? 'created' : 'updated', // تحديد نوع الإجراء
                'auditable_type' => get_class($setting),
                'auditable_id' => $setting->id,
                'old_values' => $isNewSetting ? null : $oldValues, // لا توجد قيم قديمة عند الإنشاء
                'new_values' => $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => $request->header('User-Agent'),
            ]);

            DB::commit();

            return redirect()->route('settings.edit')->with('success', 'تم تحديث الإعدادات بنجاح.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating settings: ' . $e->getMessage());
            return back()->withInput()->with('error', 'حدث خطأ أثناء تحديث الإعدادات: ' . $e->getMessage());
        }
    }
}
